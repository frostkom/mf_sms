<?php
/*
Plugin Name: MF SMS
Plugin URI: https://github.com/frostkom/mf_sms
Description: 
Version: 2.6.13
Licence: GPLv2 or later
Author: Martin Fors
Author URI: https://frostkom.se
Text Domain: lang_sms
Domain Path: /lang

Depends: MF Base
GitHub Plugin URI: frostkom/mf_sms

API Documentation: https://www.cellsynt.com/sv/sms/api-integration || https://www.ip1sms.com/en/manuals/restful/
*/

if(function_exists('is_plugin_active') && is_plugin_active("mf_base/index.php"))
{
	include_once("include/classes.php");

	load_plugin_textdomain('lang_sms', false, dirname(plugin_basename(__FILE__))."/lang/");

	$obj_sms = new mf_sms();

	add_action('cron_base', array($obj_sms, 'cron_base'), mt_rand(1, 10));

	if(is_admin())
	{
		register_uninstall_hook(__FILE__, 'uninstall_sms');

		add_action('init', array($obj_sms, 'init'));

		add_action('admin_init', array($obj_sms, 'settings_sms'));
		add_action('admin_init', array($obj_sms, 'admin_init'), 0);
		add_action('admin_menu', array($obj_sms, 'admin_menu'));

		add_filter('get_group_message_type', array($obj_sms, 'get_group_message_type'));
		add_filter('get_group_message_form_fields', array($obj_sms, 'get_group_message_form_fields'));
		add_filter('get_group_message_send_fields', array($obj_sms, 'get_group_message_send_fields'));

		add_filter('user_contactmethods', array($obj_sms, 'user_contactmethods'));
		add_filter('add_group_list_amount_actions', array($obj_sms, 'add_group_list_amount_actions'), 10, 2);
	}

	add_action('group_init_other', array($obj_sms, 'group_init_other'));
	add_filter('group_send_other', array($obj_sms, 'group_send_other'));

	function uninstall_sms()
	{
		mf_uninstall_plugin(array(
			'options' => array('setting_sms_provider', 'setting_sms_username', 'setting_sms_password', 'setting_sms_senders'),
			'meta' => array('meta_sms_phone'),
			'post_types' => array('mf_sms'),
		));
	}
}