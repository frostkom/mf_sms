<?php
/*
Plugin Name: MF SMS
Plugin URI: https://github.com/frostkom/mf_sms
Description: 
Version: 2.4.9
Licence: GPLv2 or later
Author: Martin Fors
Author URI: http://frostkom.se
Text Domain: lang_sms
Domain Path: /lang

Depends: MF Base
GitHub Plugin URI: frostkom/mf_sms
*/

add_action('cron_base', 'activate_sms', mt_rand(1, 10));

if(is_admin())
{
	include_once("include/classes.php");
	include_once("include/functions.php");

	$obj_sms = new mf_sms();

	register_activation_hook(__FILE__, 'activate_sms');
	register_uninstall_hook(__FILE__, 'uninstall_sms');

	add_action('init', 'init_sms');

	add_action('admin_init', 'settings_sms');
	add_action('admin_init', array($obj_sms, 'admin_init'), 0);
	add_action('admin_menu', 'menu_sms');

	add_filter('user_contactmethods', 'contactmethods_sms');

	load_plugin_textdomain('lang_sms', false, dirname(plugin_basename(__FILE__)).'/lang/');
}

function activate_sms()
{
	replace_user_meta(array('old' => 'mf_sms_phone', 'new' => 'meta_sms_phone'));
}

function uninstall_sms()
{
	mf_uninstall_plugin(array(
		'options' => array('setting_sms_url', 'setting_sms_username', 'setting_sms_password', 'setting_sms_senders'),
		'meta' => array('meta_sms_phone'),
		'post_types' => array('mf_sms'),
	));
}