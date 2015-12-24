jQuery(document).ready(function($){
		
	$('#shipping_postcode, #shipping_city, #shipping_address_1').focusout(function() {

		var country = $('#select2-chosen-2').text();
		var city = $('#shipping_city').val();
		var address = $('#shipping_address_1').val();
		var postcode = $('#shipping_postcode').val();

		var data = {
			'action': 'show_shops',
			'country': country,
			'city': city,
			'address': address,
			'postcode': postcode,
		};
		
		$.ajax({
			url: MyAjax.ajaxurl,
			type: 'POST',
			data: data,
			success: function (response) {
				$( 'body' ).trigger( 'update_checkout' );
			}
		})
	});

	// Save billing_email in session(for no login user).
	$('#billing_email').focusout(function() {
		var email = $(this).val();
		var data = {
			'action': 'save_billing_email',
			'billing_email': email
		};
		
		$.ajax({
			url: MyAjax.ajaxurl,
			type: 'POST',
			data: data,
			success: function (response) {
				$( 'body' ).trigger( 'update_checkout' );
			}
		})
	});

	// Click and init map.
	$('#send24_map').live('click' ,function(){
		var map_value = $('#send24_map').attr('rel');
		 $('#send24-popup-map').append('<script> coordinates = '+map_value+';</script>');
		 initMap();
	});

	// Select shops.
	$('#send24_select_shops').live('change' ,function(){
		var id = $(this).val();

	    var data = {
			'action': 'select_shops',
			'id': id,
		};
			
		$.ajax({
			url: MyAjax.ajaxurl,
			type: 'POST',
			data: data,
			success: function (response){
				// console.log(response);
			}
		})
	});

	var map;
	var one_start = 0;
    function initMap() {
	    if (coordinates != "") {
		    var start_coordinates = Math.round(coordinates.length/2);
		    map = new google.maps.Map(document.getElementById('map'), {
		        center: {lat: Number(coordinates[start_coordinates].lat), lng: Number(coordinates[start_coordinates].lng)},
		        zoom: 12
		    });
		    var marker = Array();
		    for (var i = 0; i < coordinates.length; i++) {  
		        var infowindow = new google.maps.InfoWindow;
		        infowindow.setContent('<b>'+coordinates[i].title+'</b>');

	            marker[i] = new google.maps.Marker({
  	                map: map, 
		            position: {lat: Number(coordinates[i].lat), lng: Number(coordinates[i].lng)},
		            id_marker: coordinates[i].id,
		        });

		        // Only run at startup.
	            if(one_start == '0'){
		            var data = {
						'action': 'show_map',
						'id': coordinates[0].id,
					};
						
					$.ajax({
						url: MyAjax.ajaxurl,
						type: 'POST',
						data: data,
						success: function (response) {
							var res = JSON.parse(response);
							var shop = JSON.parse(res.shop_info);
							var rating = JSON.parse(res.rating_info);

							var monday = '<tr><td>Monday</td><td>'+shop.opening_week.Monday.start+'-'+shop.opening_week.Monday.end+'</td></tr>';
							var tuesday = '<tr><td>Tuesday</td><td>'+shop.opening_week.Tuesday.start+'-'+shop.opening_week.Tuesday.end+'</td></tr>';
							var wednesday = '<tr><td>Wednesday</td><td>'+shop.opening_week.Wednesday.start+'-'+shop.opening_week.Wednesday.end+'</td></tr>';
							var thursday = '<tr><td>Thursday</td><td>'+shop.opening_week.Thursday.start+'-'+shop.opening_week.Thursday.end+'</td></tr>';
							var friday = '<tr><td>Friday</td><td>'+shop.opening_week.Friday.start+'-'+shop.opening_week.Friday.end+'</td></tr>';
							var saturday = '<tr><td>Saturday</td><td>'+shop.opening_week.Saturday.start+'-'+shop.opening_week.Saturday.end+'</td></tr>';
							var sunday = '<tr><td>Sunday</td><td>'+shop.opening_week.Sunday.start+'-'+shop.opening_week.Sunday.end+'</td></tr>';
	             			$('#send24_info_map').html('<h3 id="popup_h3">Addresse</h3><p>Shop: '+shop.shop_title+'</p><p id="step_1info_map">'+shop.shop_location+'</p>');
	             			$('#step_1info_map').after('<h3 id="popup_h3">Abningstider</h3><table id="popup_table">'+monday+''+tuesday+''+wednesday+''+thursday+''+friday+''+saturday+''+sunday+'</table>');
	         	
	             			if(rating.user_login != null){
	             				if(rating.rating != '0.0'){
									var avatar = rating.user_avatar;
									var rating_html = '<div id="rating_send24"><div id="rating_avatar_send24">'+avatar+'<br><div id="rating_name_user">'+rating.user_login+'</div></div><div id="rating_service_name">'+rating.category+'<br><span class="send24_rating_stars"><span class="stars-rating">';	
									// var rating_html = '';
									for (var i = 0; i < Math.ceil(rating.rating); i++) {
										rating_html += '<span class="dashicons dashicons-star-filled"></span>';
									};
									if(i <= 5){
										var c = 5-i;
										for (var i = 0; i < c; i++) {
											rating_html += '<span class="dashicons dashicons-star-empty"></span>';
										};
									}
									rating_html += '</span><span class="send24_rating_average">'+rating.rating+'</span></span></div></div>	';
			             			$('#step_1info_map').after(rating_html);
			             			$('#step_1info_map').after('<h3 class="bestyrer_h3">Bestyrer</h3>');
			             		}
		             		}
							$('#send24_selected_shop').html('Selected shop: <b style="color: #4E8FFD;">'+shop.shop_title+'</b>');
							$('#send24_map').html('change shop');
							$('.send34_map_selected').html('<span style="font-size: 12px;">Selected: <b style="color: #4E8FFD;">'+shop.shop_title+'</b></span>');
						}
					})
					// fixed start.
					one_start = 1;
				}

	            // Click markers.
	            marker[i].addListener('click', function(){
	                var m = this;

	             	var data = {
						'action': 'show_map',
						'id': this.id_marker,
					};
						
					$.ajax({
						url: MyAjax.ajaxurl,
						type: 'POST',
						data: data,
						success: function (response) {
							var res = JSON.parse(response);
							var shop = JSON.parse(res.shop_info);
							var rating = JSON.parse(res.rating_info);

							var monday = '<tr><td>Monday</td><td>'+shop.opening_week.Monday.start+'-'+shop.opening_week.Monday.end+'</td></tr>';
							var tuesday = '<tr><td>Tuesday</td><td>'+shop.opening_week.Tuesday.start+'-'+shop.opening_week.Tuesday.end+'</td></tr>';
							var wednesday = '<tr><td>Wednesday</td><td>'+shop.opening_week.Wednesday.start+'-'+shop.opening_week.Wednesday.end+'</td></tr>';
							var thursday = '<tr><td>Thursday</td><td>'+shop.opening_week.Thursday.start+'-'+shop.opening_week.Thursday.end+'</td></tr>';
							var friday = '<tr><td>Friday</td><td>'+shop.opening_week.Friday.start+'-'+shop.opening_week.Friday.end+'</td></tr>';
							var saturday = '<tr><td>Saturday</td><td>'+shop.opening_week.Saturday.start+'-'+shop.opening_week.Saturday.end+'</td></tr>';
							var sunday = '<tr><td>Sunday</td><td>'+shop.opening_week.Sunday.start+'-'+shop.opening_week.Sunday.end+'</td></tr>';
	             			$('#send24_info_map').html('<h3 id="popup_h3">Addresse</h3><p>Shop: '+shop.shop_title+'</p><p id="step_1info_map">'+shop.shop_location+'</p>');
	             			$('#step_1info_map').after('<h3 id="popup_h3">Abningstider</h3><table id="popup_table">'+monday+''+tuesday+''+wednesday+''+thursday+''+friday+''+saturday+''+sunday+'</table>');
	             			if(rating.user_login != null){
	             				if(rating.rating != '0.0'){
									var avatar = rating.user_avatar;
									var rating_html = '<div id="rating_send24"><div id="rating_avatar_send24">'+avatar+'<br><div id="rating_name_user">'+rating.user_login+'</div></div><div id="rating_service_name">'+rating.category+'<br><span class="send24_rating_stars"><span class="stars-rating">';	
									// var rating_html = '';
									for (var i = 0; i < Math.ceil(rating.rating); i++) {
										rating_html += '<span class="dashicons dashicons-star-filled"></span>';
									};
									if(i <= 5){
										var c = 5-i;
										for (var i = 0; i < c; i++) {
											rating_html += '<span class="dashicons dashicons-star-empty"></span>';
										};
									}
									rating_html += '</span><span class="send24_rating_average">'+rating.rating+'</span></span></div></div>	';
			             			$('#step_1info_map').after(rating_html);
			             			$('#step_1info_map').after('<h3 class="bestyrer_h3">Bestyrer</h3>');
			             		}
		             		}
							$('#send24_selected_shop').html('Selected shop: <b style="color: #4E8FFD;">'+shop.shop_title+'</b>');
							$('#send24_map').html('change shop');
							$('.send34_map_selected').html('<span style="font-size: 12px;">Selected: <b style="color: #4E8FFD;">'+shop.shop_title+'</b></span>');
						}
					})
		        });
		    };
		}
    }

    // Coupons. 
    $('#enter_coupons').live('focusout' ,function(){
    	var value = $(this).val();
    	if(value <= 19){
	 		// Valid coupon function.
	 		function check_cuopon(value){
	   	    	if(value !== ''){
			    	var str = value;
					str = str.replace(/\W/g, "");
					str = str.replace("_", "");
					var regexp = /[A-Za-z0-9]{4}/gi;
					var matches = str.match(regexp);

				    var result = matches.join('-');
					var valid_coupon = /^([A-Za-z0-9]{4}-){3}[A-Za-z0-9]{4}$/;

				    if(result.length <= 19){
						$('#enter_coupons').val(result);
				    	if(valid_coupon.test(result) == true){
				    		var data = {
								'action': 'select_coupons',
								'coupon': result,
							};
									
							$.ajax({
								url: MyAjax.ajaxurl,
								type: 'POST',
								data: data,
								success: function (response) {
									if(response == 'true'){
			        				  $('#check_valid_coupon').html(' - success').css('color','#138E5B');
			        				  $( 'body' ).trigger( 'update_checkout' );
			        				}else{
			        				  $('#check_valid_coupon').html(' - no valid').css('color', '#F54040');
			        				  $( 'body' ).trigger( 'update_checkout' );
			        				}
								}
							});
			        	}else{
			        		$('#check_valid_coupon').html(' - no valid').css('color', '#F54040');
			        	}
				    }else{
				    	$('#enter_coupons').val(result.substr(0, result.length - (result.length-19)));
				    	// Check again.
				    	check_cuopon(value);
				    }
				}
			}
			// Check valid coupon.
	    	check_cuopon(value);
	    }else{
	    	$(this).val(value.substr(0, value.length - (value.length-19)));
	    	// Check valid coupon.
	    	check_cuopon(value);
	    }
    });
});
