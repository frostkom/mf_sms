<?php

$obj_sms = new mf_sms();

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

			if($obj_sms->has_correct_settings())
			{
				$arr_data_from = $obj_sms->get_from_for_select();

				if(count($arr_data_from) > 1)
				{
					echo "<form action='#' method='post' id='mf_sms' class='mf_form mf_settings'>"
						.show_select(array('data' => $arr_data_from, 'name' => 'strSmsFrom', 'text' => __("From", 'lang_sms'), 'value' => "", 'required' => true, 'description' => __("Add more", 'lang_sms').": <a href='".admin_url("profile.php#meta_sms_phone")."'>".__("Profile", 'lang_sms')."</a> ".__("or", 'lang_sms')." <a href='".admin_url("options-general.php?page=settings_mf_base#settings_sms")."'>".__("Settings", 'lang_sms')."</a>"))
						.show_textfield(array('name' => 'strSmsTo', 'text' => __("To", 'lang_sms'), 'value' => $strSmsTo, 'required' => true, 'placeholder' => "0046701234567"))
						.show_textarea(array('name' => 'strSmsText', 'text' => __("Message", 'lang_sms'), 'value' => "", 'required' => true, 'xtra' => " maxlength='".($obj_sms->chars_limit_multiple * $obj_sms->sms_limit)."'"))
						.show_button(array('name' => 'btnGroupSend', 'text' => __("Send", 'lang_sms')))
						.$obj_sms->get_message_count_html(array('display_total' => false))
					."</form>";
				}

				else
				{
					echo sprintf(__("You have to %sadd your phone number in the profile%s or %sadd a sender on the settings page%s for this to work", 'lang_sms'), "<a href='".admin_url("profile.php#meta_sms_phone")."'>", "</a>", "<a href='".admin_url("options-general.php?page=settings_mf_base#settings_sms")."'>", "</a>");
				}
			}

			else
			{
				echo sprintf(__("You have to %sadd Provider and credentials on the settings page%s for this to work", 'lang_sms'), "<a href='".admin_url("options-general.php?page=settings_mf_base#settings_sms")."'>", "</a>");
			}

		echo "</div>
	</div>";

	/*if($_SERVER['REMOTE_ADDR'] == "")
	{*/
		$tbl_group = new mf_sms_table();

		$tbl_group->select_data(array(
			//'select' => "*",
			'debug' => ($_SERVER['REMOTE_ADDR'] == ""),
		));

		$tbl_group->do_display();
	/*}

	else
	{
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

						echo "<tr>
							<td><i class='".$status_icon."'></i></td>
							<td>".$post_name."</td>
							<td>".$post_title."</td>
							<td>".$post_content."</td>
							<td>".format_date($post_date)."</td>
						</tr>";
					}

				echo "</tbody>
			</table>";
		}
	}*/

echo "</div>";