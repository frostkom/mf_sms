<?php

$obj_sms = new mf_sms();

$result = $wpdb->get_results("SELECT MIN(post_date) AS dateMin, MAX(post_date) AS dateMax FROM ".$wpdb->posts." WHERE post_type = 'mf_sms'");

foreach($result as $r)
{
	$dteDateMin = $r->dateMin;
	$dteDateMax = $r->dateMax;
}

$intDateMin_year = date("Y", strtotime($dteDateMin));
$intDateMax_year = date("Y", strtotime($dteDateMax));
$intDateMin_month = date("Y-m", strtotime($dteDateMin));
$intDateMax_month = date("Y-m", strtotime($dteDateMax));

$dteSmsMonth = check_var('dteSmsMonth', 'date', true, date("Y-m", strtotime($dteDateMax)));

$arr_data_months = [];

for($i = $intDateMin_year; $i <= $intDateMax_year; $i++)
{
	$date = $i;

	$arr_data_months[$date] = "-- ".$date." --";

	for($j = 1; $j <= 12; $j++)
	{
		$date = $i."-".zeroise($j, 2);

		if($i == $intDateMin_year && $date < $intDateMin_month)
		{
			//Do nothing
		}

		else if($i == $intDateMax_year && $date > $intDateMax_month)
		{
			break;
		}

		else
		{
			$wpdb->get_results("SELECT post_author FROM ".$wpdb->posts." WHERE post_type = 'mf_sms' AND post_date LIKE '".$date."-%' LIMIT 0, 1");

			if($wpdb->num_rows > 0)
			{
				$arr_data_months[$date] = month_name($j);
			}
		}
	}
}

echo "<div class='wrap'>
	<h2>".__("Statistics", 'lang_sms')."</h2>
	<div id='poststuff'>
		<div id='post-body' class='columns-2'>
			<div id='post-body-content'>
				<div class='postbox'>
					<h3 class='hndle'><span>".__("Amount / User", 'lang_sms')."</span></h3>
					<div class='inside'>";

						$result = $wpdb->get_results("SELECT post_author, COUNT(post_author) AS sms_amount, display_name FROM ".$wpdb->posts." LEFT JOIN ".$wpdb->users." ON ".$wpdb->posts.".post_author = ".$wpdb->users.".ID WHERE post_type = 'mf_sms'".($dteSmsMonth != '' ? " AND post_date LIKE '".$dteSmsMonth."-%'" : "")." GROUP BY post_author ORDER BY sms_amount DESC");

						if($wpdb->num_rows > 0)
						{
							echo "<ul>";

								foreach($result as $r)
								{
									$intUserID = $r->post_author;
									$intSmsAmount = $r->sms_amount;
									$strUserName = $r->display_name;

									echo "<li>".$intSmsAmount.". ".$strUserName."</li>";
								}

							echo "</ul>";
						}

						else
						{
							echo "<p>".__("There were no text messages sent during this period", 'lang_sms')."</p>";
						}

					echo "</div>
				</div>
			</div>
			<div id='postbox-container-1'>
				<div class='postbox'>
					<h3 class='hndle'><span>".__("Filter", 'lang_sms')."</span></h3>
					<div class='inside'>
						<form".apply_filters('get_form_attr', "").">"
							.show_select(array('data' => $arr_data_months, 'name' => 'dteSmsMonth', 'value' => $dteSmsMonth, 'xtra' => "rel='submit_change' class='is_disabled' disabled"))
						."</form>
					</div>
				</div>
				<div class='postbox'>
					<h3 class='hndle'><span>".__("Overall", 'lang_sms')."</span></h3>
					<div class='inside'>";

						$wpdb->get_results("SELECT post_author FROM ".$wpdb->posts." LEFT JOIN ".$wpdb->users." ON ".$wpdb->posts.".post_author = ".$wpdb->users.".ID WHERE post_type = 'mf_sms'");

						$intSmsTotal = $wpdb->num_rows;

						echo "<p>".__("First Date", 'lang_sms').": ".format_date($dteDateMin)."</p>
						<p>".__("Last date", 'lang_sms').": ".format_date($dteDateMax)."</p>
						<p>".__("Total Amount", 'lang_sms').": ".$intSmsTotal."</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>";