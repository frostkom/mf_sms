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

echo json_encode($json_output);