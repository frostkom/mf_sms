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
						.show_select(array('data' => $arr_data_from, 'name' => 'strSmsFrom', 'text' => __("From", 'lang_sms'), 'value' => "", 'required' => true, 'description' => __("Add more", 'lang_sms').": <a href='".admin_url("profile.php#profile_phone")."'>".__("Profile", 'lang_sms')."</a> ".__("or", 'lang_sms')." <a href='".admin_url("options-general.php?page=settings_mf_base#settings_sms")."'>".__("Settings", 'lang_sms')."</a>"))
						.show_textfield(array('name' => 'strSmsTo', 'text' => __("To", 'lang_sms'), 'value' => $strSmsTo, 'required' => true, 'placeholder' => "0046701234567"))
						.show_textarea(array('name' => 'strSmsText', 'text' => __("Message", 'lang_sms'), 'value' => "", 'required' => true, 'xtra' => " maxlength='".($obj_sms->chars_limit_multiple * $obj_sms->sms_limit)."'"))
						.show_button(array('name' => 'btnGroupSend', 'text' => __("Send", 'lang_sms')))
						.$obj_sms->get_message_count_html(array('display_total' => false))
					."</form>";
				}

				else
				{
					echo sprintf(__("You have to %sadd your phone number in the profile%s or %sadd a sender on the settings page%s for this to work", 'lang_sms'), "<a href='".admin_url("profile.php#profile_phone")."'>", "</a>", "<a href='".admin_url("options-general.php?page=settings_mf_base#settings_sms")."'>", "</a>");
				}
			}

			else
			{
				echo sprintf(__("You have to %sadd Provider and credentials on the settings page%s for this to work", 'lang_sms'), "<a href='".admin_url("options-general.php?page=settings_mf_base#settings_sms")."'>", "</a>");
			}

		echo "</div>
	</div>";

	$tbl_group = new mf_sms_table();

	$tbl_group->select_data(array(
		//'select' => "*",
	));

	$tbl_group->do_display();

echo "</div>";