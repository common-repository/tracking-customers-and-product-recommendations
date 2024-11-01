<?php
defined('ABSPATH') or wp_die('Nope, not accessing this');
class NL2A_TCPR_Shortcode_Products_Viewed{
    public static function products_viewed() {

        if(!function_exists('wc_get_products')) {
            return;
        }
        
        $products_viewed = ! empty( $_COOKIE['nl2a_tcpr_products_viewed'] ) ? (array) explode( '|', wp_unslash( $_COOKIE['nl2a_tcpr_products_viewed'] ) ) : array();
		$products_viewed = array_reverse( array_filter( array_map( 'absint', $products_viewed ) ) );
        $save_customer = get_option('nl2a-tcpr-save-customers');
        $ip = nl2a_tcpr_get_ip();
	    $user_agent = nl2a_tcpr_get_user_agent();
        if(!empty($save_customer)){
            $current_tc = NL2A_TCPR_Tables::get(['customer_user_agent'=>$user_agent, 'customer_ip'=>$ip]);
            if(!empty($current_tc) && empty($products_viewed)){
                $products_viewed = unserialize($current_tc['products_viewed']);
            }
        }
        ob_start();
        if(!empty($products_viewed)){
            $paged                   = (get_query_var('paged')) ? absint(get_query_var('paged')) : 1;
            $ordering                = WC()->query->get_catalog_ordering_args();
            $ordering['orderby']     = array_shift(explode(' ', $ordering['orderby']));
            $ordering['orderby']     = stristr($ordering['orderby'], 'price') ? 'meta_value_num' : $ordering['orderby'];
            $products_per_page       = apply_filters('loop_shop_per_page', wc_get_default_products_per_row() * wc_get_default_product_rows_per_page());
        
            $products_data       = wc_get_products(array(
                'status'               => 'publish',
                'limit'                => $products_per_page,
                'page'                 => $paged,
                'paginate'             => true,
                'return'               => 'ids',
                'post_status'    => 'publish',
                'post_type'      => 'product',
                'include'       => $products_viewed,
                'order'        => $ordering['orderby'],
            ));
            wc_set_loop_prop('current_page', $paged);
            wc_set_loop_prop('is_paginated', wc_string_to_bool(true));
            wc_set_loop_prop('page_template', get_page_template_slug());
            wc_set_loop_prop('per_page', $products_per_page);
            wc_set_loop_prop('total', $products_data->total);
            wc_set_loop_prop('total_pages', $products_data->max_num_pages);
            echo '<div class="woocommerce products_viewed_page">';
            if($products_data) {
                do_action('woocommerce_before_shop_loop');
                woocommerce_product_loop_start();
                foreach($products_data->products as $product) {
                    $post_object = get_post($product);
                    setup_postdata($GLOBALS['post'] =& $post_object);
                    wc_get_template_part('content', 'product');
                }
                wp_reset_postdata();
                woocommerce_product_loop_end();
                do_action('woocommerce_after_shop_loop');
            } else {
                do_action('woocommerce_no_products_found');
            }
            echo '</div>';
        }else{
            echo '<div class="woocommerce product_recommendations_page">';
            do_action('woocommerce_no_products_found');
            echo '</div>';
        }

        $content = ob_get_clean();
        return $content;
    }
}
add_shortcode( 'nl2a_tcpr_products_viewed', array( 'NL2A_TCPR_Shortcode_Products_Viewed', 'products_viewed' ) );