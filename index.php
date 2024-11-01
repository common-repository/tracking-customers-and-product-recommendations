<?php
/*
Plugin Name: Tracking customers and product recommendations
Plugin URI: 
Description: Track and list products that customers have viewed or purchased, then show recommended products to customers.
Author: Nam Nguyen
Author URI: https://www.facebook.com/banam.nguyen.50/
Version: 1.0
Plugin path: languages
*/
defined('ABSPATH') or wp_die('Nope, not accessing this');

define( 'NL2A_TCPR_PATH', plugin_dir_path( __FILE__ ) );
define( 'NL2A_TCPR_URL', plugin_dir_url( __FILE__ ) );
define( 'NL2A_TCPR_BASENAME', plugin_basename(NL2A_TCPR_PATH) );

function nl2a_tcpr_is_active_woocommerce(){
	try{
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if(is_plugin_active( 'woocommerce/woocommerce.php' )){
			return true;
		}
	}catch(Exception $e){
		return false;
	}
	return false;
}

function nl2a_tcpr_include_file_in_directory($directory, $extension = 'php'){
	if(!empty($directory)){
		$files = glob(NL2A_TCPR_PATH.$directory.'*.'.$extension);
		if(isset($files) && !empty($files)){
			foreach ($files as $file) {
				include $file;
			}
		}
	}
}
if(nl2a_tcpr_is_active_woocommerce()){
	include_once NL2A_TCPR_PATH.'/functions.php';
    nl2a_tcpr_include_file_in_directory('/includes/core/');
    nl2a_tcpr_include_file_in_directory('/admin/');
    nl2a_tcpr_include_file_in_directory('/includes/widgets/');
    nl2a_tcpr_include_file_in_directory('/includes/shortcodes/');
}
