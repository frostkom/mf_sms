<?php

class mf_sms
{
	function __construct()
	{
		$this->post_type = 'mf_sms';
		$this->message_type = 'sms';
	}

	function strip_phone_no($data)
	{
		$exkludera = array("/", "-", " ");

		return str_replace($exkludera, "", $data['number']);
	}

	function count_sent($data = array())
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
		}

		return false;
	}

	function get_from_for_select()
	{
		$setting_sms_senders = get_option('setting_sms_senders');
		$setting_sms_phone = get_user_meta(get_current_user_id(), 'meta_sms_phone', true);

		$arr_data = array(
			'' => "-- ".__("Choose Here", 'lang_sms')." --",
		);

		if(is_array($setting_sms_senders))
		{
			foreach(explode(",", $setting_sms_senders) as $sender)
			{
				if($sender != '')
				{
					$arr_data[$sender] = $sender;
				}
			}
		}

		if($setting_sms_phone != '')
		{
			$arr_data[$setting_sms_phone] = $setting_sms_phone;
		}

		return $arr_data;
	}

	function send_sms($data)
	{
		global $wpdb;

		if(!isset($data['country_no'])){	$data['country_no'] = "46";}
		if(!isset($data['user_id'])){		$data['user_id'] = get_current_user_id();}

		if($data['to'] != '' && $data['text'] != '')
		{
			$data['to'] = $this->strip_phone_no(array('number' => $data['to']));
			$data['from'] = $this->strip_phone_no(array('number' => $data['from']));

			$setting_sms_provider = get_option('setting_sms_provider');
			$setting_sms_username = get_option('setting_sms_username');
			$setting_sms_password = get_option('setting_sms_password');

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
							$originatortype = "numeric";

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
							$originatortype = "alpha";

							$data['from'] = urlencode($data['from']);
						}
					}

					$url = "https://se-1.cellsynt.net/sms.php?username=".$setting_sms_username."&password=".$setting_sms_password."&destination=".$data['to']."&originatortype=".$originatortype."&originator=".$data['from']."&charset=UTF-8&text=".urlencode(html_entity_decode(html_entity_decode(stripslashes($data['text']))))."&allowconcat=6";

					list($content, $headers) = get_url_content(array('url' => $url, 'catch_head' => true));

					if(substr($content, 0, 2) == "OK")
					{
						$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->posts." SET post_type = %s, post_title = %s, post_name = %s, post_content = %s, post_author = '%d', post_excerpt = %s, post_date = NOW()", $this->post_type, $data['to'], $data['from'], $data['text'], $data['user_id'], substr($content, 4)));

						return true;
					}

					else
					{
						do_log("Error while sending SMS through Cellsynt: ".htmlspecialchars($content));

						return false;
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

					$url = "https://api.ip1sms.com/api/sms/send";

					$arr_post_data = array(
						'From' => $data['from'],
						'Numbers' => array($data['to']),
						'Message' => $data['text'],
					);

					$post_data = json_encode($arr_post_data);

					list($content, $headers) = get_url_content(array(
						'url' => $url,
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
							$json = json_decode($content, true);

							foreach($json as $r)
							{
								//do_log("JSON Row: ".var_export($r, true));

								/*{
								   "ID": 65416,
								   "BundleID": null,
								   "Status": 0,
								   "StatusDescription": "Delivered to gateway",
								   "Prio": 1,
								   "From": "iP1sms",
								   "To": "46123456789",
								   "Message": "Hello world!"
								}*/

								$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->posts." SET post_type = %s, post_title = %s, post_name = %s, post_content = %s, post_author = '%d', post_excerpt = %s, post_status = '%d', post_date = NOW()", $this->post_type, $data['to'], $data['from'], $data['text'], $data['user_id'], $r['ID'], $r['Status']));
							}

							return true;
						break;

						default:
							do_log("Error while sending SMS through IP1SMS: ".$headers['http_code']." (".htmlspecialchars($content).", ".var_export($arr_post_data, true).")");

							return false;
						break;
					}
				break;
			}

			return false;
		}
	}

	function init()
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

		register_post_type($this->post_type, $args);
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
					$arr_settings['setting_sms_password'] = __("Password")." / ".__("API Key", 'lang_sms');
				break;
			}

			$arr_settings['setting_sms_senders'] = __("Senders", 'lang_sms');
		}

		show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
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

		echo show_password_field(array('name' => $setting_key, 'value' => $option, 'xtra' => " autocomplete='off'"));
	}

	function setting_sms_senders_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => __("Company,0046701234567", 'lang_sms'), 'description' => __("One or more numbers/names separated by comma, is used for selecting which number/name is displayed to the recipient", 'lang_sms')));
	}

	function admin_init()
	{
		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		mf_enqueue_script('script_sms', $plugin_include_url."script_wp.js", array('admin_url' => admin_url("admin.php?page=mf_sms/list/index.php"), 'plugin_url' => $plugin_include_url), $plugin_version);
	}

	function admin_menu()
	{
		$menu_root = 'mf_sms/';
		$menu_start = $menu_root.'list/index.php';
		$menu_capability = override_capability(array('page' => $menu_start, 'default' => 'edit_pages'));

		$menu_title = __("SMS", 'lang_sms');
		add_menu_page($menu_title, $menu_title, $menu_capability, $menu_start, '', 'dashicons-phone', 99);

		if($this->count_sent(array('limit' => 1)) > 0)
		{
			$menu_capability = override_capability(array('page' => $menu_start, 'default' => 'update_core'));

			$menu_title = __("Statistics", 'lang_sms');
			add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_root."stats/index.php");
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

	/*function get_from_for_select()
	{
		$sms_senders = get_option('setting_sms_senders');
		$sms_phone = get_user_meta(get_current_user_id(), 'meta_sms_phone', true);

		$arr_data = array(
			'' => "-- ".__("Choose Here", 'lang_sms')." --"
		);

		foreach(explode(",", $sms_senders) as $sender)
		{
			if($sender != '')
			{
				$arr_data[$sender] = $sender;
			}
		}

		if($sms_phone != '')
		{
			$arr_data[$sms_phone] = $sms_phone;
		}

		return $arr_data;
	}*/

	function get_group_message_form_fields($data)
	{
		if($data['type'] == $this->message_type)
		{
			$data['html'] .= show_select(array('data' => $this->get_from_for_select(), 'name' => 'strMessageFrom', 'text' => __("From", 'lang_sms'), 'value' => $data['from_value'], 'required' => true))
			.show_select(array('data' => $data['to_select'], 'name' => 'arrGroupID[]', 'text' => __("To", 'lang_sms'), 'value' => $data['to_value'], 'maxsize' => 6, 'required' => true))
			.show_textarea(array('name' => 'strMessageText', 'text' => __("Message", 'lang_sms'), 'value' => $data['message'], 'required' => true));
		}

		return $data;
	}

	function get_group_message_send_fields($data)
	{
		if($data['type'] == $this->message_type)
		{
			$data['html'] .= " <span id='chars_left'></span> (<span id='sms_amount'>1</span>)";
		}

		return $data;
	}

	function user_contactmethods($profile_fields)
	{
		$profile_fields['meta_sms_phone'] = __("Phone number", 'lang_sms');

		return $profile_fields;
	}

	function add_group_list_amount_actions($actions, $post_id)
	{
		$actions['send_sms'] = "<a href='".admin_url("admin.php?page=mf_group/send/index.php&intGroupID=".$post_id."&type=sms")."' title='".__("Send SMS to everyone in the group", 'lang_sms')."'><i class='fa fa-mobile-alt fa-lg'></i></a>";

		return $actions;
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
		return $this->send_sms(array('from' => $data['from'], 'to' => $data['to'], 'text' => $data['message'], 'user_id' => $data['user_id']));
	}
}