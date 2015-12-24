jQuery(document).ready(function($){

	// Show service.
	$('#woocommerce_send24_shipping_enabled_services').on('click', function(){
		$(".service-send24").toggle(this.checked);
	});

	// Show insurence.
  	$('#woocommerce_send24_shipping_enabled_insurance').on('click', function(){
		$("#woocommerce_send24_shipping_insurance_field").parents('tr').toggle(this.checked);
	});
  	// Hide, doesn't work until the service.
  	// $("#woocommerce_send24_shipping_enabled_express").parents('tr').hide();
	// Check postcode.
	$('#woocommerce_send24_shipping_zip').focusout(function(e) {

		e.preventDefault();
		var value = $(this).val();
		var data = {
			'action': 'check_postcode',
			'zip': value
		};

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: data,
			success: function (response) {
				 if (response == 'true') {
					$('.p_send24_success').remove();
					$('.p_send24_error').remove();
					$('#woocommerce_send24_shipping_zip').after('<p class="p_send24_success">success</p>');
					$('#woocommerce_send24_shipping_c_key').after('<p class="p_send24_success">success</p>');
					$('#woocommerce_send24_shipping_c_secret').after('<p class="p_send24_success">success</p>');

					setTimeout(function(){
						$('.p_send24_success').remove();
					}, 5000)
				 }else{
				 	$('.p_send24_success').remove();
					$('.p_send24_error').remove();
				 	data = JSON.parse(response);
				 	if (data == "") {
				 		$('#woocommerce_send24_shipping_zip').after('<p class="p_send24_error">invalid</p>');
				 	}else{
				 		$('#woocommerce_send24_shipping_c_key').after('<p class="p_send24_error">invalid</p>');
						$('#woocommerce_send24_shipping_c_secret').after('<p class="p_send24_error">invalid</p>');
				 	}
				 }
			}
		})
	});

	$('#points_sales_you_percent').change(function(){
	    if ($(this).val() > 7){
	        $(this).val(7);
	    }else if ($(this).val() < 1){
            $(this).val(1);
	      }       
    }); 

});