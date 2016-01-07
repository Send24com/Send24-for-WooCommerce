<?php

class WC_Send24_Shipping_Method extends WC_Shipping_Method{

	public $price;
	public $price_express;
	public $default_postcode = 999;
	public $category_danmark = 'Danmark';
	public $category_express = 'Ekspres';
	public $status_danmark = false;
	public $status_international = false;
	public $percent = 10;
	public $send24_settings;
	public $auth;

  	public function __construct()
  	{
		$this->id = 'send24_shipping';
	  	$this->method_title = __( 'Send24', 'woocommerce' );

	  	// Load the settings.
	  	$this->send24_settings = get_option('send24_settings');
	  	$this->auth = base64_encode($this->send24_settings['c_key'].':'.$this->send24_settings['c_secret']);
	  	$this->init_form_fields();
	  	$this->init_settings();

	  	// Define user set variables
	  	$this->enabled	= $this->get_option( 'enabled' );
	  	$this->title = $this->get_option( 'title' );

  		add_action('woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options'));
  	}

  	public function init_form_fields(){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://send24.com/wc-api/v3/get_user_id");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			"Content-Type: application/json",
			"Authorization: Basic " . $this->auth
			));
		$user_meta = json_decode(curl_exec($ch));
		if(!empty($user_meta->return_activate)){
			$result_return = $user_meta->return_webpage_link['0'];
		}
		curl_close($ch);
		// Link return service.
		if(!empty($result_return)){
			$return_service = '<a id="link_return" href="'.$result_return.'" target="_blank">'.$result_return.'</a>';
		}else{
			$return_service = '<a id="button_return" href="http://send24.com/retur-indstilling/" target="_blank">Apply</a>';
		}

  		// General settings.
  		$this->form_fields = array(
		  	'enabled' => array(
		      'title' 	=> __( 'Enable/Disable', 'woocommerce' ),
		      'type' 	=> 'checkbox',
		      'label' 	=> __( 'Send24 Shipping', 'woocommerce' ),
		      'default' => 'yes'
		    ),
		  	'title' => array(
		      'title' 		=> __( 'Method Title', 'woocommerce' ),
		      'type' 			=> 'text',
		      'description' 	=> __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
		      'default'		=> __( 'Send24', 'woocommerce' ),
		    ),
		    'zip' => array(
		      'title' 		=> __( 'Postcode', 'woocommerce' ),
		      'type' 			=> 'text',
		      'default'		=> '999',
		    ),
		    'api'           => array(
				'title'       => __( 'API Settings', 'woocommerce-shipping-usps' ),
				'type'        => 'title',
				'description' => sprintf( __( 'You can obtain send24 user ID by signing up on the %s website.', 'woocommerce-shipping-usps' ), '<a href="https://send24.com/apikey/" target="_blank">Send24.com</a>' )
		    ),
		    'c_key' => array(
		      'title' 		=> 'Send24 Consumer Key',
		      'type' 			=> 'text',
		    ),
		    'c_secret' => array(
		      'title' 		=> 'Send24 Consumer Secret',
		      'type' 			=> 'text',
		    ),
		    'enabled_track' => array(
		      'title' 	=> __( 'Track Notice:', 'woocommerce' ),
		      'type' 	=> 'checkbox',
		      'label' 	=> __( 'Enable track link in the order confirmation mail', 'woocommerce' ),
		      'default' => 'no'
		    ),
		    'payment_and_product'           => array(
				'title'       => 'Payment Settings',
				'type'        => 'title',
		    ),
		    'whopay' => array(
		      'title' 	=> __( 'Payment parcels', 'woocommerce' ),
		      'type'        => 'select',
				'default'     => 'user',
				'options'     => array(
				'shop'      => 'Shop',
				'user'         => 'User'),
				'desc_tip'    => true,
				'description' => 'Who will pay the shipping costs?',
		     ),
		    'send24_coupons'           => array(
				'title'       => 'Coupons Settings',
				'type'        => 'title',
		    ),
		     'enabled_coupons' => array(
		      'title' 	=> __( 'Discount', 'woocommerce' ),
		      'type' 	=> 'checkbox',
		      'label' 	=> __( 'Enable coupons for user in checkout', 'woocommerce' ),
		      'default' => 'no'
		    ),
		    'product_title'         => array(
				'title'       => 'Product Settings',
				'type'        => 'title',
		    ),
		    'enabled_danmark' => array(
		      'title' 	=> __( 'Danmark:', 'woocommerce' ),
		      'type' 	=> 'checkbox',
		      'label' 	=> __( 'Danmark', 'woocommerce' ),
		      'default' => 'yes'
		    ),
		    'insurance_field' => array(
		      'title' 		=> __( 'Level', 'woocommerce' ),
		      'class' => 'level_class',
		      'type'        => 'select',
				'default'     => 'DKK 1',
				'options'     => array(
				'0'      => '1000kr',
				'DKK 1'      => '2000kr',
				'DKK 2'      => '3000kr',
				'DKK 3'      => '4000kr',
				'DKK 4'      => '5000kr'),
				'desc_tip'    => true,
				'description' => 'Please choose your insurance',
		    ),
		    'enabled_international' => array(
		      'title' 	=> __( 'International:', 'woocommerce' ),
		      'type' 	=> 'checkbox',
		      'label' 	=> __( 'International', 'woocommerce' ),
		      'default' => 'yes',
		      'description' => 'Insurance see specifications on Send24/pakke',
		    ),
		    'enabled_express' => array(
		      'title' 	=> __( 'Express:', 'woocommerce' ),
		      'type' 	=> 'checkbox',
		      'label' 	=> __( 'Express', 'woocommerce' ),
		      'default' => 'no',
		    ),
		    'start_work_express' => array(
		      'title' 	=> __( 'Start time work Express:', 'woocommerce' ),
		      'type'        => 'select',
				'default'     => '08:00',
				'options'     => array(
					'00:00' => '00:00',
					'00:30' => '00:30',
					'01:00' => '01:00',
					'01:30' => '01:30',
					'02:00' => '02:00',
					'02:30' => '02:30',
					'03:00' => '03:00',
					'03:30' => '03:30',
					'04:00' => '04:00',
					'04:30' => '04:30',
					'05:00' => '05:00',
					'05:30' => '05:30',
					'06:00' => '06:00',
					'06:30' => '06:30',
					'07:00' => '07:00',
					'07:30' => '07:30',
					'08:00' => '08:00',
					'08:30' => '08:30',
					'09:00' => '09:00',
					'09:30' => '09:30',
					'10:00' => '10:00',
					'10:30' => '10:30',
					'11:00' => '11:00',
					'11:30' => '11:30',
					'12:00' => '12:00',
					'12:30' => '12:30',
					'13:00' => '13:00',
					'13:30' => '13:30',
					'14:00' => '14:00',
					'14:30' => '14:30',
					'15:00' => '15:00',
					'15:30' => '15:30',
					'16:00' => '16:00',
					'16:30' => '16:30',
					'17:00' => '17:00',
					'17:30' => '17:30',
					'18:00' => '18:00',
					'18:30' => '18:30',
					'19:00' => '19:00',
					'19:30' => '19:30',
					'20:00' => '20:00',
					'20:30' => '20:30',
					'21:00' => '21:00',
					'21:30' => '21:30',
					'22:00' => '22:00',
					'22:30' => '22:30',
					'23:00' => '23:00',
					'23:30' => '23:30'),
		      'desc_tip'    => true,
			  'description' => 'Please choose start time work Express',
		    ),
		    'end_work_express' => array(
		      'title' 	=> __( 'End time work Express:', 'woocommerce' ),
		       'type'        => 'select',
				'default'     => '18:00',
				'options'     => array(
					'00:00' => '00:00',
					'00:30' => '00:30',
					'01:00' => '01:00',
					'01:30' => '01:30',
					'02:00' => '02:00',
					'02:30' => '02:30',
					'03:00' => '03:00',
					'03:30' => '03:30',
					'04:00' => '04:00',
					'04:30' => '04:30',
					'05:00' => '05:00',
					'05:30' => '05:30',
					'06:00' => '06:00',
					'06:30' => '06:30',
					'07:00' => '07:00',
					'07:30' => '07:30',
					'08:00' => '08:00',
					'08:30' => '08:30',
					'09:00' => '09:00',
					'09:30' => '09:30',
					'10:00' => '10:00',
					'10:30' => '10:30',
					'11:00' => '11:00',
					'11:30' => '11:30',
					'12:00' => '12:00',
					'12:30' => '12:30',
					'13:00' => '13:00',
					'13:30' => '13:30',
					'14:00' => '14:00',
					'14:30' => '14:30',
					'15:00' => '15:00',
					'15:30' => '15:30',
					'16:00' => '16:00',
					'16:30' => '16:30',
					'17:00' => '17:00',
					'17:30' => '17:30',
					'18:00' => '18:00',
					'18:30' => '18:30',
					'19:00' => '19:00',
					'19:30' => '19:30',
					'20:00' => '20:00',
					'20:30' => '20:30',
					'21:00' => '21:00',
					'21:30' => '21:30',
					'22:00' => '22:00',
					'22:30' => '22:30',
					'23:00' => '23:00',
					'23:30' => '23:30'),
		      'desc_tip'    => true,
			  'description' => 'Please choose end time work Express',
		    ),
		    'level_express' => array(
		      'title' 		=> __( 'Level', 'woocommerce' ),
		      'type' 			=> 'text',
		      'default'		=> $this->get_insurance_express(),
		      'value'		=> $this->get_insurance_express(),
		      'desc_tip'    => true,
			 'description' => 'Information',
		    ),
		    'enable_return'         => array(
				'title'       => 'Return Service',
				'type'        => 'title',
		    ),

		    'return_portal'         => array(
				'title'       => 'Return portal',
				'type'        => 'title',
				'description' => ''.$return_service.'',
		    ),
		    'enabled_mail_return' => array(
		      'title' 	=> __( 'Return Notice:', 'woocommerce' ),
		      'type' 	=> 'checkbox',
		      'label' 	=> __( 'Link to Return portal in confirmation mail', 'woocommerce' ),
		      'default' => 'no'
		    ),
		   'shop_settings'         => array(
				'title'       => 'Shop Settings',
				'type'        => 'title',
		    ),
		    'show_shops' => array(
		      'title' 		=> __( 'Show shops as', 'woocommerce' ),
		      'class' => 'level_class',
		      'type'        => 'select',
				'default'     => 'select',
				'options'     => array(
				'select'      => 'select box',
				'map'      => 'map'),
				'desc_tip'    => true,
				'description' => 'Please choose',
		    ),
		    'service'         => array(
				'title'       => 'Service Settings',
				'type'        => 'title',
		    ),
		    'enabled_services' => array(
		      'title' 	=> __( 'Enable/Disable Service', 'woocommerce' ),
		      'type' 	=> 'checkbox',
		      'label' 	=> __( 'Show service', 'woocommerce' ),
		      'default' => 'no'
		    ),
		);

  	}

  	// Check woo.
	public function admin_options(){
		// Check curentcy.
		if ( get_woocommerce_currency() != "DKK" ) {
			echo '<div class="error">
				<p>' . sprintf( __( 'Send24 requires that the <a href="%s">currency</a> is set to Danish Krone (DKK).', 'woocommerce-shipping-usps' ), admin_url( 'admin.php?page=wc-settings&tab=general' ) ) . '</p>
			</div>';
		}

		// Save/Update key.
		if(empty($this->send24_settings)){
	  		add_option('send24_settings', $this->settings);
	  	}else{
	  		update_option('send24_settings', $this->settings);
	  	}
	  	// Check keys and zip user.
	  	if($_POST){
	  		$this->check_key_and_zip($_POST['woocommerce_send24_shipping_c_key'], $_POST['woocommerce_send24_shipping_c_secret'], $_POST['woocommerce_send24_shipping_zip']);
		}else{
			$this->check_key_and_zip($this->send24_settings['c_key'], $this->send24_settings['c_secret'], $this->send24_settings['zip']);
		}
		
		// Show settings
		parent::admin_options();
		// Check and show service.
		if($_POST){
			if(!empty($_POST['woocommerce_send24_shipping_enabled_services'])){
				echo $this->generate_services_html($_POST['woocommerce_send24_shipping_enabled_services']);
			}else{
				echo $this->generate_services_html('no');
			}
		}else{
			echo $this->generate_services_html($this->send24_settings['enabled_services']);
		}
	}

	// Get inseranse express.
	public function get_insurance_express(){
		if($this->send24_settings['enabled_express'] == 'yes'){
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://send24.com/wc-api/v3/get_products");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				"Content-Type: application/json",
				"Authorization: Basic " . $this->auth
				));
			$send24_countries = json_decode(curl_exec($ch));
			$select_country = 'Ekspres';
			$n = count($send24_countries);
			for ($i = 0; $i < $n; $i++)
			{
				if ($send24_countries[$i]->title == $select_country)
				{
					$result = $send24_countries[$i]->weigth->options->_empty_->label;
				}
			}
			if(!empty($result)){
				return $result;
			}
		}
	}

	// Check keys user.
	public function check_key_and_zip($c_key, $c_secret, $postcode){
		$auth = base64_encode($c_key.':'.$c_secret);
		if(!empty($zip)){
			$postcode = $this->default_postcode;
		}
		// Check zip.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://send24.com/wc-api/v3/get_service_area/".$postcode);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			"Content-Type: application/json",
			"Authorization: Basic ".$auth
			));
		$zip_area = curl_exec($ch);
		$zip = json_decode($zip_area, true);
		if(!empty($zip['errors'])){
			echo '<div class="error"><p>Invalid Key</p></div>';
		}else{
			if($zip_area != 'true'){
		 		echo '<div class="error"><p>Invalid ZIP</p></div>';
			}
		}
	}


	// Generate_services_html function.
	public function generate_services_html($display) {
		$output = "";
		ob_start();
		if($display == 'no'){
			$visibility = 'display: none;';
		}else{
			$visibility = 'display: block;';
		}
		include_once(S_PLUGIN_DIR.'views/services.php');
		$output .= ob_get_clean();
		return $output;
	}

	// Show/Hide shipping.
  	public function is_available($package){
  		$weight = 0;

  		foreach ( $package['contents'] as $item_id => $values ) {
  		  $_product  = $values['data'];
	      $weight =  $weight + $_product->get_weight() * $values['quantity'];
	    }

	  	$is_available = "yes" === $this->enabled;

		global $wpdb;
		if ($is_available){
	        if(!empty($this->send24_settings)){ 
	        	$is_available = false;
				$res['auth'] = $this->auth;
				// Check zip.
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, "https://send24.com/wc-api/v3/get_service_area/".$package['destination']['postcode']);
				//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
				curl_setopt($ch, CURLOPT_HEADER, FALSE);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					"Content-Type: application/json",
					"Authorization: Basic ".$res['auth']
					));
				$zip_area = curl_exec($ch);
				curl_close($ch);
				if($zip_area == 'true'){
					// Check country.
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, "https://send24.com/wc-api/v3/get_products");
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
					curl_setopt($ch, CURLOPT_HEADER, FALSE);
					curl_setopt($ch, CURLOPT_HTTPHEADER, array(
						"Content-Type: application/json",
						"Authorization: Basic " . $res['auth']
						));
					$send24_countries = json_decode(curl_exec($ch));
					curl_close($ch);
					$n = count($send24_countries);

					// Default countries.
					for ($i = 0; $i < $n; $i++)
					{
						$select_country = WC()->countries->countries[$package['destination']['country']];
						if($select_country == 'Denmark'){
							$select_country = $this->category_danmark;
							// Express shipping.
							if($send24_countries[$i]->category_name == $this->category_express){
								$this->price_express = $send24_countries[$i]->price;
							}

							if($this->send24_settings['enabled_danmark'] == 'no'){
								 $this->status_danmark = false;
								 if($this->send24_settings['enabled_express'] == 'yes'){
								 	$is_available = true;
								 }else{
								 	$is_available = false;
								 }
							}else{
								$this->status_danmark = true;
								$is_available = true;
							}
						}else{
							// NO International.
							if($this->send24_settings['enabled_international'] == 'no'){
								if($this->send24_settings['enabled_express'] == 'no'){
									$is_available = false;
								}else{
									$is_available = true;
								}
							// Yes International.
							}else{
								$is_available = true;
								$this->status_danmark = true;
								$this->status_international = true;
							}
						}

						if ($send24_countries[$i]->title == $select_country )
						{
							$res['msg'] = 'success';
							$this->price = $send24_countries[$i]->price;
						}else{ 
							$res['msg'] = 'error';
						}
					}	
				}else{
					// Fix - show without checked postcode express.
					// $is_available = false;
					// Check country.
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, "https://send24.com/wc-api/v3/get_products");
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
					curl_setopt($ch, CURLOPT_HEADER, FALSE);
					curl_setopt($ch, CURLOPT_HTTPHEADER, array(
						"Content-Type: application/json",
						"Authorization: Basic " . $res['auth']
						));
					$send24_countries = json_decode(curl_exec($ch));
					curl_close($ch);
					$n = count($send24_countries);
					for ($i = 0; $i < $n; $i++)
					{
						$select_country = WC()->countries->countries[$package['destination']['country']];
						if($select_country == 'Denmark'){
							$select_country = $this->category_danmark;
							// Express shipping.
							if($send24_countries[$i]->category_name == $this->category_express){
								$this->price_express = $send24_countries[$i]->price;
							}

							if($this->send24_settings['enabled_danmark'] == 'no'){
								 $this->status_danmark = false;
								 if($this->send24_settings['enabled_express'] == 'yes'){
								 	$is_available = true;
								 }else{
								 	$is_available = false;
								 }
							}else{
								$this->status_danmark = false;
								$is_available = true;
							}
						}
					}
				}
			}
		}

		return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package);
  	}

  	// Calculate.
  	public function calculate_shipping($package){
     	// Check service points - Denmark and International. 
  		$get_points_sales = get_option('send24_points_sales');
  		if($get_points_sales['points_sales_enabled'] == 'on' && empty($_SESSION['enable_coupon'])){
  			// If user login.
  			if(is_user_logged_in()){
  				$user_id = get_current_user_id();
  				$billing_email = get_user_meta($user_id, 'billing_email', true);
  				if(empty($billing_email)){
  					$billing_email = $_SESSION['send24_billing_email'];
  				}
  			}else{
   				$billing_email = $_SESSION['send24_billing_email'];
  			}

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://send24.com/wc-api/v3/calc_need_points");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, '
											{
											"email": "'.$billing_email.'",
											"cart_total": "'.$this->price.'"
											}
											');
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				"Authorization: Basic " . $this->auth,
				"Content-Type: application/json",
			));
			$discount_price = curl_exec($ch);
			$response = json_decode($discount_price);
			if(!empty($response->need)){
				$_SESSION['price_need'] = base64_encode($response->need);
				$this->price = $this->price-$response->need;
			}
			curl_close($ch);
  		}

  		// Check Cuopons - Denmark and International.
  		if($this->send24_settings['enabled_coupons'] == 'yes'){
  			if(!empty($_SESSION['enable_coupon'])){
  				$percent_result = ($this->price/100)*$this->percent;
  				$_SESSION['price_need'] = base64_encode($percent_result);
  				$this->price = $this->price-$percent_result; 
  			}
  		}

  		// Check Denmark.
		if($this->status_danmark == true){
			if(!empty($this->send24_settings['insurance_field'])){
				if($this->send24_settings['whopay'] == 'user'){
					if($this->status_international == false){
						$result_price = preg_replace('/[^0-9]/', '', $this->send24_settings['insurance_field']);
		  				$this->price = $this->price+$result_price;
		  			}
	  			}else{
	  				$this->price = 0;
	  			}

			}

			$rate = array(
				'id'    => $this->id,
				'label' => $this->title,
				'cost'  => $this->price
			);
			$this->add_rate($rate);

		}
  		
  		// Check Express.
  		if(!empty($this->price_express)){
				
  			if($this->send24_settings['whopay'] == 'shop'){
  				$coast_express = 0;
  			}else{
  				$coast_express = $this->price_express;
  			}
  			
  			// Check service points - Express. 
  			if($get_points_sales['points_sales_enabled'] == 'on' && empty($_SESSION['enable_coupon'])){
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, "https://send24.com/wc-api/v3/calc_need_points");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
				curl_setopt($ch, CURLOPT_HEADER, FALSE);
				curl_setopt($ch, CURLOPT_POST, TRUE);
				curl_setopt($ch, CURLOPT_POSTFIELDS, '
												{
												"email": "'.$billing_email.'",
												"cart_total": "'.$coast_express.'"
												}
												');
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					"Authorization: Basic " . $this->auth,
					"Content-Type: application/json",
				));
				$discount_price = curl_exec($ch);
				$response = json_decode($discount_price);
				if(!empty($response->need)){
					$_SESSION['price_need_express'] = base64_encode($response->need);
					$coast_express = $coast_express-$response->need;
				}
				curl_close($ch);
  			}

  			// Check Cuopons - Express.
	  		if($this->send24_settings['enabled_coupons'] == 'yes'){
	  			if(!empty($_SESSION['enable_coupon'])){
	  				$percent_result = ($coast_express/100)*$this->percent;
	  				$_SESSION['price_need_express'] = base64_encode($percent_result);
	  				$coast_express = $coast_express-$percent_result; 
	  			}
	  		}
	  		// If Express = enable show shipping.
 	  		if($this->send24_settings['enabled_express'] == 'yes'){
 	  			// Check time work.
 	  			date_default_timezone_set('Europe/Copenhagen');
 	  			$today = strtotime(date("Y-m-d H:i"));
 	  			$start_time = strtotime(''.date("Y-m-d").' '.$this->send24_settings['start_work_express'].'');
 	  			$end_time = strtotime(''.date("Y-m-d").' '.$this->send24_settings['end_work_express'].'');
 	  			if($start_time < $today && $end_time > $today){
 	  				    // Get user billing
 	  				    $ch = curl_init();
			            curl_setopt($ch, CURLOPT_URL, "https://send24.com/wc-api/v3/get_user_id");
			            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			            curl_setopt($ch, CURLOPT_HEADER, FALSE);
			            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			                "Content-Type: application/json",
			                "Authorization: Basic ".$this->auth
			                ));
			            $user_meta = json_decode(curl_exec($ch));

			            $billing_address_1 = $user_meta->billing_address_1['0'];
			            $billing_postcode = $user_meta->billing_postcode['0'];
			            $billing_city = $user_meta->billing_city['0'];
			            $billing_country = $user_meta->billing_country['0'];
			            if($billing_country == 'DK'){
			                $billing_country = 'Denmark';
			            }	
			            // Full address.
 	  					$full_billing_address = "$billing_address_1, $billing_postcode $billing_city, $billing_country";
 	  					$data_customer = WC()->session->customer;
 	  					if($data_customer['shipping_country'] == 'DK'){
			                $data_customer['shipping_country'] = 'Denmark';
			            }
			        	$full_shipping_address = ''.$data_customer['shipping_address_1'].', '.$data_customer['postcode'].' '.$data_customer['shipping_city'].', '.$data_customer['shipping_country'].'';
			            // Get billing coordinates.
			            $billing_url = "http://maps.googleapis.com/maps/api/geocode/json?sensor=false&address=".urlencode($full_billing_address);
			            $billing_latlng = get_object_vars(json_decode(file_get_contents($billing_url)));
			            // Check billing address.
                		if(!empty($billing_latlng['results'])){
				            $billing_lat = $billing_latlng['results'][0]->geometry->location->lat;
				            $billing_lng = $billing_latlng['results'][0]->geometry->location->lng;

				            // Get shipping coordinates.
				            $shipping_url = "http://maps.googleapis.com/maps/api/geocode/json?sensor=false&address=".urlencode($full_shipping_address);
				            $shipping_latlng = get_object_vars(json_decode(file_get_contents($shipping_url)));

				            // Check shipping address.
                    		if(!empty($shipping_latlng['results'])){
					            $shipping_lat = $shipping_latlng['results'][0]->geometry->location->lat;
					            $shipping_lng = $shipping_latlng['results'][0]->geometry->location->lng;
		 	  				    // get_is_driver_area_five_km
					            $ch = curl_init();
					            curl_setopt($ch, CURLOPT_URL, "https://send24.com/wc-api/v3/get_is_driver_area_five_km");
					            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
					            curl_setopt($ch, CURLOPT_HEADER, FALSE);
					            curl_setopt($ch, CURLOPT_POST, TRUE);
					            curl_setopt($ch, CURLOPT_POSTFIELDS, '
					                                            {
					                                                "billing_lat": "'.$billing_lat.'",
					                                                "billing_lng": "'.$billing_lng.'",
					                                                "shipping_lat": "'.$shipping_lat.'",
					                                                "shipping_lng": "'.$shipping_lng.'"
					                                            }
					                                            ');

					            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					                "Content-Type: application/json",
					                "Authorization: Basic ".$this->auth
					            ));

					            $response = curl_exec($ch);
					            $res = json_decode($response);

					            if(!empty($res)){
					            	 // Check start_time.
                                	if(!empty($res->start_time)){
                                		$picked_up_time = strtotime(''.date("Y-m-d").' '.$res->start_time.'');
	                                    // Check time work from send24.com
	                                    if($start_time < $picked_up_time && $end_time > $picked_up_time){
											$rate = array(
												'id'    => $this->id.'_express',
												'label' => 'Send24 Express(ETA: '.$res->end_time.')',
												'cost'  => $coast_express
											);
											$this->add_rate($rate);
										}
									}
								}
							}
						}
				}
	  		}
		}

  	}
}
