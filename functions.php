<?php
function nl2a_tcpr_frontend_register_style(){
    wp_enqueue_style('nl2a-tcpr-style', NL2A_TCPR_URL.'/assets/css/nl2a-tcpr.css');
}
add_action('wp_enqueue_scripts', 'nl2a_tcpr_frontend_register_style');

function nl2a_tcpr_plugin_text_domain() {
    load_plugin_textdomain( 'nl2a', false, NL2A_TCPR_BASENAME. '/languages' ); 
}
add_action( 'plugins_loaded', 'nl2a_tcpr_plugin_text_domain' );