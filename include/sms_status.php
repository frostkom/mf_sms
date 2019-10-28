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
	/*"212.100.254.167",
	"37.250.191.29",
	"83.138.162.66",
	"83.138.162.68",*/
);

for($i = 64; $i <= 71; $i++)
{
	$arr_ips[] = "83.138.162.".$i;
}

for($i = 144; $i <= 151; $i++)
{
	$arr_ips[] = "159.135.143.".$i;
}

if(!in_array($strDataIP, $arr_ips))
{
	do_log("Wrong IP: ".$strDataIP.", ".$trackingid.", ".$status);

	header("Status: 503 Unknown IP-address");
}

else if($trackingid == '' || $status == '')
{
	do_log("No trackingIDs or status attached (".var_export($_REQUEST, true).")");

	header("Status: 400 Bad Request");
}

else
{
	$obj_sms = new mf_sms();

	$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = %s AND post_excerpt LIKE %s LIMIT 0, 1", $obj_sms->post_type, "%".$trackingid."%"));

	if($wpdb->num_rows > 0)
	{
		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_status = %s WHERE post_type = %s AND post_excerpt LIKE %s", $status, $obj_sms->post_type, "%".$trackingid."%"));

		if($wpdb->rows_affected == 1)
		{
			header("Status: 200 OK");
		}

		else
		{
			do_log("No trackingIDs were updated (".var_export($_REQUEST, true).", ".$wpdb->last_query.")");

			header("Status: 500 Internal Server Error");
		}
	}

	else
	{
		do_log("There were no trackingIDs that matched (".var_export($_REQUEST, true).", ".$wpdb->last_query.")");

		header("Status: 500 Internal Server Error");
	}
}