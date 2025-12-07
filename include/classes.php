<?php

class mf_sms
{
	var $post_type = __CLASS__;
	var $meta_prefix = "";
	var $message_type = 'sms';
	var $chars_double = array("|", "^", "€", "{", "}", "[", "~", "]", "\\");
	var $chars_limit_single = 0;
	var $chars_limit_multiple = 0;
	var $sms_limit = 0;
	var $sms_price = 0;

	function __construct()
	{
		$this->meta_prefix = $this->post_type.'_';

		switch(get_option('setting_sms_provider'))
		{
			case 'cellsynt':
				$this->chars_limit_single = 160;
				$this->chars_limit_multiple = 153;
				$this->sms_limit = 6;
				$this->sms_price = (date("Y-m-d") >= "2023-10-01" ? 0.6 : 0.5);
			break;

			case 'ip1sms':
				$this->chars_limit_single = 160;
				$this->chars_limit_multiple = 152;
				$this->sms_limit = 10;
				$this->sms_price = 0.59;
			break;

			case 'pixie':
				$this->chars_limit_single = 160;
				$this->chars_limit_multiple = 153;
				$this->sms_limit = floor(1000 / 153);
				$this->sms_price = 0.48;
			break;

			/*default:
				$this->chars_limit_single = $this->chars_limit_multiple = $this->sms_limit = $this->sms_price = 0;
			break;*/
		}
	}

	function strip_phone_no($data)
	{
		$exkludera = array("/", "-", " ");

		return str_replace($exkludera, "", $data['number']);
	}

	function count_sent($data = [])
	{
		global $wpdb;

		if(!isset($data['limit'])){		$data['limit'] = 0;}

		$query_limit = "";

		if($data['limit'])
		{
			$query_limit .= " LIMIT 0, ".esc_sql($data['limit']);
		}

		return $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM ".$wpdb->posts." WHERE post_type = %s".$query_limit, $this->post_type));
	}

	function has_correct_settings()
	{
		switch(get_option('setting_sms_provider'))
		{
			case 'cellsynt':
			case 'ip1sms':
				if(get_option('setting_sms_username') != '' && get_option('setting_sms_password') != '')
				{
					return true;
				}
			break;

			case 'cellsynt':
			case 'ip1sms':
			case 'pixie':
				if(get_option('setting_sms_password') != '')
				{
					return true;
				}
			break;
		}

		return false;
	}

	function shorten_sender_name($string)
	{
		if(strlen($string) > 11)
		{
			$string = substr($string, 0, 10).".";
		}

		return $string;
	}

	function get_from_for_select()
	{
		$setting_sms_senders = get_option('setting_sms_senders');

		$profile_phone = get_the_author_meta('profile_phone', get_current_user_id());

		if($profile_phone == '')
		{
			$profile_phone = get_user_meta(get_current_user_id(), 'meta_sms_phone', true);
		}

		$user_data = get_userdata(get_current_user_id());

		$arr_data = array(
			'' => "-- ".__("Choose Here", 'lang_sms')." --",
		);

		if($setting_sms_senders != '')
		{
			foreach(array_map('trim', explode(",", $setting_sms_senders)) as $sender)
			{
				if($sender != '')
				{
					$sender = $this->shorten_sender_name($sender);

					$arr_data[$sender] = $sender;
				}
			}
		}

		if($profile_phone != '')
		{
			$profile_phone = $this->shorten_sender_name($profile_phone);

			$arr_data[$profile_phone] = $profile_phone;
		}

		if($user_data->display_name != '')
		{
			$display_name = $this->shorten_sender_name($user_data->display_name);

			$arr_data[$display_name] = $display_name;
		}

		return $arr_data;
	}

	function calculate_amount($message)
	{
		$message_length = strlen($message);

		foreach($this->chars_double as $character)
		{
			$message_length += substr_count($message, $character);
		}

		if($message_length <= $this->chars_limit_single)
		{
			$sms_amount = 1;
		}

		else
		{
			$sms_amount = ceil($message_length / $this->chars_limit_multiple);
		}

		return $sms_amount;
	}

	function send_sms($data)
	{
		global $wpdb;

		$sent = false;
		$message = "";

		if(!isset($data['country_no'])){	$data['country_no'] = "46";}
		if(!isset($data['user_id'])){		$data['user_id'] = get_current_user_id();}

		if($data['to'] != '' && $data['text'] != '')
		{
			$data['to'] = $this->strip_phone_no(array('number' => $data['to']));
			$data['from'] = $this->strip_phone_no(array('number' => $data['from']));

			$setting_sms_provider = get_option('setting_sms_provider');
			$setting_sms_username = get_option('setting_sms_username');
			$setting_sms_password = get_option('setting_sms_password');

			$obj_encryption = new mf_encryption(__CLASS__);
			$setting_sms_password = $obj_encryption->decrypt($setting_sms_password, md5(AUTH_KEY));

			switch($setting_sms_provider)
			{
				case 'cellsynt':
					// MSISDN-format (ex. 0708123456 -> 0046708123456)
					if(!preg_match("/^(\+|00)/", $data['to']))
					{
						if(substr($data['to'], 0, 1) == 0)
						{
							$data['to'] = substr($data['to'], 1, 20);
						}
					}

					$data['to'] = "00".$data['country_no'].$data['to'];

					if($data['from'] != '')
					{
						if(is_numeric($data['from']))
						{
							$originatortype = 'numeric';

							if(!preg_match("/^(\+|00)/", $data['from']))
							{
								if(substr($data['from'], 0, 1) == 0)
								{
									$data['from'] = substr($data['from'], 1, 20);
								}
							}

							$data['from'] = "00".$data['country_no'].$data['from'];
						}

						else
						{
							$originatortype = 'alpha';

							$data['from'] = urlencode($data['from']);
						}
					}

					list($content, $headers) = get_url_content(array(
						'url' => "https://se-1.cellsynt.net/sms.php?username=".$setting_sms_username."&password=".$setting_sms_password."&destination=".$data['to']."&originatortype=".$originatortype."&originator=".$data['from']."&charset=UTF-8&text=".urlencode(html_entity_decode(html_entity_decode(stripslashes($data['text']))))."&allowconcat=6",
						'catch_head' => true,
					));

					if(substr($content, 0, 2) == "OK")
					{
						$trackingids = substr($content, 4);

						$post_data = array(
							'post_type' => $this->post_type,
							'post_name' => $data['from'],
							'post_title' => $data['to'],
							'post_content' => $data['text'],
							'post_author' => $data['user_id'],
							'meta_input' => apply_filters('filter_meta_input', array(
								$this->meta_prefix.'from' => $data['from'],
								$this->meta_prefix.'trackingids' => $trackingids,
								$this->meta_prefix.'amount' => (substr_count($trackingids, ",") + 1),
							)),
						);

						wp_insert_post($post_data);

						$sent = true;
					}

					else
					{
						do_log(__FUNCTION__." - ".$setting_sms_provider.": ".htmlspecialchars($content)." (".var_export($data, true).")");

						$message = htmlspecialchars($content);
					}
				break;

				case 'ip1sms':
					// E164-format (ex. 0708123456 -> 46708123456)
					if(!preg_match("/^(\+|00)/", $data['to']))
					{
						if(substr($data['to'], 0, 1) == 0)
						{
							$data['to'] = substr($data['to'], 1, 20);
						}
					}

					$data['to'] = $data['country_no'].$data['to'];

					if($data['from'] != '')
					{
						if(is_numeric($data['from']))
						{
							if(!preg_match("/^(\+|00)/", $data['from']))
							{
								if(substr($data['from'], 0, 1) == 0)
								{
									$data['from'] = substr($data['from'], 1, 20);
								}
							}

							$data['from'] = $data['country_no'].$data['from'];
						}

						else
						{
							$data['from'] = urlencode($data['from']);
						}
					}

					$arr_post_data = array(
						'From' => $data['from'],
						'Numbers' => array($data['to']),
						'Message' => $data['text'],
					);

					$post_data = json_encode($arr_post_data);

					list($content, $headers) = get_url_content(array(
						'url' => "https://api.ip1sms.com/api/sms/send",
						'catch_head' => true,
						'headers' => array(
							'Authorization: Basic '.base64_encode($setting_sms_username.":".$setting_sms_password),
							'Content-Type: application/json',
							'Content-Length: '.strlen($post_data),
						),
						'post_data' => $post_data,
					));

					switch($headers['http_code'])
					{
						case 200:
						case 201:
							$arr_json = json_decode($content, true);

							foreach($arr_json as $r)
							{
								//do_log(__FUNCTION__." - ".$setting_sms_provider." - JSON: ".var_export($r, true));

								/*{
									"ID": 65416,
									"BundleID": null,
									"Status": 0,
									"StatusDescription": "Delivered to gateway",
									"Prio": 1,
									"CountryCode": "string",
									"Currency": 752,
									"TotalPrice": 0.0,
									"Price": 0.0,
									"Encoding": 0,
									"Segments": 0,
									"From": "iP1sms",
									"To": "46123456789",
									"Message": "Hello world!"
								}*/

								$post_data = array(
									'post_type' => $this->post_type,
									'post_status' => $r['Status'],
									'post_name' => $data['from'],
									'post_title' => $data['to'],
									//'post_excerpt' => $r['ID'],
									'post_content' => $data['text'],
									'post_author' => $data['user_id'],
									'meta_input' => apply_filters('filter_meta_input', array(
										//$this->meta_prefix.'ID' => $r['ID'],
										$this->meta_prefix.'from' => $data['from'],
										$this->meta_prefix.'cost' => $r['TotalPrice'],
										$this->meta_prefix.'amount' => $r['Segments'],
									)),
								);

								wp_insert_post($post_data);
							}

							$sent = true;
						break;

						default:
							do_log(__FUNCTION__." - ".$setting_sms_provider.": ".$headers['http_code']." (".htmlspecialchars($content).", ".var_export($arr_post_data, true).")");

							$message = htmlspecialchars($content);
						break;
					}
				break;

				case 'pixie':
					// 10 requests / second

					// E164-format (ex. 0708123456 -> 46708123456)
					/*if(!preg_match("/^(\+|00)/", $data['to']))
					{
						if(substr($data['to'], 0, 1) == 0)
						{
							$data['to'] = substr($data['to'], 1, 20);
						}
					}

					$data['to'] = $data['country_no'].$data['to'];*/

					if($data['from'] != '')
					{
						if(is_numeric($data['from']))
						{
							$originatortype = 'numeric';

							if(!preg_match("/^(\+|00)/", $data['from']))
							{
								if(substr($data['from'], 0, 1) == 0)
								{
									$data['from'] = substr($data['from'], 1, 20);
								}
							}

							$data['from'] = "+".$data['country_no'].$data['from'];
						}

						else
						{
							$originatortype = 'alpha';

							$data['from'] = urlencode($data['from']);
						}
					}

					$api_key = $setting_sms_password;

					$arr_headers = [
						'Content-Type: application/json',
						'Authorization: '.$api_key,
					];

					$arr_post_data = [
						"sender" => $data['from'],
						"message" => $data['text'],
						"recipients" => array($data['to'])
					];

					list($content, $headers) = get_url_content(array(
						'url' => "https://app.pixie.se/api/v2/sms",
						'catch_head' => true,
						'headers' => $arr_headers,
						'post_data' => json_encode($arr_post_data),
					));

					switch($headers['http_code'])
					{
						case 200:
						case 201:
							$arr_json = json_decode($content, true);

							if(is_array($arr_json))
							{
								do_log(__FUNCTION__." - ".$setting_sms_provider." - JSON: ".var_export($arr_json, true));

								/*{
								  "id": 42,
								  "smsCount": 2,
								  "cost": 0.00234,
								  "rejected": {
									"invalid": [
									  {
										"number": "+46701740605",
										"reason": "invalid_number_format"
									  }
									],
									"optedOut": [
									  "+46701740605"
									]
								  }
								}*/

								$post_data = array(
									'post_type' => $this->post_type,
									//'post_status' => $arr_json['Status'],
									'post_name' => $data['from'],
									'post_title' => $data['to'],
									'post_content' => $data['text'],
									'post_author' => $data['user_id'],
									'meta_input' => apply_filters('filter_meta_input', array(
										$this->meta_prefix.'trackingids' => $arr_json['id'],
										$this->meta_prefix.'from' => $data['from'],
										$this->meta_prefix.'cost' => $arr_json['cost'],
										$this->meta_prefix.'amount' => $arr_json['smsCount'],
									)),
								);

								wp_insert_post($post_data);
							}

							else
							{
								do_log(__FUNCTION__." - ".$setting_sms_provider.": ".htmlspecialchars($content)." -> ".var_export($arr_json, true));
							}

							$sent = true;
						break;

						default:
							do_log(__FUNCTION__." - ".$setting_sms_provider.": ".$headers['http_code']." (".htmlspecialchars($content).", ".var_export($arr_post_data, true).")");

							$message = $content;
						break;
					}
				break;
			}
		}

		return array($sent, $message);
	}

	function cron_base()
	{
		global $wpdb;

		$obj_cron = new mf_cron();
		$obj_cron->start(__CLASS__);

		if($obj_cron->is_running == false)
		{
			replace_user_meta(array('old' => 'meta_sms_phone', 'new' => 'profile_phone'));

			// Get status
			##################################
			$setting_sms_provider = get_option('setting_sms_provider');
			$setting_sms_username = get_option('setting_sms_username');
			$setting_sms_password = get_option('setting_sms_password');

			$obj_encryption = new mf_encryption(__CLASS__);
			$setting_sms_password = $obj_encryption->decrypt($setting_sms_password, md5(AUTH_KEY));

			switch($setting_sms_provider)
			{
				case 'ip1sms':
					$arr_status = array(0, 10, 11, 21);

					$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_excerpt FROM ".$wpdb->posts." WHERE post_type = %s AND post_excerpt != '' AND post_status IN ('".implode("','", $arr_status)."')", $this->post_type));

					foreach($result as $r)
					{
						$intSmsID = $r->ID;
						$intSmsApiID = $r->post_excerpt;

						list($content, $headers) = get_url_content(array(
							'url' => "https://api.ip1sms.com/api/sms/sent/".$intSmsApiID,
							'catch_head' => true,
							'headers' => array(
								'Authorization: Basic '.base64_encode($setting_sms_username.":".$setting_sms_password),
								'Content-Type: application/json',
							),
						));

						switch($headers['http_code'])
						{
							case 200:
							case 201:
								$arr_json = json_decode($content, true);

								/*{
									"Created": "2019-05-21T17:53:36.3333017+00:00",
									"CreatedDate": "2019-05-21T17:53:36.3333017+00:00",
									"Modified": "2019-05-21T17:53:36.3333017+00:00",
									"UpdatedDate": "2019-05-21T17:53:36.3333017+00:00",
									"ID": 1,
									"BundleID": 1,
									"Status": 2,
									"StatusDescription": "Invalid message content",
									"To": "sample string 3",
									"CountryCode": null,
									"Currency": "SEK",
									"TotalPrice": 4.0,
									"Price": 5.0,
									"Encoding": "GSM7",
									"Segments": 1,
									"Prio": 2,
									"From": "sample string 7",
									"Message": "sample string 8"
								}*/

								$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_status = '%d' WHERE post_type = %s AND post_excerpt = %s", $arr_json['Status'], $this->post_type, $arr_json['ID']));

								return true;
							break;

							default:
								do_log(__FUNCTION__." - ".$setting_sms_provider.": ".$headers['http_code']." (".htmlspecialchars($content).")");

								return false;
							break;
						}
					}
				break;
			}
			##################################
		}

		$obj_cron->end();
	}

	function init()
	{
		load_plugin_textdomain('lang_sms', false, str_replace("/include", "", dirname(plugin_basename(__FILE__)))."/lang/");

		// Post types
		#######################
		register_post_type($this->post_type, array(
			'labels' => array(
				'name' => __("SMS", 'lang_sms'),
				'singular_name' => __("SMS", 'lang_sms'),
				'menu_name' => __("SMS", 'lang_sms'),
				'all_items' => __('List', 'lang_sms'),
				'edit_item' => __('Edit', 'lang_sms'),
				'view_item' => __('View', 'lang_sms'),
				'add_new_item' => __('Add New', 'lang_sms'),
			),
			'public' => false,
		));
		#######################
	}

	function settings_sms()
	{
		$options_area = __FUNCTION__;

		add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

		$arr_settings = array(
			'setting_sms_provider' => __("Provider", 'lang_sms'),
		);

		$setting_sms_provider = get_option('setting_sms_provider');

		if($setting_sms_provider != '')
		{
			switch($setting_sms_provider)
			{
				case 'cellsynt':
				case 'ip1sms':
					$arr_settings['setting_sms_username'] = __("Username", 'lang_sms');
					$arr_settings['setting_sms_password'] = __("Password", 'lang_sms')." / ".__("API Key", 'lang_sms');
				break;

				case 'pixie':
					$arr_settings['setting_sms_password'] = __("API Key", 'lang_sms');
				break;
			}

			$arr_settings['setting_sms_senders'] = __("Senders", 'lang_sms');
		}

		show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
	}

	function pre_update_option($new_value, $option_key, $old_value)
	{
		if($new_value != '')
		{
			switch($option_key)
			{
				case 'setting_sms_password':
					$obj_encryption = new mf_encryption(__CLASS__);
					$new_value = $obj_encryption->encrypt($new_value, md5(AUTH_KEY));
				break;
			}
		}

		return $new_value;
	}

	function settings_sms_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo settings_header($setting_key, __("SMS", 'lang_sms'));
	}

	function setting_sms_provider_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		$arr_data = array(
			'' => "-- ".__("Choose Here", 'lang_sms')." --",
			'cellsynt' => "Cellsynt",
			'ip1sms' => "IP.1",
			'pixie' => "Pixie",
		);

		switch($option)
		{
			case 'cellsynt':
				$description = sprintf(__("Use the URL %s for delivery reports", 'lang_sms'), plugin_dir_url(__FILE__)."sms_status.php");
			break;

			default:
				$description = "";
			break;
		}

		echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option, 'description' => $description));
	}

	function setting_sms_username_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'xtra' => " autocomplete='off'"));
	}

	function setting_sms_password_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		$obj_encryption = new mf_encryption(__CLASS__);
		$option = $obj_encryption->decrypt($option, md5(AUTH_KEY));

		echo show_password_field(array('name' => $setting_key, 'value' => $option, 'xtra' => " autocomplete='new-password'"));
	}

	function setting_sms_senders_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Company, 0046701234567", 'lang_sms'), 'description' => __("One or more numbers/names separated by comma, 1-11 characters each, is used for selecting which number/name is displayed to the recipient", 'lang_sms')));
	}

	function admin_init()
	{
		global $pagenow;

		if($pagenow == 'admin.php' && in_array(check_var('page'), array("mf_sms/list/index.php", "mf_group/send/index.php")))
		{
			$plugin_include_url = plugin_dir_url(__FILE__);

			mf_enqueue_script('script_sms', $plugin_include_url."script_wp.js", array(
				'admin_url' => admin_url("admin.php?page=mf_sms/list/index.php"),
				'plugin_url' => $plugin_include_url,
				'chars_limit_single' => $this->chars_limit_single,
				'chars_limit_multiple' => $this->chars_limit_multiple,
				'chars_double' => $this->chars_double,
				'sms_limit' => $this->sms_limit,
				'sms_price' => $this->sms_price,
			));
		}
	}

	function admin_menu()
	{
		$menu_root = 'mf_sms/';
		$menu_start = $menu_root.'list/index.php';
		$menu_capability = 'edit_pages';

		$menu_title = __("SMS", 'lang_sms');
		add_menu_page($menu_title, $menu_title, $menu_capability, $menu_start, '', 'dashicons-phone', 99);

		if($this->count_sent(array('limit' => 1)) > 0)
		{
			$menu_title = __("Statistics", 'lang_sms');
			$menu_capability = 'update_core';
			add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_root."stats/index.php");
		}

		if(IS_EDITOR)
		{
			$menu_title = __("Settings", 'lang_sms');
			add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, admin_url("options-general.php?page=settings_mf_base#settings_sms"));
		}
	}

	function get_group_message_type($type)
	{
		if($type == $this->message_type)
		{
			$type = __("SMS", 'lang_sms');
		}

		return $type;
	}

	function get_group_message_form_fields($data)
	{
		if($data['type'] == $this->message_type)
		{
			$data['html'] .= show_select(array('data' => $this->get_from_for_select(), 'name' => 'strMessageFrom', 'text' => __("From", 'lang_sms'), 'value' => $data['from_value'], 'required' => true))
			.show_select(array('data' => $data['to_select'], 'name' => 'arrGroupID[]', 'text' => __("To", 'lang_sms'), 'value' => $data['to_value'], 'maxsize' => 6, 'required' => true))
			.show_textarea(array('name' => 'strMessageText', 'text' => __("Message", 'lang_sms'), 'value' => $data['message'], 'required' => true, 'xtra' => " maxlength='".($this->chars_limit_multiple  * $this->sms_limit)."'"));
		}

		return $data;
	}

	function get_message_count_html($data)
	{
		$out = "&nbsp;<span id='sms_count'>".sprintf(__("%s SMS, approx. %s left", 'lang_sms'), "<span></span>", "<span></span>")."</span>";

		if($data['display_total'])
		{
			$out .= "&nbsp;<div id='sms_cost'>".sprintf(__("Totally %s SMS, approx. %s", 'lang_sms'), "<span></span>", "<span></span> SEK")."</div><br>";
		}

		return $out;
	}

	function get_group_message_send_fields($data)
	{
		if($data['type'] == $this->message_type)
		{
			$data['html'] .= $this->get_message_count_html(array('display_total' => true));
		}

		return $data;
	}

	function user_contactmethods($profile_fields)
	{
		$setting_users_add_profile_fields = get_option('setting_users_add_profile_fields');

		if(!is_array($setting_users_add_profile_fields) || !in_array('profile_phone', $setting_users_add_profile_fields))
		{
			$profile_fields['profile_phone'] = __("Phone Number", 'lang_sms');
		}

		return $profile_fields;
	}

	function add_group_list_amount_actions($arr_actions, $post_id)
	{
		do_action('load_font_awesome');

		if(count($this->get_from_for_select()) > 1)
		{
			$sms_link = admin_url("admin.php?page=mf_group/send/index.php&intGroupID=".$post_id."&type=sms");
			$sms_text = __("Send SMS to everyone in the group", 'lang_sms');
		}

		else
		{
			$profile_phone = get_the_author_meta('profile_phone', get_current_user_id());

			if($profile_phone == '')
			{
				$profile_phone = get_user_meta(get_current_user_id(), 'meta_sms_phone', true);
			}

			if($profile_phone == '')
			{
				$sms_link = admin_url("profile.php");
				$sms_text = __("You have not entered a cell phone number in your profile. Please do so, and then you can start sending messages", 'lang_sms');
			}

			else
			{
				$sms_link = admin_url("options-general.php?page=settings_mf_base#settings_sms");
				$sms_text = __("You have not entered any senders in the settings. Please do so, and then you can start sending messages", 'lang_sms');
			}
		}

		$arr_actions['send_sms'] = "<a href='".$sms_link."' title='".$sms_text."'><i class='fa fa-mobile-alt fa-lg'></i></a>";

		return $arr_actions;
	}

	function group_init_other()
	{
		// Do I really need this now when it is an action?

		//Must be here to make sure that send_sms() is loaded
		##################
		/*require_once(ABSPATH."wp-admin/includes/plugin.php");

		if(is_plugin_active("mf_sms/index.php"))
		{
			require_once(ABSPATH."wp-content/plugins/mf_sms/include/classes.php");
		}
		##################

		$obj_sms = new mf_sms();*/
	}

	function group_send_other($data)
	{
		list($sent, $message) = $this->send_sms(array('from' => $data['from'], 'to' => $data['to'], 'text' => $data['message'], 'user_id' => $data['user_id']));

		return $sent;
	}
}

if(class_exists('mf_list_table'))
{
	class mf_sms_table extends mf_list_table
	{
		function set_default()
		{
			$this->post_type = 'mf_sms';

			$this->orderby_default = "post_date";
			$this->orderby_default_order = "DESC";
		}

		function init_fetch()
		{
			global $wpdb, $obj_sms;

			$this->query_where .= ($this->query_where != '' ? " AND " : "")."post_author = '".esc_sql(get_current_user_id())."'";

			if($this->search != '')
			{
				$this->query_where .= ($this->query_where != '' ? " AND " : "")."(post_name LIKE '".$this->filter_search_before_like($this->search)."' OR post_title LIKE '".$this->filter_search_before_like($this->search)."' OR post_content LIKE '".$this->filter_search_before_like($this->search)."' OR SOUNDEX(post_content) = SOUNDEX('".$this->search."') OR post_date LIKE '".$this->filter_search_before_like($this->search)."')";
			}

			$this->set_views(array(
				'db_field' => 'post_status',
				'types' => array(
					'all' => __("All", 'lang_sms'),
					'trash' => __("Trash", 'lang_sms'),
				),
			));

			$this->set_columns(array(
				'cb' => '<input type="checkbox">',
				'post_status' => "",
				'post_name' => __("From", 'lang_sms'),
				'post_title' => __("To", 'lang_sms'),
				'post_content' => __("Message", 'lang_sms'),
				'post_date' => __("Date", 'lang_sms'),
			));

			$this->set_sortable_columns(array(
				'post_name',
				'post_title',
				'post_date',
			));
		}

		function column_default($item, $column_name)
		{
			global $obj_sms;

			$out = "";

			switch($column_name)
			{
				case 'post_status':
					switch($item['post_status'])
					{
						// Cellsynt
						case 'delivered':
						//IP.1
						case 22:
							$status_icon = "fa fa-check green";
						break;

						// Cellsynt
						case 'failed':
						//IP.1
						case 1:
						case 2:
						case 3:
						case 4:
						case 12:
						case 30:
						case 41:
						case 42:
						case 43:
						case 44:
						case 45:
						case 50:
						case 51:
						case 52:
						case 100:
						case 101:
						case 110:
							$status_icon = "fa fa-ban red";
						break;

						// Cellsynt
						case 'buffered':
						//IP.1
						case 0:
						case 10:
						case 11:
						case 21:
							$status_icon = "fa fa-cloud blue";
						break;

						default:
						// Cellsynt
						case 'unknown':
						case 'acked':
							$status_icon = "fa fa-question";
						break;
					}

					$amount_reported = get_post_meta($item['ID'], $obj_sms->meta_prefix.'amount', true);

					if($item['post_excerpt'] != '')
					{
						$trackingids = $item['post_excerpt'];
					}

					else
					{
						$trackingids = get_post_meta($item['ID'], $obj_sms->meta_prefix.'trackingids', true);
					}

					$amount_calculated = $obj_sms->calculate_amount($item['post_content']);

					if(!($amount_reported > 0) && strlen($trackingids) > 6)
					{
						$amount_reported = substr_count($trackingids, ",") + 1;
					}

					$arr_actions = [];

					$arr_actions['amount'] = "<span title='".sprintf(__("Calculated from %d characters", 'lang_sms'), strlen($item['post_content']))."'>".$amount_calculated."</span>";

					if($amount_reported > 0)
					{
						$arr_actions['amount'] .= " / <span title='".__("Reported from provider", 'lang_sms')." (".$trackingids.")'>".$amount_reported."</span>";
					}

					$out .= "<i class='".$status_icon."'></i>"
					.$this->row_actions($arr_actions);
				break;

				case 'post_name':
					$post_from = get_post_meta($item['ID'], $obj_sms->meta_prefix.'from', true);

					if($post_from != '')
					{
						$out .= $post_from;
					}

					else
					{
						$out .= $item['post_name'];
					}
				break;

				case 'post_content':
					$out .= $item['post_content'];
				break;

				case 'post_date':
					$out .= format_date($item['post_date']);
				break;

				default:
					if(isset($item[$column_name]))
					{
						$out .= $item[$column_name];
					}
				break;
			}

			return $out;
		}
	}
}