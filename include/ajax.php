<?php

if(!defined('ABSPATH'))
{
	header('Content-Type: application/json');

	$folder = str_replace("/wp-content/plugins/mf_sms/include", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

require_once("functions.php");

$json_output = array();

$type = check_var('type', 'char');
$arr_input = explode("/", $type);
$type_action = $arr_input[0];

if($type_action == "sms_send" && get_current_user_id() > 0)
{
	$strSmsFrom = check_var('strSmsFrom');
	$strSmsTo = check_var('strSmsTo');
	$strSmsText = check_var('strSmsText');

	$sent = send_sms(array('from' => $strSmsFrom, 'to' => $strSmsTo, 'text' => $strSmsText));

	if($sent == "OK")
	{
		$json_output['success'] = true;
	}
}

else if($type_action == "sms")
{
	if($arr_input[1] == "search")
	{
		$strSearch = check_var('s', 'char');

		$arr_exclude = array("/", "-", "");
		$strSearch = str_replace($arr_exclude, "", $strSearch);

		if(is_plugin_active("mf_address/index.php"))
		{
			$result = $wpdb->get_results("SELECT addressCellNo FROM ".$wpdb->base_prefix."address WHERE addressCellNo != '' AND (addressFirstName LIKE '%".$strSearch."%' OR addressSurName LIKE '%".$strSearch."%' OR CONCAT(addressFirstName, ' ', addressSurName) LIKE '%".$strSearch."%' OR REPLACE(REPLACE(REPLACE(addressCellNo, '/', ''), '-', ''), ' ', '') LIKE '%".$strSearch."%') GROUP BY addressCellNo ORDER BY addressSurName ASC, addressFirstName ASC");

			foreach($result as $r)
			{
				$json_output[] = $r->addressCellNo;
			}
		}

		$result = $wpdb->get_results("SELECT post_title FROM ".$wpdb->posts." WHERE post_type = 'mf_sms' AND post_title LIKE '%".$strSearch."%' GROUP BY post_title ORDER BY post_date DESC");

		foreach($result as $r)
		{
			if(!in_array($r->post_title, $json_output))
			{
				$json_output[] = $r->post_title;
			}
		}

		$json_output['amount'] = count($json_output);
		$json_output['success'] = true;
	}
}

echo json_encode($json_output);