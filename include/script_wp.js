jQuery(function($)
{
	$(document).on('click', "a[href^='tel:']", function(e)
	{
		if(e.which != 3)
		{
			var this_href = $(this).attr('href').replace('tel:', ''),
				url = script_sms.admin_url + '&strSmsTo=' + this_href;

			location.href = url;

			return false;
		}
	});

	$("#strSmsTo").autocomplete(
	{
		source: function(request, response)
		{
			$.ajax(
			{
				url: script_sms.plugin_url + 'api/?type=sms/search',
				dataType: 'json',
				data: {
					s: request.term
				},
				success: function(data)
				{
					if(data.amount > 0)
					{
						response(data);
					}
				}
			});
		},
		minLength: 3
	});

	function calculate_amount()
	{
		var text_length = $("#strMessageText, #strSmsText").val().length,
			recipients = 0,
			chars_limit = 155;

		if(text_length > 0)
		{
			var sms_amount = Math.ceil(text_length / chars_limit),
				chars_left = chars_limit - (text_length % chars_limit);

			if(chars_left == chars_limit)
			{
				chars_left = 0;
			}

			$("#sms_count").removeClass('hide')
				.children("span:first-child").text(sms_amount)
				.siblings("span").text(chars_left);

			if($("#strSmsTo").length > 0 && $("#strSmsTo").val() != '')
			{
				recipients++;

				console.log("To");
			}

			else if($("#arrGroupID").length > 0)
			{
				var selected_options = $("#arrGroupID option:selected");

				$.each(selected_options, function(e)
				{
					recipients += parseInt($(this).attr('amount'));
				});

				console.log("Group");
			}
		}

		else
		{
			$("#sms_count").addClass('hide');
		}

		if(($("#strMessageFrom").length > 0 && $("#strMessageFrom").val() != '' || $("#strSmsFrom").length > 0 && $("#strSmsFrom").val() != '') && recipients > 0)
		{
			var sms_total = recipients * sms_amount,
				sms_cost = Math.ceil(sms_total * script_sms.sms_price);

			$("#sms_cost").removeClass('hide')
				.children("span:first-child").text(sms_total)
				.siblings("span").text(sms_cost);

			$("button[name='btnGroupSend']").removeAttr("disabled");
		}

		else
		{
			$("#sms_cost").addClass('hide');
				
			$("button[name='btnGroupSend']").prop({'disabled': 'disabled'});
		}
	}

	calculate_amount();

	$("#strMessageFrom, #strSmsFrom, #arrGroupID").on('change', function()
	{
		calculate_amount();
	});

	$("#strSmsTo, #strMessageText, #strSmsText").on('keyup', function()
	{
		calculate_amount();
	});

	$("#mf_sms").on('submit', function()
	{
		$(".updated, .error").addClass('hide');

		var form_data = $(this).serialize();

		$.ajax(
		{
			url: script_sms.plugin_url + 'api/?type=sms_send',
			type: 'post',
			dataType: 'json',
			data: form_data,
			success: function(data)
			{
				if(data.success)
				{
					$(".updated").removeClass('hide');

					$("#mf_sms")[0].reset();
				}

				else
				{
					$(".error").removeClass('hide');
				}
			}
		});

		return false;
	});
});