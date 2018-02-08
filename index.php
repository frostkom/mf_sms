<?php
/*
Plugin Name: MF SMS
Plugin URI: https://github.com/frostkom/mf_sms
Description: 
Version: 2.3.18
Licence: GPLv2 or later
Author: Martin Fors
Author URI: http://frostkom.se
Text Domain: lang_sms
Domain Path: /lang

Depends: MF Base
GitHub Plugin URI: frostkom/mf_sms
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
}

function uninstall_sms()
{
	mf_uninstall_plugin(array(
		'options' => array('setting_sms_url', 'setting_sms_username', 'setting_sms_password', 'setting_sms_senders'),
		'post_types' => array('mf_sms'),
	));
}