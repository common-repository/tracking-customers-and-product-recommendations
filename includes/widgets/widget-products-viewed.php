<?php
defined('ABSPATH') or wp_die('Nope, not accessing this');
class NL2A_Widget_Products_Viewed extends WP_Widget {

	public function __construct() {
        $widget_ops = array( 
			'classname' => 'widget_nl2a_products_viewed',
			'description' => __( "Displays a list of viewed products.", 'nl2a' ),
		);
		parent::__construct( 'nl2a_products_viewed', __( 'NL2A Products viewed', 'nl2a' ), $widget_ops );

	}

	public function widget( $args, $instance ) {
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
            extract( $args );
            $title = apply_filters( 'widget_title', $instance['title'] );
    
            echo $before_widget;
            if ( ! empty( $title ) ) {
                echo $before_title . $title . $after_title;
            }
            $query_args = array(
                'posts_per_page' => $instance['number'],
                'no_found_rows'  => 1,
                'post_status'    => 'publish',
                'post_type'      => 'product',
                'post__in'       => $products_viewed,
                'orderby'        => 'post__in',
            );
            $query = new WP_Query( $query_args );

            if($query->have_posts()){
                switch($instance['style']){
                    case 1:
                        echo '<ul class="nl2a_widget_products_list">';

                        while ( $query->have_posts() ) {
                            $query->the_post();
                            global $product;
                            ?>
                            <div class="widget-product-item">
                                <div class="image-item float-left">
                                    <a href="<?php echo esc_url( $product->get_permalink() ); ?>">
                                    <?php echo $product->get_image(); ?>
                                    </a>
                                </div>
                                <div class="des-item">
                                    <div class="product-name">
                                        <h4 class="product-item-name">
                                            <a class="" href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo wp_kses_post( $product->get_name() ); ?></a>
                                        </h4>
                                    </div>
                                    <div class="product-price price">
                                    <?php echo $product->get_price_html(); ?>
                                    </div>
                                </div>
                            </div>
                        <?php
                        }
                        wp_reset_query();
                        $products_viewed_page = esc_attr( get_option('nl2a-tcpr-products-viewed-page'));
                        if($products_viewed_page){
                            printf('<div class="widget-product-item"><a href="%s">%s</a></div>', get_permalink($products_viewed_page), 'View all', 'nl2a');
                        }
                        echo '</ul>';
                        break;
                    case 2:
                        echo wp_kses_post( apply_filters( 'woocommerce_before_widget_product_list', '<ul class="product_list_widget">' ) );

                        while ( $query->have_posts() ) {
                            $query->the_post();
                            wc_get_template( 'content-widget-product.php' );
                        }

                        echo wp_kses_post( apply_filters( 'woocommerce_after_widget_product_list', '</ul>' ) );
                        break;
                }
            }

            echo $after_widget;
        }

        $content = ob_get_clean();

		echo $content;

	}

    public function form( $instance ) {
		
        $default = array(
            'title' =>  __('Products viewed', 'nl2a'),
            'style' =>  1,
            'number'    =>  4
        );
        $instance = wp_parse_args( (array) $instance, $default);

        ?>
<p>
    <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'nl2a' ); ?></label>
    <input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>">
</p>
<p>
    <label for="<?php echo esc_attr( $this->get_field_id( 'style' ) ); ?>"><?php esc_attr_e( 'Display style:', 'nl2a' ); ?></label>
    <select name="<?php echo esc_attr( $this->get_field_name( 'style' ) ); ?>" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'style' ) ); ?>">
        <option value="1" <?php selected( esc_attr( $instance['style'] ), 1 ); ?>><?php _e('Default', 'nl2a'); ?></option>
        <option value="2" <?php selected( esc_attr( $instance['style'] ), 2 ); ?>><?php _e('Woocommerce widget', 'nl2a'); ?></option>
    </select>
</p>
<p>
    <label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>"><?php esc_attr_e( 'Number of products to show:', 'nl2a' ); ?></label>
    <input type="number" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>" value="<?php echo esc_attr( $instance['number'] ); ?>">
</p>
        <?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['style'] = ( ! empty( $new_instance['style'] ) ) ? sanitize_text_field( $new_instance['style'] ) : '';
		$instance['number'] = ( ! empty( $new_instance['number'] ) ) ? sanitize_text_field( $new_instance['number'] ) : '';
		return $instance;
	}
}

function NL2A_Widget_Products_Viewed() {
    register_widget( 'NL2A_Widget_Products_Viewed' );
}
add_action( 'widgets_init', 'NL2A_Widget_Products_Viewed' );