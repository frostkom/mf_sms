<?php

if(!defined('ABSPATH'))
{
	$folder = str_replace("/wp-content/plugins/mf_sms/include", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

require_once("functions.php");

$trackingid = check_var('trackingid', 'char');
$status = check_var('status', 'char');

$strDataIP = $_SERVER['REMOTE_ADDR'];

$arr_ips = array(
	"212.100.254.167",
	"37.250.191.29",
	"83.138.162.66",
	"83.138.162.68",
);

if(!in_array($strDataIP, $arr_ips))
{
	error_log("Wrong IP: ".$strDataIP.", ".$trackingid.", ".$status);

	header("Status: 503 Unknown IP-address");
}

else
{
	$wpdb->get_results("SELECT ID FROM ".$wpdb->posts." WHERE post_excerpt LIKE '%".esc_sql($trackingid)."%' LIMIT 0, 1");

	if($status != '' && $wpdb->num_rows > 0)
	{
		$wpdb->query("UPDATE ".$wpdb->posts." SET post_status = '".esc_sql($status)."' WHERE post_excerpt LIKE '%".esc_sql($trackingid)."%'");
	}

	header("Status: 200 OK");
}