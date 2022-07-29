jQuery(function($){
	$(".ajax_button").click(function() {
		var data = $(".bs_platform_setting_form").serialize();
		data += "&page=bs_platform_setting&action=bs_platform_ajax_setting";

		$('#bspf_saveMessage').hide();
		$('#bspf_saving_data').show();

		$.ajax({
			type : "post",
			dataType : "json",
			url : bs_platform_admin_setting_ajax_object.ajax_url,
			data : data,
			success: function(response) { 
				$('#bspf_saving_data').hide();
				$('#saved_data').html('<div id="bspf_saveMessage" class="bspf_successModal"></div>');
				$('#bspf_saveMessage').html('<p>設定を保存しました。</p>').show();
				setTimeout(function() {
					$('#bspf_saveMessage:not(:hidden, :animated)').fadeOut();
				}, 3000);
			},
			error: function() {
				$('#bspf_saving_data').hide();
				alert("エラーが発生しました。");
			}
		});

		return false;
	});
});


