<?php

if(!defined('ABSPATH'))
{
	$folder = str_replace("/wp-content/plugins/mf_sms/include", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

include_once("classes.php");

$trackingid = check_var('trackingid', 'char');
$status = check_var('status', 'char');

$strDataIP = get_current_visitor_ip();

$arr_ips = array();

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

	$post_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." LEFT JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE post_type = %s AND (post_excerpt LIKE %s OR (meta_key = %s AND meta_value LIKE %s)) LIMIT 0, 1", $obj_sms->post_type, "%".$trackingid."%", $obj_sms->meta_prefix.'trackingids', "%".$trackingid."%"));

	if($post_id > 0)
	{
		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_status = %s WHERE ID = '%d'", $status, $post_id));

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