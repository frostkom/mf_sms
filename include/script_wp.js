jQuery(function($)
{
	$(document).on('click', "a[href^='tel:']", function(e)
	{
		if(e.which != 3)
		{
			var this_href = $(this).attr('href').replace('tel:', ''),
				url = '/wp-admin/admin.php?page=mf_sms/list/index.php&strSmsTo=' + this_href;

			location.href = url;

			return false;
		}
	});

	$('#mf_sms textarea').on('keyup', function()
	{
		var text_length = $(this).val().length,
			sms_amount = Math.ceil(text_length / 155);
			chars_left = 155 - text_length % 155;

		$('#sms_amount').text(sms_amount);
		$('#chars_left').text(chars_left);
	});

	$('#mf_sms').on('submit', function()
	{
		$('.updated, .error').hide();

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
					$('.updated').show();

					$('#mf_sms')[0].reset();
				}

				else if(data.error)
				{
					$('.error').show();
					//alert(data.error);
				}
			}
		});

		return false;
	});
});