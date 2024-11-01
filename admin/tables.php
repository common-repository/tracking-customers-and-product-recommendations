<?php
defined('ABSPATH') or wp_die('Nope, not accessing this');
if(!class_exists('NL2A_TCPR_Tables')):
class NL2A_TCPR_Tables{
    public $table_name;
    public $charset_cl;
    public static $fields = ['id', 'customer_id', 'customer_email', 'customer_ip', 'customer_user_agent', 'products_views', 'created_at', 'updated_at', 'custom_meta'];

    public function __construct(){
        global $wpdb;
        $this->table_name = $wpdb->prefix.'nl2a_tcpr_tracking_customers';
        $this->charset_cl = $wpdb->get_charset_collate();

        add_action( 'activated_plugin', array($this, 'tables'), 10, 2 );
        add_action('wp_ajax_nl2a_tcpr_delete_tc', array($this, 'ajax_delete'));
        add_action('wp_ajax_nopriv_nl2a_tcpr_delete_tc', array($this, 'ajax_delete'));
        add_action('wp_ajax_nl2a_tcpr_detail', array($this, 'ajax_view_detail'));
        add_action('wp_ajax_nopriv_nl2a_tcpr_detail', array($this, 'ajax_view_detail'));
    }

    public static function table_name(){
        global $wpdb;
        return $wpdb->prefix.'nl2a_tcpr_tracking_customers';
    }

    public static function charset_collate(){
        global $wpdb;
        return $wpdb->get_charset_collate();
    }

    public function tables(){
        $sql = "CREATE TABLE ".self::table_name()." (
            `id` int(0) NOT NULL AUTO_INCREMENT,
            `customer_id` varchar(255) NULL,
            `customer_email` varchar(255) NULL,
            `customer_ip` varchar(255) NULL,
            `customer_user_agent` varchar(255) NULL,
            `products_viewed` longtext NULL,
            `created_at` datetime(0) NULL,
            `updated_at` datetime(0) NULL,
            PRIMARY KEY (`id`)
        )".self::charset_collate().";";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    public static function total($data = array()){
        global $wpdb;
        $where = [];
        if(!empty($data)){
            foreach($data as $k=>$v){
                if(!is_array($v)){
                    if($k == 'created_at'){
                        $where[] = "Date($k) = '".esc_sql( $v )."'";
                    }else{
                        $where[] = "$k = '".esc_sql( $v )."'";
                    }
                }
            }
        }else{
            $where[] = "1=1";
        }
        $results = $wpdb->get_row("select COUNT(*) AS COUNT from ".self::table_name()." where ".implode("AND ", $where), ARRAY_A);
        if(is_wp_error( $results )){
            return 0;
        }
        return $results['COUNT'];
    }

    public static function results_page($data = array(), $paged = 1, $number = 30){
        global $wpdb;
        $start = 0;
        $limit = "limit 0,$number";
        $where = [];
        if($paged > 1 && is_numeric($paged)){
            $start = ($paged - 1) * $number;
            $limit = "limit $start,$number";
        }
        if($paged == 0 || !is_numeric($paged)){
            $limit = "";
        }
        if($data){
            foreach($data as $k=>$v){
                if(in_array($k, self::$fields)){
                    if($k == 'custom_meta'){
                        foreach($v as $v1){
                            $compare = isset($v1['compare'])?$v1['compare']:"=";
                            if(isset($v1['type'])){
                                switch($v1['type']){
                                    case 'date':
                                        $where[] = "Date(".$v1['key'].") $compare '".esc_sql( $v1['value'] )."'";
                                        break;
                                    case 'DATE':
                                        $where[] = "Date(".$v1['key'].") $compare '".esc_sql( $v1['value'] )."'";
                                        break;
                                    case 'NUMERIC':
                                        $where[] = $v1['key']." $compare ".esc_sql( $v1['value'] )." + 0";
                                        break;
                                    case 'numeric':
                                        $where[] = $v1['key']." $compare ".esc_sql( $v1['value'] )." + 0";
                                        break;
                                }
                            }else{
                                $where[] = $v1['key']." $compare '".esc_sql( $v1['value'] )."'";
                            }
                        }
                    }else{
                        $compare = isset($v['compare'])?$v['compare']:"=";
                        if(isset($v['type'])){
                            switch($v['type']){
                                case 'date':
                                    $where[] = "Date($k) $compare '".esc_sql( $v['value'] )."'";
                                    break;
                                case 'DATE':
                                    $where[] = "Date($k) $compare '".esc_sql( $v['value'] )."'";
                                    break;
                                case 'NUMERIC':
                                    $where[] = "$k $compare ".esc_sql( $v['value'] )." + 0";
                                    break;
                                case 'numeric':
                                    $where[] = "$k $compare ".esc_sql( $v['value'] )." + 0";
                                    break;
                            }
                        }else{
                            $where[] = "$k $compare '".esc_sql( $v['value'] )."'";
                        }
                    }
                }
            }
        }
        if(empty($where)){
            $where[] = "1=1";
        }
        $orderby = " ORDER BY created_at DESC";
        if(isset($data['orderby']) && !empty($data['orderby']) && isset($data['order']) && !empty($data['order'])){
            $orderby = " ORDER BY ".$data['orderby']." ".$data['order'];
        }
        $results = $wpdb->get_results("select * from ".self::table_name()." where ".implode(" AND ", $where)." $orderby $limit", ARRAY_A);
        if(is_wp_error( $results )){
            return false;
        }
        return $results;
    }

    public static function get($data){
        if(!is_array($data) || empty($data)){
            return false;
        }
        global $wpdb;
        $where = [];
        foreach($data as $k=>$v){
            if(!is_array($v)){
                if($k == 'created_at'){
                    $where[] = "Date($k) = '".esc_sql( $v )."'";
                }else{
                    $where[] = "$k = '".esc_sql( $v )."'";
                }
            }
        }
        $results = $wpdb->get_row("select * from ".self::table_name()." where ".implode("AND ", $where)." ORDER BY created_at DESC", ARRAY_A);
        if(is_wp_error( $results )){
            return false;
        }
        return $results;
    }

    public static function insert($data){
        if(!is_array($data) || empty($data)){
            return false;
        }
        global $wpdb;
        $current_data = self::get($data);
        if(!empty($current_data)){
            $id = $current_data['id'];
        }else{
            foreach($data as $k=>$v){
                if(is_array($v)){
                    $data[$k] = serialize($v);
                }else{
                    $data[$k] = esc_sql($v);
                }
            }
            $data['updated_at'] = current_time( 'mysql' );
            $data['created_at'] = current_time( 'mysql' );
            $wpdb->insert(self::table_name(), $data);
            $id = $wpdb->insert_id;
        }
        
        return $id;
    }

    public static function update($data, $where){
        if(!is_array($data) || empty($data) || !is_array($where) || empty($where)){
            return false;
        }
        global $wpdb;
        foreach($data as $k=>$v){
            if(is_array($v)){
                $data[$k] = serialize($v);
            }else{
                $data[$k] = esc_sql($v);
            }
        }
        $wpdb->update(self::table_name(), $data, $where);
    }

    public static function delete($where){
        if(!is_array($where) || empty($where)){
            return false;
        }
        global $wpdb;
        $wpdb->delete(self::table_name(), $where);
    }

    public function ajax_delete(){
        if ( ! wp_verify_nonce( $_POST['nonce'], 'nl2a-nonce' ) && !isset($_POST['tc']) ) {
            die ( 'Error!');
        }
        global $wpdb;
        $tc = array_map('intval', $_POST['tc']);
        if(is_array($tc)){
            $results = $wpdb->query("delete from ".self::table_name()." where id in (".implode(",", $tc).")");
            if(!is_wp_error( $results )){
                wp_die(json_encode(['error'=>0]));
            }else{
                wp_die(json_encode(['error'=>1]));
            }
        }
        wp_die(json_encode(['error'=>1]));
    }

    public function ajax_view_detail(){
        if ( ! wp_verify_nonce( $_POST['nonce'], 'nl2a-nonce' ) && !isset($_POST['id']) ) {
            die ( 'Error!');
        }
        global $wpdb;
        $id = (int)$_POST['id'];
        $tc_detail = self::get(['id'=>$id]);
        if($tc_detail){
        ?>
        <table class="wp-list-table widefat fixed striped table-view-list tracking-customer-detail">
            <tr>
                <th class="manage-column" width="100px"><?php _e('Email', 'nl2a'); ?></th>
                <td><?php echo esc_html($tc_detail['customer_email']); ?></td>
            </tr>
            <tr>
                <th class="manage-column" width="100px"><?php _e('IP', 'nl2a'); ?></th>
                <td><?php echo esc_html($tc_detail['customer_ip']); ?></td>
            </tr>
            <tr>
                <th class="manage-column" width="100px"><?php _e('User Agent', 'nl2a'); ?></th>
                <td><?php echo esc_html($tc_detail['customer_user_agent']); ?></td>
            </tr>
            <tr>
                <th class="manage-column" width="100px"><?php _e('Date', 'nl2a'); ?></th>
                <td><?php echo esc_html($tc_detail['created_at']); ?></td>
            </tr>
            <tr>
                <th class="manage-column" width="100px"><?php _e('Products Viewed', 'nl2a'); ?></th>
                <td>
                    <?php 
                    $products_viewed = unserialize($tc_detail['products_viewed']);
                    if(!empty($products_viewed) && is_array($products_viewed)){
                        $args = array(
                            'posts_per_page' => -1,
                            'post_status'    => 'publish',
                            'post_type'      => 'product',
                            'post__in'       => $products_viewed,
                            'orderby'        => 'post__in',
                        );
                        $query = new WP_Query( $args );
                        if($query->have_posts()){
                            echo '<ul class="list_products_reviewed">';
                            while ( $query->have_posts() ) {
                                $query->the_post();
                                global $product;
                                ?>
                                <li>
                                    <div class="product_image">
                                        <a target="_blank" href="<?php echo esc_url( $product->get_permalink() ); ?>">
                                        <?php echo $product->get_image(); ?>
                                        </a>
                                    </div>
                                    <div class="product_title">
                                        <a class="" target="_blank" href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo wp_kses_post( $product->get_name() ); ?></a>
                                    </div>
                                </li>
                                <?php
                            }
                            echo '</ul>';
                            wp_reset_query();
                        }
                    }
                    
                    ?>
                </td>
            </tr>
        </table>
        <?php
        }
        wp_die();
    }
}
new NL2A_TCPR_Tables();
endif;