<?php

$strSmsTo = check_var('strSmsTo');

echo "<div class='wrap'>
	<h2>".__("SMS", 'lang_sms')."</h2>
	<div class='error hide'>
		<p>
			<strong>".__("The message was not sent, contact an admin if this persists", 'lang_sms')."</strong>
		</p>
	</div>
	<div class='updated hide'>
		<p>
			<strong>".__("The message was sent", 'lang_sms')."</strong>
		</p>
	</div>
	<div id='poststuff' class='postbox'>
		<h3 class='hndle'>".__("Send SMS", 'lang_sms')."</h3>
		<div class='inside'>";

			$setting_sms_provider = get_option('setting_sms_provider');
			$setting_sms_url = get_option('setting_sms_url');
			$setting_sms_username = get_option('setting_sms_username');
			$setting_sms_password = get_option('setting_sms_password');
			$setting_sms_senders = get_option('setting_sms_senders');
			$setting_sms_phone = get_user_meta(get_current_user_id(), 'meta_sms_phone', true);

			if(($setting_sms_provider != '' || $setting_sms_url != '') && $setting_sms_username != '' && $setting_sms_password != '' && ($setting_sms_senders != '' || $setting_sms_phone != ''))
			{
				echo "<form action='#' method='post' id='mf_sms' class='mf_form mf_settings'>";

					$arr_data = array(
						'' => "-- ".__("Choose Here", 'lang_sms')." --",
					);

					foreach(explode(",", $setting_sms_senders) as $sender)
					{
						if($sender != '')
						{
							$arr_data[$sender] = $sender;
						}
					}

					if($setting_sms_phone != '')
					{
						$arr_data[$setting_sms_phone] = $setting_sms_phone;
					}

					echo show_select(array('data' => $arr_data, 'name' => 'strSmsFrom', 'text' => __("From", 'lang_sms'), 'value' => "", 'required' => true, 'description' => __("Add more", 'lang_sms').": <a href='".admin_url("profile.php#meta_sms_phone")."'>".__("Profile", 'lang_sms')."</a> ".__("or", 'lang_sms')." <a href='".admin_url("options-general.php?page=settings_mf_base#settings_sms")."'>".__("Settings", 'lang_sms')."</a>"))
					.show_textfield(array('name' => "strSmsTo", 'text' => __("To", 'lang_sms'), 'value' => $strSmsTo, 'required' => true, 'placeholder' => "0046701234567"))
					.show_textarea(array('name' => "strSmsText", 'text' => __("Message", 'lang_sms'), 'value' => "", 'required' => true))
					.show_button(array('name' => "btnGroupSend", 'text' => __("Send", 'lang_sms')))
					."<span id='chars_left'></span> (<span id='sms_amount'>1</span>)
				</form>";
			}

			else
			{
				echo __("You have to", 'lang_sms')." <a href='".admin_url("profile.php#meta_sms_phone")."'>".__("Add your phone number in the profile", 'lang_sms')."</a> ".__("or", 'lang_sms')." <a href='".admin_url("options-general.php?page=settings_mf_base#settings_sms")."'>".__("Add URL, Username & Password in the settings page", 'lang_sms')."</a> ".__("for this to work", 'lang_sms');
			}

		echo "</div>
	</div>";

	$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_name, post_title, post_content, post_date, post_status FROM ".$wpdb->posts." WHERE post_type = 'mf_sms' AND post_author = '%d' ORDER BY post_date DESC", get_current_user_id()));

	if($wpdb->num_rows > 0)
	{
		echo "<table class='widefat striped'>";

			$arr_header[] = "";
			$arr_header[] = __("From", 'lang_sms');
			$arr_header[] = __("To", 'lang_sms');
			$arr_header[] = __("Message", 'lang_sms');
			$arr_header[] = __("Date", 'lang_sms');

			echo show_table_header($arr_header)
			."<tbody>";

				foreach($result as $r)
				{
					$post_id = $r->ID;
					$post_name = $r->post_name;
					$post_title = $r->post_title;
					$post_content = $r->post_content;
					$post_date = $r->post_date;
					$post_status = $r->post_status;

					switch($post_status)
					{
						case 'delivered':
							$status_icon = "fa-check green";
						break;

						case 'failed':
							$status_icon = "fa-ban red";
						break;

						case 'buffered':
							$status_icon = "fa-cloud blue";
						break;

						default:
						case 'unknown':
						case 'acked':
							$status_icon = "fa-question";
						break;
					}

					echo "<tr>
						<td><i class='fa ".$status_icon."'></i></td>
						<td>".$post_name."</td>
						<td>".$post_title."</td>
						<td>".$post_content."</td>
						<td>".format_date($post_date)."</td>
					</tr>";
				}

			echo "</tbody>
		</table>";
	}

echo "</div>";