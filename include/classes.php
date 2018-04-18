<?php

class mf_sms
{
	function __construct()
	{

	}

	function admin_init()
	{
		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		mf_enqueue_script('script_sms', $plugin_include_url."script_wp.js", array('admin_url' => admin_url("admin.php?page=mf_sms/list/index.php"), 'plugin_url' => $plugin_include_url), $plugin_version);
	}
}