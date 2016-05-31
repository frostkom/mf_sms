<?php
/*
Plugin Name: MF SMS
Plugin URI: 
Description: 
Version: 1.5.8
Author: Martin Fors
Author URI: http://frostkom.se
Text Domain: lang_sms
Domain Path: /lang
*/

if(is_admin())
{
	include_once("include/functions.php");

	register_uninstall_hook(__FILE__, 'uninstall_sms');

	add_action('init', 'init_sms');
	add_action('admin_menu', 'menu_sms');
	add_action('admin_init', 'settings_sms');
	add_filter('user_contactmethods', 'contactmethods_sms');

	load_plugin_textdomain('lang_sms', false, dirname(plugin_basename(__FILE__)).'/lang/');

	function uninstall_sms()
	{
		mf_uninstall_plugin(array(
			'options' => array('mf_sms_url', 'mf_sms_username', 'mf_sms_password', 'mf_sms_senders'),
		));
	}
}