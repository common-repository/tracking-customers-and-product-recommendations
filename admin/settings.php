<?php
defined('ABSPATH') or wp_die('Nope, not accessing this');
if(!class_exists('NL2A_TCPR_Menu_Settings')):
class NL2A_TCPR_Menu_Settings{
    public function __construct(){
        add_action( 'admin_menu', array( $this, 'menu' ) );
        add_filter('plugin_action_links', array($this, 'plugin_settings_link'), 10, 2 );
        add_action('admin_enqueue_scripts', array($this, 'register_style'));
    }

    public function plugin_settings_link($plugin_actions, $plugin_file) { 
        if($plugin_file === NL2A_TCPR_BASENAME.'/index.php'){
            $settings_link = sprintf('<a href="%s">%s</a>', add_query_arg( ['page' => 'nl2a_tcpr'], admin_url('admin.php')), 'Settings', 'nl2a' );
            array_unshift($plugin_actions, $settings_link); 
        }
        return $plugin_actions; 
    }

    public static $field_settings = [
        'nl2a-tcpr-save-customers'  =>  [
            'default'   =>  1,
        ],
        'nl2a-tcpr-cookie-expires'  =>  [
            'type' => 'integer', 
            'default' => 10,
        ],
        'nl2a-tcpr-recommendations-taxonomies'  =>  [
            'type'  =>  'array',
            'default'   =>  ['product_cat'],
        ],
        'nl2a-tcpr-recommendations-order'  =>  [
            'default'   =>  '',
        ],
        'nl2a-tcpr-products-viewed-page'  =>  [
            'default'   =>  '',
        ],
        'nl2a-tcpr-product-recommendations-page'  =>  [
            'default'   =>  '',
        ],
        'nl2a-tcpr-time-duration-orders'  =>  [
            'type' => 'integer', 
            'default' => 10,
        ],
    ];

    public function reset_settings($callable,  $int){
        if(isset($_POST['nl2a_tcpr_reset_settings'])){
            foreach(self::$field_settings as $k => $v){
                delete_option($k);
            }
        }
    }

    public function reset_settings_notification(){
        if(isset($_POST['nl2a_tcpr_reset_settings'])){
            add_settings_error('nl2a_tcpr_reset_settings', 'nl2a_tcpr_reset_settings', __('Your settings has been changed default setting.', 'nl2a'), 'updated');
        }
    }


    public function register_settings(){
        foreach(self::$field_settings as $k => $v){
            register_setting('nl2a-tcpr-settings-group', $k, $v);
        }
    }

    public function register_style(){
        $current_screen = get_current_screen();
        if(isset($current_screen) && ($current_screen->base == 'product-recommendations_page_nl2a_tcpr_tc' || $current_screen->base == 'de-xuat-san-pham_page_nl2a_tcpr_tc' || $current_screen->base == 'toplevel_page_nl2a_tcpr')){
            wp_enqueue_script('nl2a-tcpr-select2', NL2A_TCPR_URL.'/admin/assets/js/select2.min.js');
            wp_enqueue_script('nl2a-tcpr-datetimepicker', NL2A_TCPR_URL.'/admin/assets/js/jquery.datetimepicker.full.min.js');
            wp_enqueue_script('nl2a-tcpr-tc', NL2A_TCPR_URL.'/admin/assets/js/nl2a-tcpr-tc.js');
            wp_enqueue_style('nl2a-tcpr-select2', NL2A_TCPR_URL.'/admin/assets/css/select2.min.css');
            wp_enqueue_style('nl2a-tcpr-datetimepicker', NL2A_TCPR_URL.'/admin/assets/css/jquery.datetimepicker.min.css');

            wp_localize_script( 'nl2a-tcpr-tc', 'nl2a_tc',
                array( 
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'nl2a_tcpr_nonce'   =>  wp_create_nonce('nl2a-nonce')
                ) 
            );
        }
    }

    public function menu(){
        add_menu_page(
            __( 'Product Recommendations', 'nl2a' ),
            __( 'Product Recommendations', 'nl2a' ),
            'manage_options',
            'nl2a_tcpr',
            array($this, 'page_settings'),
            'dashicons-awards'
        );
        add_submenu_page( 'nl2a_tcpr', __('Settings', 'nl2a'), __('Settings', 'nl2a'), 'manage_options', 'nl2a_tcpr', array($this, 'page_settings'));
        add_action( 'admin_init', array($this, 'register_settings') );
        add_submenu_page( 'nl2a_tcpr', __('Tracking customers', 'nl2a'), __('Tracking customers', 'nl2a'), 'manage_options', 'nl2a_tcpr_tc', array($this, 'page_tracking_customers'));
    }

    public function page_settings(){
        if( isset( $_GET[ 'tab' ] ) ) {
            $active_tab = esc_attr($_GET[ 'tab' ]);
        }else{
            $active_tab = 'settings';
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Settings', 'nl2a'); ?></h1>
            <?php settings_errors(); ?>
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo add_query_arg( ['page' => 'nl2a_tcpr', 'tab' => 'settings'], admin_url('admin.php')); ?>" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>"><?php _e('Settings', 'nl2a'); ?></a>
                <a href="<?php echo add_query_arg( ['page' => 'nl2a_tcpr', 'tab' => 'shortcode'], admin_url('admin.php')); ?>" class="nav-tab <?php echo $active_tab == 'shortcode' ? 'nav-tab-active' : ''; ?>"><?php _e('Shortcode', 'nl2a'); ?></a>
            </h2>
            <?php switch($active_tab):
            case 'settings':
                ?>
                <form action="options.php" method="post">
                    <?php settings_fields( 'nl2a-tcpr-settings-group' ); ?>
                    <?php do_settings_sections( 'nl2a-tcpr-settings-group' ); ?>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row"><?php _e('Save customers', 'nl2a'); ?></th>
                            <td>
                                <input type="checkbox" value="1" <?php checked(esc_attr( get_option('nl2a-tcpr-save-customers') ), 1); ?> name="nl2a-tcpr-save-customers" id="nl2a-tcpr-save-customers">
                                <p class="description"><?php _e('Save customers who have viewed the product to database', 'nl2a'); ?></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php _e('Cookie expires', 'nl2a'); ?></th>
                            <td>
                                <input class="regular-text" type="number" min="0" name="nl2a-tcpr-cookie-expires" id="nl2a-tcpr-cookie-expires" value="<?php echo esc_attr( get_option('nl2a-tcpr-cookie-expires')); ?>">
                                <p class="description"><?php _e('Sets the cookie retention period for products viewed by customers. Unit: day(s). If this parameter is omitted or set to 0, the cookie will expire at the end of the session (when the browser closes).', 'nl2a'); ?></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php _e('Recommendations type', 'nl2a'); ?></th>
                            <td>
                                <?php
                                $product_taxonomies = get_object_taxonomies( 'product', 'objects' );
                                $recommendations_taxonomies = get_option('nl2a-tcpr-recommendations-taxonomies');
                                foreach($product_taxonomies as $tax){
                                    if($tax->public == true && $tax->name !== 'product_shipping_class'){
                                ?>
                                <p>
                                    <input type="checkbox" name="nl2a-tcpr-recommendations-taxonomies[]" <?php if(!empty($recommendations_taxonomies) && in_array($tax->name, $recommendations_taxonomies)){ echo 'checked'; } ?> value="<?php echo $tax->name; ?>" id="nl2a-tcpr-recommendations-taxonomies-<?php echo $tax->name; ?>"> <label for="nl2a-tcpr-recommendations-taxonomies-<?php echo $tax->name; ?>"><?php echo $tax->label; ?></label>
                                </p>
                                <?php 
                                    }
                                } 
                                ?>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php _e('Purchased products', 'nl2a'); ?></th>
                            <td>
                                <input type="checkbox" value="1" <?php checked(esc_attr( get_option('nl2a-tcpr-recommendations-order') ), 1); ?> name="nl2a-tcpr-recommendations-order" id="nl2a-tcpr-recommendations-order">
                                <p class="description"><?php _e('Product recommendations from products that a customer has purchased', 'nl2a'); ?></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php _e('Time duration orders', 'nl2a'); ?></th>
                            <td>
                                <input class="regular-text" type="number" min="5" name="nl2a-tcpr-time-duration-orders" id="nl2a-tcpr-time-duration-orders" value="<?php echo esc_attr( get_option('nl2a-tcpr-time-duration-orders')); ?>">
                                <p class="description"><?php _e('The time of the customer order compared to the current date. This is used to apply product recommendations to customers. Unit: day(s).', 'nl2a'); ?></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php _e('Products viewed page', 'nl2a'); ?></th>
                            <td>
                                <select class="nl2a-tcpr-search-page regular-text" name="nl2a-tcpr-products-viewed-page" id="nl2a-tcpr-products-viewed-page">
                                    <option value="0"><?php _e('Search page', 'nl2a') ?></option>
                                    <?php
                                    $products_viewed_page = esc_attr( get_option('nl2a-tcpr-products-viewed-page'));
                                    if($products_viewed_page && is_numeric($products_viewed_page)):
                                        $page_info = get_post($products_viewed_page);
                                    ?>
                                    <option value="<?php echo $page_info->ID; ?>" selected ><?php echo $page_info->post_title;  ?></option>
                                    <?php endif; ?>
                                </select>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php _e('Product recommendations page', 'nl2a'); ?></th>
                            <td>
                                <select class="nl2a-tcpr-search-page regular-text" name="nl2a-tcpr-product-recommendations-page" id="nl2a-tcpr-product-recommendations-page">
                                    <option value="0"><?php _e('Search page', 'nl2a') ?></option>
                                    <?php
                                    $product_recommendations_page = esc_attr( get_option('nl2a-tcpr-product-recommendations-page'));
                                    if($product_recommendations_page && is_numeric($product_recommendations_page)):
                                        $page_info = get_post($product_recommendations_page);
                                    ?>
                                    <option value="<?php echo $page_info->ID; ?>" selected ><?php echo $page_info->post_title;  ?></option>
                                    <?php endif; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
                <?php
                break;
            case 'shortcode':
                ?>
                <table class="form-table">
                    <body>
                        <tr valign="top">
                            <th scope="row"><?php _e('Products viewed', 'nl2a'); ?></th>
                            <td>
                                <input type="text" name="" id="copy_shortcode_products_viewed" class="w-300" readonly value="<?php echo '[nl2a_tcpr_products_viewed]'; ?>">
                                <label onclick="copyFunction(this)" data-title-copy="<?php _e('Copy to clipboard', 'nl2a'); ?>" data-title-copied="<?php _e('Copied', 'nl2a'); ?>" for="copy_shortcode_products_viewed" class="button tooltip">
                                    <?php _e('Copy', 'nl2a'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('Show all products that customers have viewed', 'nl2a'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php _e('Product recommendations', 'nl2a'); ?></th>
                            <td>
                                <input type="text" name="" id="copy_shortcode_product_recommendations" class="w-300" readonly value="<?php echo '[nl2a_tcpr]'; ?>">
                                <label onclick="copyFunction(this)" data-title-copy="<?php _e('Copy to clipboard', 'nl2a'); ?>" data-title-copied="<?php _e('Copied', 'nl2a'); ?>" for="copy_shortcode_product_recommendations" class="button tooltip">
                                    <?php _e('Copy', 'nl2a'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('Show all recommended products for customers', 'nl2a'); ?>
                                </p>
                            </td>
                        </tr>
                    </body>
                </table>
                <style type="text/css">
                    .w-300{
                        width: 300px;
                        max-width: 100%;
                    }
                    .tooltip {
                        position: relative;
                        display: inline-block;
                    }

                    .tooltip::before {
                        content: attr(data-title-copy);
                        visibility: hidden;
                        background-color: #555;
                        color: #fff;
                        text-align: center;
                        border-radius: 6px;
                        padding: 5px 10px;
                        position: absolute;
                        z-index: 1;
                        bottom: 150%;
                        left: 50%;
                        opacity: 0;
                        transform: translate(-50%, 0);
                    }

                    .tooltip::after {
                        content: "";
                        position: absolute;
                        top: -50%;
                        left: 50%;
                        margin-left: -5px;
                        border-width: 5px;
                        border-style: solid;
                        border-color: #555 transparent transparent transparent;
                        opacity: 0;
                    }

                    .tooltip:hover::before,
                    .tooltip:hover::after {
                        visibility: visible;
                        opacity: 1;
                    }
                    .tooltip:active::before{
                        content: attr(data-title-copied);
                    }
                </style>
                <script type="text/javascript">
                    function copyFunction(e) {
                        var copyText = document.getElementById(e.getAttribute("for"));
                        copyText.select();
                        copyText.setSelectionRange(0, 99999);
                        document.execCommand("copy");
                    }
                </script>
                <?php
                break;
            endswitch; ?>
            
        </div>
        <?php
    }

    public function page_tracking_customers(){
        if(isset($_GET['paged']) && is_numeric($_GET['paged'])){
            $paged = $_GET['paged'];
        }else{
            $paged = 1;
        }
        $args = [];

        if(isset($_GET['created_at']) && is_array($_GET['created_at']) && !empty(array_filter($_GET['created_at'])) && $created_at = $_GET['created_at']){
            if(isset($created_at['from'])){
                $args['custom_meta'][] = [
                    'key'   =>  'created_at',
                    'value' =>  $created_at['from'],
                    'type'  =>  'date',
                    'compare'   =>  '>='
                ];
            }
            if(isset($created_at['to'])){
                $args['custom_meta'][] = [
                    'key'   =>  'created_at',
                    'value' =>  $created_at['to'],
                    'type'  =>  'date',
                    'compare'   =>  '<='
                ];
            }
        }
        if(isset($_GET['customer_ip']) && !empty($_GET['customer_ip']) && $customer_ip = $_GET['customer_ip']){
            $args['customer_ip'] = [
                'value' =>  $customer_ip
            ];
        }
        if(isset($_GET['customer_id']) && !empty($_GET['customer_id']) && $customer_id = $_GET['customer_id']){
            $args['customer_id'] = [
                'value' =>  $customer_id,
                'type'  =>  'numeric'
            ];
        }
        
        $total = count(NL2A_TCPR_Tables::results_page($args, 0));
        $items = NL2A_TCPR_Tables::results_page($args, $paged);
        $max_page = ceil($total/30);

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Tracking customer', 'nl2a'); ?></h1>
            <hr class="wp-header-end">
            <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <select name="bulkactions" id="bulk-action-selector-top">
                            <option value="-1"><?php _e('Bulk actions', 'nl2a'); ?></option>
                            <option value="delete" class="hide-if-no-js"><?php _e('Delete', 'nl2a'); ?></option>
                        </select>
                        <input type="submit" id="tcdoaction" class="button action" value="Apply">
                    </div>
                    <form action="<?php echo admin_url('admin.php'); ?>">
                        <input type="hidden" name="page" value="nl2a_tcpr_tc">
                        <div class="alignleft actions">
                            <select class="regular-text" name="customer_id" id="filter-by-customer-id">
                                <option value="0"><?php _e('Search customer', 'nl2a') ?></option>
                                <?php if(isset($_GET['customer_id']) && is_numeric($_GET['customer_id']) && $current_customer_id = esc_attr($_GET['customer_id'])){
                                    $current_customer = get_user_by('id', $current_customer_id);
                                    if($current_customer){
                                        echo '<option selected="selected" value="'.$current_customer->ID.'">'.$current_customer->display_name.'('.$current_customer->user_email.')</option>';
                                    }
                                } ?>
                            </select>
                            <label for="created_at"><?php _e('Date', 'nl2a'); ?></label>
                            <input autocomplete="off" placeholder="<?php _e('Date from', 'nl2a'); ?>" type="text" name="created_at[from]" id="filter-by-created-from" value="<?php echo isset($_GET['created_at']['from'])?esc_attr($_GET['created_at']['from']):''; ?>">
                            <input type="text" autocomplete="off" placeholder="<?php _e('Date to', 'nl2a'); ?>" name="created_at[to]" id="filter-by-created-to" value="<?php echo isset($_GET['created_at']['to'])?esc_attr($_GET['created_at']['to']):''; ?>">
                            <label for="customer_ip"><?php _e('Ip', 'nl2a'); ?></label>
                            <input type="text" autocomplete="off" placeholder="<?php _e('Enter customer ip', 'nl2a'); ?>" name="customer_ip" id="customer_ip" value="<?php echo isset($_GET['customer_ip'])?esc_attr($_GET['customer_ip']):''; ?>">
                            <input type="submit" id="search-submit" class="button" value="<?php _e( 'Filter', 'nl2a' ); ?>">
                            <a class="button" href="<?php echo add_query_arg( ['page'=>'nl2a_tcpr_tc'], admin_url('admin.php') ); ?>"><?php _e('Reset', 'nl2a'); ?></a>
                        </div>
                    </form>
                    <div class="tablenav-pages">
                        <?php nl2a_tcpr_paging($paged, $max_page); ?>
                    </div>
                
            </div>
            <table class="wp-list-table widefat fixed striped table-view-list tracking-customer">
                <thead>
                    <tr>
                        <td class="check-column"><input id="cb-select-all-1" type="checkbox"></td>
                        <th class="manage-column" width="300px"><?php _e('Email', 'nl2a'); ?></th>
                        <th class="manage-column" width="200px"><?php _e('IP', 'nl2a'); ?></th>
                        <th class="manage-column"><?php _e('User Agent', 'nl2a'); ?></th>
                        <th class="manage-column" width="150px"><?php _e('Date', 'nl2a'); ?></th>
                        <th class="manage-column center" width="50px"><?php _e('View', 'nl2a'); ?></th>
                    </tr>
                </thead>
                <body>
                    <?php if($items): ?>
                        <?php foreach($items as $item): ?>
                            <tr >
                                <th class="check-column"><input id="cb-select-<?php echo $item['id']; ?>" type="checkbox" name="tc[]" value="<?php echo esc_html($item['id']); ?>"></th>
                                <td class="manage-column" width="300px"><?php echo esc_html($item['customer_email']); ?></td>
                                <td class="manage-column" width="200px"><?php echo esc_html($item['customer_ip']); ?></td>
                                <td class="manage-column"><?php echo esc_html($item['customer_user_agent']); ?></td>
                                <td class="manage-column" width="150px"><?php echo esc_html($item['created_at']); ?></td>
                                <td class="manage-column center" width="50px"><span class="dashicons dashicons-visibility view_detail" data-id="<?php echo $item['id']; ?>"></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </body>
            </table>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php nl2a_tcpr_paging($paged, $max_page); ?>
                </div>
            </div>
        </div>
        <div id="tcModal" class="modal">
            <div class="modal-content">
                <span class="close"><span class="dashicons dashicons-no-alt"></span></span>
                <div class="content">
                    <div class="center">
                        <div class="loader"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <style type="text/css">
        .list_products_reviewed > li{
            display: -ms-flexbox;
            display: flex;
            -ms-flex-wrap: wrap;
            flex-wrap: wrap;
        }
        .list_products_reviewed > li:last-child{
            margin-bottom: 0;
        }
        .product_image{
            -ms-flex: 0 0 100px;
            flex: 0 0 100px;
            max-width: 100px;
            padding-right: 10px;
            box-sizing: border-box;
        }
        .product_image img{
            max-width: 100%;
            height: auto;
        }
        .product_title{
            -ms-flex: 0 0 calc(100% - 100px);
            flex: 0 0 calc(100% - 100px);
            max-width: calc(100% - 100px);
            box-sizing: border-box;
        }
        .tracking-customer .center,
        #tcModal .center{
            text-align: center;
        }
        .tracking-customer .view_detail{
            cursor: pointer;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            padding-top: 100px;
            padding-bottom: 100px;
            left: 0;
            top: 0;
            bottom: 0;
            width: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4); 
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 100%;
            max-width: 600px;
            position: relative;
        }

        .close {
            color: #aaaaaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            top: 0;
            right: 0;
        }

        .close:hover,
        .close:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }
        .loader {
            border:5px solid #333;
            border-radius: 50%;
            border-top: 5px solid #ddd;
            width: 40px;
            height: 40px;
            -webkit-animation: spin 2s linear infinite;
            animation: spin 2s linear infinite;
            display: inline-block;
        }
        @-webkit-keyframes spin {
            0% { -webkit-transform: rotate(0deg); }
            100% { -webkit-transform: rotate(360deg); }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        </style>
        <?php
    }
}
new NL2A_TCPR_Menu_Settings();
endif;