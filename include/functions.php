<?php

function sms_is_active()
{
	if(get_option('setting_sms_url') != '' && get_option('setting_sms_username') != '' && get_option('setting_sms_password') != '')
	{
		return true;
	}

	else
	{
		return false;
	}
}

function init_sms()
{
	$labels = array(
		'name' => _x(__("SMS", 'lang_sms'), 'post type general name'),
		'singular_name' => _x(__("SMS", 'lang_sms'), 'post type singular name'),
		'menu_name' => __("SMS", 'lang_sms')
	);

	$args = array(
		'labels' => $labels,
		'public' => false,
		'exclude_from_search' => true,
	);

	register_post_type('mf_sms', $args);
}

function menu_sms()
{
	global $wpdb;

	$menu_root = 'mf_sms/';
	$menu_start = $menu_root.'list/index.php';
	$menu_capability = "edit_pages";

	if(current_user_can($menu_capability))
	{
		mf_enqueue_script('script_sms', plugin_dir_url(__FILE__)."script_wp.js", array('plugin_url' => plugin_dir_url(__FILE__)), get_plugin_version(__FILE__));
	}

	$menu_title = __("SMS", 'lang_sms');
	add_menu_page($menu_title, $menu_title, $menu_capability, $menu_start, '', 'dashicons-phone');

	$menu_capability = "update_core";

	$menu_title = __("Statistics", 'lang_sms');
	add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_root."stats/index.php");
}

function contactmethods_sms($profile_fields)
{
	$profile_fields['mf_sms_phone'] = __("Phone number", 'lang_sms');

	return $profile_fields;
}

function settings_sms()
{
	$options_area = __FUNCTION__;

	add_settings_section($options_area, "", $options_area."_callback", BASE_OPTIONS_PAGE);

	$arr_settings = array(
		'setting_sms_url' => __("URL", 'lang_sms'),
		'setting_sms_username' => __("Username", 'lang_sms'),
		'setting_sms_password' => __("Password", 'lang_sms'),
		'setting_sms_senders' => __("Senders", 'lang_sms'),
	);

	show_settings_fields(array('area' => $options_area, 'settings' => $arr_settings));
}

function settings_sms_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);

	echo settings_header($setting_key, __("SMS", 'lang_sms'));
}

function setting_sms_url_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option));
}

function setting_sms_username_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option));
}

function setting_sms_password_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_password_field(array('name' => $setting_key, 'value' => $option));
}

function setting_sms_senders_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Company,0046701234567", 'lang_sms'), 'description' => __("One or more numbers/names separated by comma, is used for selecting which number/name is displayed to the recipient", 'lang_sms')));
}

if(!function_exists('strip_phone_no'))
{
	function strip_phone_no($data)
	{
		$exkludera = array("/", "-", " ");

		return str_replace($exkludera, "", $data['number']);
	}
}

if(!function_exists('send_sms'))
{
	function send_sms($data)
	{
		global $wpdb;

		if(!isset($data['country_no'])){	$data['country_no'] = "0046";}
		if(!isset($data['user_id'])){		$data['user_id'] = get_current_user_id();}

		if($data['to'] != '' && $data['text'] != '')
		{
			$data['to'] = strip_phone_no(array('number' => $data['to']));
			$data['from'] = strip_phone_no(array('number' => $data['from']));

			// Mottagare av sms på MSISDN-format (ex. 0708123456 blir 0046708123456)
			#########################
			if(!preg_match("/^(\+|00)/", $data['to']))
			{
				if(substr($data['to'], 0, 1) == 0)
				{
					$data['to'] = substr($data['to'], 1, 20);
				}

				/*else
				{
					$data['to'] = substr($data['to'], 0, 20);
				}*/
			}

			$data['to'] = $data['country_no'].$data['to'];
			###############################

			if($data['from'] != '')
			{
				if(is_numeric($data['from']))
				{
					// NUMERISK AVSÄNDARE
					#########################
					// Ett telefonnummer kommer stå som avsändare på smset. + läggs alltid till före numret. Dvs om du vill att 0708123456 skall stå som avsändare, ange 46708123456 så kommer numret formateras om till +46708123456
					$originatortype = "numeric";

					if(!preg_match("/^(\+|00)/", $data['from']))
					{
						if(substr($data['from'], 0, 1) == 0)
						{
							$data['from'] = substr($data['from'], 1, 20);
						}

						/*else
						{
							$data['from'] = substr($data['from'], 0, 20);
						}*/
					}

					$data['from'] = $data['country_no'].$data['from'];
					###############################
				}

				else
				{
					// ALFANUMERISK AVSÄNDARE
					// En text kommer stå som avsänadare
					$originatortype = "alpha";

					$data['from'] = urlencode($data['from']);
				}
			}

			$sms_url = get_option('setting_sms_url');
			$sms_username = get_option('setting_sms_username');
			$sms_password = get_option('setting_sms_password');

			$result = get_url_content($sms_url."?username=".$sms_username."&password=".$sms_password."&destination=".$data['to']."&originatortype=".$originatortype."&originator=".$data['from']."&charset=UTF-8&text=".urlencode(html_entity_decode(html_entity_decode(stripslashes($data['text']))))."&allowconcat=6");

			if(substr($result, 0, 2) == "OK")
			{
				$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->posts." SET post_type = 'mf_sms', post_title = %s, post_name = %s, post_content = %s, post_author = '%d', post_excerpt = %s, post_date = NOW()", $data['to'], $data['from'], $data['text'], $data['user_id'], substr($result, 4)));

				$return_text = "OK";
			}

			else
			{
				$return_text = $result;
			}

			return $return_text;
		}
	}
}