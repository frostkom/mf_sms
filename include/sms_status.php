<?php

$wp_root = '../../../..';

if(file_exists($wp_root.'/wp-load.php'))
{
	require_once($wp_root.'/wp-load.php');
}

else
{
	require_once($wp_root.'/wp-config.php');
}

require_once("functions.php");

$trackingid = check_var('trackingid', 'char');
$status = check_var('status', 'char');

$strDataIP = $_SERVER['REMOTE_ADDR'];

$arr_ips = array(
	"212.100.254.167",
	"37.250.191.29",
	"83.138.162.66",
);

if(!in_array($strDataIP, $arr_ips))
{
	do_log("Wrong IP: ".$strDataIP.", ".$trackingid.", ".$status);

	header("Status: 503 Unknown IP-address");
}

else
{
	$wpdb->get_results("SELECT ID FROM ".$wpdb->posts." WHERE post_excerpt LIKE '%".esc_sql($trackingid)."%'");

	if($status != '' && $wpdb->num_rows > 0)
	{
		$wpdb->query("UPDATE ".$wpdb->posts." SET post_status = '".esc_sql($status)."' WHERE post_excerpt LIKE '%".esc_sql($trackingid)."%'");
	}

	header("Status: 200 OK");
}