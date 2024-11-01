<?php
defined('ABSPATH') or wp_die('Nope, not accessing this');
class NL2A_Widget_Product_Recommendations extends WP_Widget {

	public function __construct() {
        $widget_ops = array( 
			'classname' => 'widget_nl2a_product_recommendations',
			'description' => __( "Displays a list of recommended products.", 'nl2a' ),
		);
		parent::__construct( 'nl2a_product_recommendations', __( 'NL2A Product Recommendations', 'nl2a' ), $widget_ops );

	}

	public function widget( $args, $instance ) {
        $product_recommendations = nl2a_tcpr_get_product_recommendations();
        ob_start();

        if(!empty($product_recommendations)){
            extract( $args );
            $title = apply_filters( 'widget_title', $instance['title'] );
    
            echo $before_widget;
            if ( ! empty( $title ) ) {
                echo $before_title . $title . $after_title;
            }
            $query_args = array(
                'posts_per_page' => $instance['number'],
                'post_status'    => 'publish',
                'post_type'      => 'product',
                'post__in'       => $product_recommendations,
                'order'        => $instance['order'],
            );
            if($instance['orderby'] == 'sales'){
                $query_args['orderby'] = 'meta_value_num';
                $query_args['meta_key'] = '_sale_price';
            }else{
                $query_args['orderby'] = $instance['orderby'];
            }
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
                            $product_recommendations_page = esc_attr( get_option('nl2a-tcpr-product-recommendations-page'));
                            if($product_recommendations_page){
                                printf('<div class="widget-product-item"><a href="%s">%s</a></div>', get_permalink($product_recommendations_page), 'View all', 'nl2a');
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
            'title' =>  __('Product recommendations', 'nl2a'),
            'style' =>  1,
            'orderby'   =>  'date',
            'order'   =>  'desc',
            'number'    =>  4
        );
        $instance = wp_parse_args( (array) $instance, $default);
        $orderby_list = [
            'date'  =>  __('Date', 'nl2a'),
            'price'  =>  __('Price', 'nl2a'),
            'rand'  =>  __('Random', 'nl2a'),
            'sales'  =>  __('Sales', 'nl2a'),
        ];
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
    <label for="<?php echo esc_attr( $this->get_field_id( 'orderby' ) ); ?>"><?php esc_attr_e( 'Order by:', 'nl2a' ); ?></label>
    <select name="<?php echo esc_attr( $this->get_field_name( 'orderby' ) ); ?>" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'orderby' ) ); ?>">
        <?php foreach($orderby_list as $k=>$v){
        ?>
        <option value="<?php echo $k ?>" <?php selected( esc_attr( $instance['orderby'] ), $k ); ?>><?php echo $v; ?></option>
        <?php
        }?>
    </select>
</p>
<p>
    <label for="<?php echo esc_attr( $this->get_field_id( 'order' ) ); ?>"><?php esc_attr_e( 'Order:', 'nl2a' ); ?></label>
    <select name="<?php echo esc_attr( $this->get_field_name( 'order' ) ); ?>" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'order' ) ); ?>">
        <option value="desc" <?php selected( esc_attr( $instance['order'] ), 'desc' ); ?>><?php _e('DESC', 'nl2a'); ?></option>
        <option value="asc" <?php selected( esc_attr( $instance['order'] ), 'asc' ); ?>><?php _e('ASC', 'nl2a'); ?></option>
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
		$instance['orderby'] = ( ! empty( $new_instance['orderby'] ) ) ? sanitize_text_field( $new_instance['orderby'] ) : '';
		$instance['order'] = ( ! empty( $new_instance['order'] ) ) ? sanitize_text_field( $new_instance['order'] ) : '';
		$instance['number'] = ( ! empty( $new_instance['number'] ) ) ? sanitize_text_field( $new_instance['number'] ) : '';
		return $instance;
	}
}

function NL2A_Widget_Product_Recommendations() {
    register_widget( 'NL2A_Widget_Product_Recommendations' );
}
add_action( 'widgets_init', 'NL2A_Widget_Product_Recommendations' );