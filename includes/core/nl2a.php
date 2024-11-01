<?php

function nl2a_tcpr_paging($paged, $max_page, $args = array()){
	echo paginate_links( array(
		'base' => preg_replace('/\?.*/', '', get_pagenum_link(1)) . '%_%',
		'format' => '?paged=%#%',
		'current' => max( 1, $paged ),
		'total' => $max_page,
		'add_args' => $args
	) );
}

function nl2a_tcpr_load_plugin_textdomain() {
    load_plugin_textdomain( 'nl2a', false, NL2A_TCPR_PATH. '/languages' );
}
add_action( 'plugins_loaded', 'nl2a_tcpr_load_plugin_textdomain', 0 );

function nl2a_tcpr_get_product_order_by_user(){
	$current_customer = wp_get_current_user();
	if(empty($current_customer)){
		return false;
	}
	global $wpdb;
	$duration_orders = (!empty(get_option('nl2a-tcpr-time-duration-orders')) && is_numeric(get_option('nl2a-tcpr-time-duration-orders')))?get_option('nl2a-tcpr-time-duration-orders'):10;
	$query = "
		SELECT p.ID as id
		FROM {$wpdb->prefix}posts p
		INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim
			ON p.ID = oim.meta_value
		INNER JOIN {$wpdb->prefix}woocommerce_order_items oi
			ON oim.order_item_id = oi.order_item_id
		INNER JOIN {$wpdb->prefix}posts as o
			ON o.ID = oi.order_id
		INNER JOIN {$wpdb->prefix}postmeta as mt
			ON o.ID = mt.post_id
		WHERE p.post_type = 'product'
		AND p.post_status = 'publish'
		AND o.post_status IN ('wc-processing','wc-completed','wc-on-hold')
		AND o.post_date >= DATE_SUB(NOW(), INTERVAL $duration_orders DAY)
		AND oim.meta_key = '_product_id'
		AND ((mt.meta_key = '_customer_user' AND mt.meta_value = $current_customer->ID) OR (mt.meta_key = '_billing_email' AND mt.meta_value = '$current_customer->user_email'))
		GROUP BY p.ID
	";
	$results = $wpdb->get_results($query, ARRAY_A);
	if(!empty($results)){
		return wp_list_pluck( $results, 'id' );
	}else{
		return false;
	}
}

function nl2a_tcpr_get_product_recommendations(){
	$products_viewed = ! empty( $_COOKIE['nl2a_tcpr_products_viewed'] ) ? (array) explode( '|', wp_unslash( $_COOKIE['nl2a_tcpr_products_viewed'] ) ) : array();
	$products_viewed = array_reverse( array_filter( array_map( 'absint', $products_viewed ) ) );
	$recommendations_order = get_option('nl2a-tcpr-recommendations-order');
	$save_customer = get_option('nl2a-tcpr-save-customers');
	$order_product = false;
	$current_tc = false;
	$ip = nl2a_tcpr_get_ip();
	$user_agent = nl2a_tcpr_get_user_agent();
	
	if(!empty($recommendations_order)){
		$order_product = nl2a_tcpr_get_product_order_by_user();
		if(!empty($order_product)){
			$products_viewed = $order_product;
		}
	}
	if(!empty($save_customer)){
		$current_tc = NL2A_TCPR_Tables::get(['customer_user_agent'=>$user_agent, 'customer_ip'=>$ip]);
		if(!empty($current_tc) && empty($products_viewed)){
			$products_viewed = unserialize($current_tc['products_viewed']);
		}
	}
	
	if(!is_array($products_viewed) || empty($products_viewed)){
		return false;
	}
	global $wpdb;
	$products_viewed = array_reverse( array_filter( array_map( 'absint', $products_viewed ) ) );
	$recommendations_taxonomies = get_option('nl2a-tcpr-recommendations-taxonomies');
	$product_taxonomy = ['product_cat'];
	if(!empty($recommendations_taxonomies) && is_array($recommendations_taxonomies)){
		$product_taxonomy = $recommendations_taxonomies;
	}
	if(!empty($product_taxonomy) && is_array($product_taxonomy)){
		$where_taxonomy = [];
		foreach($product_taxonomy as $pt){
			$where_taxonomy[] = "tt.taxonomy = '$pt'";
		}
		$where_taxonomy = " AND (".implode(" OR ", $where_taxonomy).") ";
	}else{
		$where_taxonomy = "";
	}
	$query = "SELECT tr.object_id FROM {$wpdb->prefix}term_relationships as tr WHERE tr.term_taxonomy_id IN (SELECT t.term_id FROM {$wpdb->prefix}terms as t INNER JOIN {$wpdb->prefix}term_taxonomy as tt on t.term_id = tt.term_id WHERE t.term_id IN (SELECT tr.term_taxonomy_id FROM {$wpdb->prefix}term_relationships as tr INNER JOIN {$wpdb->prefix}posts as p1 on tr.object_id = p1.ID WHERE tr.object_id IN (".implode(',', $products_viewed).") GROUP BY tr.term_taxonomy_id) $where_taxonomy) AND tr.object_id NOT IN (".implode(',', $products_viewed).") GROUP BY tr.object_id";
	$results = $wpdb->get_results($query);
	if(!empty($results) && !is_wp_error( $results )){
		return wp_list_pluck( $results, 'object_id' );
	}else{
		return false;
	}
}

function nl2a_tcpr_get_track_current_customer(){
	global $wpdb;
	$customer_id = get_current_user_id();
	if($customer_id){
		
	}
}

function nl2a_tcpr_track_viewed_products() {
	if ( ! is_singular( 'product' ) ){
		return;
	}

	global $post;
	$save_customers = get_option('nl2a-tcpr-save-customers');
	$cookie_expires = get_option('nl2a-tcpr-cookie-expires');

	if ( empty( $_COOKIE['nl2a_tcpr_products_viewed'] ) ) {
		$products_viewed = array();
	} else {
		$products_viewed = wp_parse_id_list( (array) explode( '|', wp_unslash( $_COOKIE['nl2a_tcpr_products_viewed'] ) ) );
	}

	$keys = array_flip( $products_viewed );

	if ( isset( $keys[ $post->ID ] ) ) {
		unset( $products_viewed[ $keys[ $post->ID ] ] );
	}

	$products_viewed[] = $post->ID;

	if ( count( $products_viewed ) > 15 ) {
		array_shift( $products_viewed );
	}

	nl2a_tcpr_setcookie( 'nl2a_tcpr_products_viewed', implode( '|', $products_viewed ), $cookie_expires );
	if($save_customers){
		$args = [];
		$args['products_viewed'] = $products_viewed;
		$current_user = wp_get_current_user();
		$current_date = date( 'Y-m-d', current_time( 'timestamp', 0 ) );
		$ip = nl2a_tcpr_get_ip();
		$user_agent = nl2a_tcpr_get_user_agent();
		$args['customer_user_agent'] = $user_agent;
		$args['customer_ip'] = $ip;
		if($current_user->exists()){
			$args['customer_id'] = $current_user->ID;
			$args['customer_email'] = $current_user->user_email;
			$current_tc = NL2A_TCPR_Tables::get(['customer_id'=>$current_user->ID, 'created_at'=>$current_date]);
			if(!$current_tc){
				$current_tc = NL2A_TCPR_Tables::get(['customer_ip'=>$ip, 'created_at'=>$current_date, 'customer_user_agent'=>$user_agent]);
			}
		}else{
			$current_tc = NL2A_TCPR_Tables::get(['customer_ip'=>$ip, 'created_at'=>$current_date, 'customer_user_agent'=>$user_agent]);
		}
		
		if($current_tc){
			$args['updated_at'] = current_time('mysql');
			$args['products_viewed'] = array_unique(array_merge(unserialize($current_tc['products_viewed']), $products_viewed));
			NL2A_TCPR_Tables::update($args, $current_tc);
		}else{
			$args['created_at'] = current_time('mysql');
			$args['updated_at'] = current_time('mysql');
			NL2A_TCPR_Tables::insert($args);
		}
	}
}
add_action( 'template_redirect', 'nl2a_tcpr_track_viewed_products', 20 );

function nl2a_tcpr_setcookie($name, $value, $expire = 0, $secure = false, $httponly = false){
    if ( ! headers_sent() ) {
        if(is_numeric($expire) && $expire > 0){
            $expire = time() + (86400 * $expire);
        }else{
            $expire = 0;
        }
        setcookie($name, $value, $expire, "/", COOKIE_DOMAIN, $secure, $httponly);
    } elseif ( Constants::is_true( 'WP_DEBUG' ) ) {
        headers_sent( $file, $line );
        trigger_error( "{$name} cookie cannot be set - headers already sent by {$file} on line {$line}", E_USER_NOTICE );
    }
}

function nl2a_tcpr_get_ip(){
	$ip = false;
    if (isset($_SERVER['HTTP_CLIENT_IP'])){
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }else if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }else if(isset($_SERVER['HTTP_X_FORWARDED'])){
        $ip = $_SERVER['HTTP_X_FORWARDED'];
    }else if(isset($_SERVER['HTTP_FORWARDED_FOR'])){
        $ip = $_SERVER['HTTP_FORWARDED_FOR'];
    }else if(isset($_SERVER['HTTP_FORWARDED'])){
        $ip = $_SERVER['HTTP_FORWARDED'];
    }else if(isset($_SERVER['REMOTE_ADDR'])){
        $ip = $_SERVER['REMOTE_ADDR'];
    }
	return $ip;
}

function nl2a_tcpr_get_user_agent(){
	$user_agent = $_SERVER['HTTP_USER_AGENT'];
	return $user_agent;
}

function nl2a_tcpr_search_customer(){
	if ( ! wp_verify_nonce( $_POST['nonce'], 'nl2a-nonce' ) && !isset($_POST['s']) ) {
		die ( 'Error!');
	}
	$s = esc_attr( $_POST['s'] );
	$args = array (
		'order'      => 'ASC',
		'orderby'    => 'display_name',
		'search'     => '*' . $s . '*',
		'search_columns' => array(
			'user_login',
			'user_nicename',
			'user_email',
			'user_url',
		)
	);
	$wp_user_query = new WP_User_Query( $args );
	$customers = $wp_user_query->get_results();
	$results = [];
	if($customers){
		foreach($customers as $customer){
			$results[] = [
				'id'	=>	$customer->ID,
				'display_name'	=>	$customer->display_name.'('.$customer->user_email.')'
			];
		}
	}
	wp_die(json_encode($results));
}
add_action('wp_ajax_nl2a_tcpr_search_customer', 'nl2a_tcpr_search_customer');
add_action('wp_ajax_nopriv_nl2a_tcpr_search_customer', 'nl2a_tcpr_search_customer');

function nl2a_tcpr_search_page(){
	if ( ! wp_verify_nonce( $_POST['nonce'], 'nl2a-nonce' ) && !isset($_POST['s']) ) {
		die ( 'Error!');
	}
	$results = [];
	$s = esc_attr( $_POST['s'] );
	$args = [
		's'	=>	$s,
		'post_type'	=>	'page',
		'post_status'	=>	'publish',
		'posts_per_page'	=>	10,
	];
	$wp_query = new WP_Query($args);
	if($wp_query->have_posts()){
		while($wp_query->have_posts()){
			$wp_query->the_post();
			$results[] = [
				'id'	=>	get_the_ID(),
				'title'	=>	get_the_title()
			];
		}
	}
	wp_die(json_encode($results));
}
add_action('wp_ajax_nl2a_tcpr_search_page', 'nl2a_tcpr_search_page');
add_action('wp_ajax_nopriv_nl2a_tcpr_search_page', 'nl2a_tcpr_search_page');

function nl2a_tcpr_get_page_id_by_shortcode($shortcode){
	if(empty($shortcode)){
		return false;
	}
	$pages = get_pages();
	foreach($pages as $page){
		if ( strstr( $page->post_content, $shortcode ) ) {
			return $page->ID;
		}
	}
}