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
				url: script_sms.plugin_url + 'ajax.php?type=sms/search',
				dataType: "json",
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

	$('#strMessageText, #strSmsText').on('keyup', function()
	{
		var text_length = $(this).val().length,
			sms_amount = Math.ceil(text_length / 155);
			chars_left = 155 - text_length % 155;

		$('#sms_amount').text(sms_amount);
		$('#chars_left').text(chars_left);
	});

	$('#mf_sms').on('submit', function()
	{
		$('.updated, .error').addClass('hide');

		var form_data = $(this).serialize();

		$.ajax(
		{
			url: script_sms.plugin_url + 'ajax.php?type=sms_send',
			type: 'post',
			data: form_data,
			dataType: 'json',
			success: function(data)
			{
				if(data.success)
				{
					$('.updated').removeClass('hide');

					$('#mf_sms')[0].reset();
				}

				else if(data.error)
				{
					$('.error').removeClass('hide');
				}
			}
		});

		return false;
	});
});