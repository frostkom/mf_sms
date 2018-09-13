<?php

if(!defined('ABSPATH'))
{
	$folder = str_replace("/wp-content/plugins/mf_sms/include", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

include_once("classes.php");

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
	do_log("Wrong IP: ".$strDataIP.", ".$trackingid.", ".$status);

	header("Status: 503 Unknown IP-address");
}

else
{
	$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_excerpt LIKE %s LIMIT 0, 1", "%".$trackingid."%"));

	if($status != '' && $wpdb->num_rows > 0)
	{
		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_status = %s WHERE post_excerpt LIKE %s", esc_sql($status), "%".$trackingid."%"));

		if($wpdb->rows_affected == 1)
		{
			header("Status: 200 OK");
		}

		else
		{
			do_log("There were no trackingIDs that matched (".var_export($_REQUEST, true).", ".$wpdb->last_query.")");

			header("Status: 500 Internal Server Error");
		}
	}

	else
	{
		do_log("There were no trackingIDs that matched (".var_export($_REQUEST, true).", ".$wpdb->last_query.")");

		header("Status: 500 Internal Server Error");
	}
}