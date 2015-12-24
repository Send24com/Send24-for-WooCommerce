<?php

class Send24
{
	public static $coast;
	public static $price;
	public static $auth;
	public static $send24_settings;
	public static $price_express;
	public static $category_danmark = 'Danmark';
	public static $category_express = 'Ekspres';
	public static $send24_product_id;
	private static $initiated = false;

	public static function init()
	{
		if (!self::$initiated)
			{
				self::init_hooks();
				self::check_woocommerce();
				self::$send24_settings = get_option('send24_settings');
	  			self::$auth = base64_encode(self::$send24_settings['c_key'].':'.self::$send24_settings['c_secret']);
			}
	}

	// Initializes WordPress hooks.
	private static function init_hooks()
	{
		session_start();
		self::$initiated = true;
		add_filter( 'woocommerce_ship_to_different_address_checked', '__return_true' );
		add_filter( 'plugin_action_links_'.S_PLUGIN_BASENAME, array('Send24', 'plugin_settings_link'));
		add_filter( 'woocommerce_create_account_default_checked', '__return_true');

		add_action('admin_enqueue_scripts', array('Send24', 'load_resources_backend'));
		add_action('wp_enqueue_scripts', array('Send24', 'load_resources_frontend'));
		add_action('woocommerce_checkout_update_order_meta',  array('Send24', 'add_cart_weight'));
		add_action( 'wp_ajax_check_postcode', array('Send24', 'check_postcode'));
		add_action( 'wp_ajax_nopriv_check_postcode', array('Send24', 'check_postcode'));
		add_action('woocommerce_checkout_order_processed', array('Send24', 'update_order'));
		add_action('add_meta_boxes',function(){
		    add_meta_box('custom_print_out', 'Printout', array('Send24', 'custom_print_out'), 'shop_order', 'side');
		});
		add_action('woocommerce_email_customer_details', array('Send24', 'add_order_email_instructions'), 10, 3);

		add_action( 'woocommerce_review_order_after_order_total', array('Send24', 'action_woocommerce_review_order_after_order_total'), 10, 0 );
		add_action( 'wp_ajax_show_shops',  array('Send24', 'action_woocommerce_review_order_after_order_total') );
		add_action( 'wp_ajax_nopriv_show_shops',  array('Send24', 'action_woocommerce_review_order_after_order_total') );

		// Open map - shops.
		add_action( 'wp_ajax_show_map',  array('Send24', 'action_show_map'));
		add_action( 'wp_ajax_nopriv_show_map',  array('Send24', 'action_show_map') );
		// Select - shops.
		add_action( 'wp_ajax_select_shops',  array('Send24', 'action_select_shops'));
		add_action( 'wp_ajax_nopriv_select_shops',  array('Send24', 'action_select_shops') );
		// Select - coupons.
		add_action( 'wp_ajax_select_coupons',  array('Send24', 'action_select_coupons'));
		add_action( 'wp_ajax_nopriv_select_coupons',  array('Send24', 'action_select_coupons') );
		// Order processed.
		add_action( 'woocommerce_checkout_order_processed',  array('Send24', 'action_woocommerce_checkout_order_processed'), 10, 2 );
		add_action( 'wp_footer',  array('Send24', 'add_in_footer_scriptmap'));
		// Save billing_email.
		add_action( 'wp_ajax_save_billing_emails',  array('Send24', 'action_save_billing_email'));
		add_action( 'wp_ajax_nopriv_save_billing_email',  array('Send24', 'action_save_billing_email') );

		add_action('admin_menu', array('Send24', 'register_my_submenu_page'));

		// Fixed updated debug status.
		$status_options = get_option( 'woocommerce_status_options', array());
		if(empty($status_options)){
			update_option('woocommerce_status_options', array('shipping_debug_mode' => 1));
		}
		// Check status woo shipping.
		$woo_shipping = get_option('woocommerce_calc_shipping');
		if($woo_shipping == 'no'){
			update_option('woocommerce_calc_shipping', 'yes');
		}
	}


	// Print shopw. 
	public static function custom_print_out($post){
		$order = new WC_Order($post->ID);
		$order_print = get_post_meta($order->id, 'response_send24');
		$result = unserialize($order_print['0']);

		if(!empty($result['link_to_pdf'])){
			echo '<a href="'.$result['link_to_pdf'].'" class="link_print">pdf</a>';
		}
		if(!empty($result['link_to_doc'])){
			echo '<a href="'.$result['link_to_doc'].'" class="link_print">doc</a>';
		}		
		if(!empty($result['link_to_zpl'])){
			echo '<a href="'.$result['link_to_zpl'].'" class="link_print">zpl</a>';
		}
		if(!empty($result['link_to_epl'])){
			echo '<a href="'.$result['link_to_epl'].'" class="link_print">epl</a>';
		}
	}


	public static function register_my_submenu_page() {
		add_submenu_page( 'woocommerce', 'Send24', 'Send24', 'manage_options', 'admin.php?page=wc-settings&tab=shipping&section=wc_send24_shipping_method');
	}

	public static function add_order_email_instructions($order, $sent_to_admin, $plain_text) {
		if(!$sent_to_admin){
			$order_print = get_post_meta($order->id, 'response_send24');
			$result = unserialize($order_print['0']);
			// Track.
			if(!empty($result['track'])){
				if(self::$send24_settings['enabled_track'] == 'yes'){
					echo '<table cellspacing="0" cellpadding="6" style="width: 100%;font-family: &prime;Helvetica Neue&prime;, Helvetica, Roboto, Arial, sans-serif;color: #737373;border: 1px solid #e4e4e4;border-top: none;" border="1">
							<tbody>
							<tr>
							<th scope="row" colspan="2" style="text-align: left;color: #737373;border: 1px solid #e4e4e4;padding: 12px;">Track:</th>
							<td style="text-align: left;color: #737373;border: 1px solid #e4e4e4;padding: 12px;"><span><a href="'.$result['track'].'" target="_blank">'.$result['track'].'</a></span></td>
							</tr>
							</tbody>
						</table>';
				}
			}
			// Return link.
      		if(self::$send24_settings['enabled_mail_return'] == 'yes'){
      		$return_link = self::get_return_link(self::$auth);
      		if($return_link != false){
      			$return_link_send24 = get_post_meta($order->id, 'return_link_send24');
      				echo '<table cellspacing="0" cellpadding="6" style="width: 100%;font-family: &prime;Helvetica Neue&prime;, Helvetica, Roboto, Arial, sans-serif;color: #737373;border: 1px solid #e4e4e4;border-top: none;" border="1">
						<tbody>
						<tr>
							<th scope="row" colspan="2" style="text-align: left;color: #737373;border: 1px solid #e4e4e4;padding: 12px;">Return:</th>
							<td style="text-align: left;color: #737373;border: 1px solid #e4e4e4;padding: 12px;"><span>'.$return_link_send24['0'].'</span></td>
						</tr>
						</tbody>
					</table>';
      			}
      		}
		}
	}

	// Update order.
	public static function update_order($post_id){
		$order = new WC_Order($post_id);
		$billing_phone = get_post_meta($order->id, '_billing_phone', true);
		$is_available = false;
		// Check country.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://send24.com/wc-api/v3/get_products");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			"Content-Type: application/json",
			"Authorization: Basic " . self::$auth
			));
		$send24_countries = json_decode(curl_exec($ch));

		curl_close($ch);
		$n = count($send24_countries);

		// Get shipping method.
		$shipping = $order->get_items('shipping');
      	foreach ($shipping as $key => $value) {
      		$shipping_method = $value['method_id'];
      		$shipping_coast = $value['cost'];
      	}

       	// send24_shipping_express
		for ($i = 0; $i < $n; $i++)
		{
			// Shipping Express.
			$select_country = WC()->countries->countries[$order->shipping_country];
			if($shipping_method == 'send24_shipping_express'){
				$select_country = 'Ekspres';
			}
			// Shipping Danmark.
			if($select_country == 'Denmark'){
				// Check Danmark.
				if(self::$send24_settings['enabled_danmark'] == 'no'){
					if($send24_countries[$i]->category_name != self::$category_danmark){
						if(self::$send24_settings['enabled_express'] == 'no'){
							$select_country = 'none';
						}else{
							$select_country = 'Danmark';
						}
				 	}
				}else{
					if($send24_countries[$i]->category_name == self::$category_danmark){
						$select_country = 'Danmark';
						$select_category_danmark = $send24_countries[$i]->product_id;
					}
				}
			}
  		}

		for ($i = 0; $i < $n; $i++)
		{
			if ($send24_countries[$i]->title == $select_country)
			{	
				self::$coast = $send24_countries[$i]->price;
				$res['id'] = $send24_countries[$i]->product_id;
				self::$send24_product_id = $res['id'];
			
				$i = $n;
				$is_available = true;

			}else{ 
				$res['msg'] = 'error';
			}
		}	
		if(!empty($select_category_danmark)){
			self::$send24_product_id = $select_category_danmark;
		}

       	// Check value.
		if(empty($order->shipping_company)){ $order->shipping_company = ''; }
		if(empty($order->shipping_first_name)){ $order->shipping_last_name = ''; }
		if(empty($billing_phone)){ $billing_phone = ''; }

		if(is_user_logged_in()){
  			$user_id = get_current_user_id();
  			$billing_email = get_user_meta($user_id, 'billing_email', true);
  		}else{
  			$billing_email = $_SESSION['send24_billing_email'];
  		}


		if(empty($order->shipping_city)){ $order->shipping_city = ''; }
		if(empty($order->shipping_postcode)){ $order->shipping_postcode = ''; }

		// Save order on send24.
	    if($is_available == true){      
        	// Create order. 
        	if(!empty(self::$send24_settings['insurance_field'])){
        		if($select_country == 'Danmark'){
  					$insurance_price = preg_replace('/[^0-9]/', '', self::$send24_settings['insurance_field']);
  				}else{
  					$insurance_price = 0;
  				}
  			}else{
  				$insurance_price = 0;
  			}

  			// Where shop id.
  			if(!empty($_SESSION['id_shop'])){
  				$where_shop_id = $_SESSION['id_shop'];
  			}else{
  				$where_shop_id = '';
  			}
  			// Fix right country.
  			if($select_country == 'Ekspres'){ $select_country = 'Danmark'; $where_shop_id = 'ekspres'; }

  			$ship_total = $price_need = '';

	  		$get_points_sales = get_option('send24_points_sales');

  			if(self::$send24_settings['enabled_coupons'] == 'no' && $get_points_sales['points_sales_enabled'] == 'no'){
  				$discount = "false";
  			}else{
  				$discount = "true";
  			}
  			$type = '';

  			// Check service points - get origin price. 
	  		if($get_points_sales['points_sales_enabled'] == 'on' && empty($_SESSION['enable_coupon'])){
	  			if(!empty($_SESSION['price_need'])){
	  				$price_need = base64_decode($_SESSION['price_need']);
	  			    $ship_total	= $shipping_coast+$price_need;
	  			    $type = 'points';
	  			}

  				if($where_shop_id == 'ekspres'){
  					$price_need = base64_decode($_SESSION['price_need_express']);
  					$ship_total	= $shipping_coast+$price_need;
	  			    $type = 'points';
  				}
	  		}

	  		// Check service - Coupons.
	  		if(self::$send24_settings['enabled_coupons'] == 'yes'){
  				if(!empty($_SESSION['enable_coupon_selected']) && base64_decode($_SESSION['enable_coupon_selected']) == 'send24-yes-coupons'){
  					$price_need = base64_decode($_SESSION['price_need']);
	  				if($where_shop_id == 'ekspres'){
	  					$price_need = base64_decode($_SESSION['price_need_express']);
	  				}
  					$ship_total = $shipping_coast+$price_need;
  					// $discount = "true";
  					$type = 'coupon';
  					$type .= '", "coupon_code": "'.$_SESSION['enable_coupon'].'';
  				}
  			}

  			// Create order.
	        $ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://send24.com/wc-api/v3/create_order");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, '
											{
											"TO_company": "'.$order->shipping_company.'",
											"TO_first_name": "'.$order->shipping_first_name.'",
											"TO_last_name": "'.$order->shipping_last_name.'",
											"TO_phone": "'.$billing_phone.'",
											"TO_email": "'.$billing_email.'",
											"TO_country": "'.$select_country.'",
											"TO_city": "'.$order->shipping_city.'",
											"TO_postcode": "'.$order->shipping_postcode.'",
											"Insurance" : "'.$insurance_price.'",
											"Weight": "5",
											"TO_address": "'.$order->shipping_address_1.'",
											"WHAT_product_id": "'.self::$send24_product_id.'",
											"WHERE_shop_id": "'.$where_shop_id.'",
											"discount": "'.$discount.'",
											"type": "'.$type.'",
											"need_points": "'.$price_need.'",
											"total": "'.$ship_total.'",
											"ship_mail": "'.$billing_email.'",
											"bill_mail": "'.$billing_email.'"
											}
											');

			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				"Authorization: Basic " . self::$auth,
				"Content-Type: application/json",
			));
			$response = curl_exec($ch);

			curl_close($ch);

			$response_order = json_decode($response, JSON_FORCE_OBJECT);
			$result_response = serialize($response_order);

      		update_post_meta($order->id, 'response_send24', $result_response);
	      	// Return link.
      		if(self::$send24_settings['enabled_mail_return'] == 'yes'){
	      		$return_link = self::get_return_link(self::$auth);
	      		if($return_link != false){
	      			update_post_meta($order->id, 'return_link_send24', $return_link);
	      		}
	      	}
			// Add note.
	        $note  = wp_kses_post('<strong>Track parsel </strong><br><a href="'.$response_order['track'].'" target="_blank">'.$response_order['track'].'</a>');
	        $order->add_order_note($note, 2);

		}
	}

	// Removes unnecessary.
	public static function action_woocommerce_checkout_order_processed(){
		unset($_SESSION['enable_coupon']);
		unset($_SESSION['enable_coupon_selected']);
		unset($_SESSION['price_need']);
		unset($_SESSION['price_need_express']);
	}

	// Get return link.
	public static function get_return_link($auth){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://send24.com/wc-api/v3/get_user_id");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			"Content-Type: application/json",
			"Authorization: Basic " . $auth
			));
		$user_meta = json_decode(curl_exec($ch));
		if(!empty($user_meta->return_activate)){
			$result_return = $user_meta->return_webpage_link['0'];
		}
		curl_close($ch);
		if(!empty($result_return)){
			return $return_service = '<a id="link_return" href="'.$result_return.'" target="_blank">'.$result_return.'</a>';
		}else{
			return false;
		}
	}

	// Woocommerce check.
	public static function check_woocommerce(){
		if (!in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' )))){
			// Check activate woo.
			add_action( 'admin_notices', function () {
				$class = "send24_notice";
				$message = 'To work <strong>Send24</strong> need to <a href="'.admin_url( 'plugins.php').'">activate</a> the plugin WooCommerce.';
			    echo "<div class=\"$class\"> <p>$message</p></div>"; 
			}); 
		}else{
			// Woocommerce action.
			add_action( 'woocommerce_shipping_init',  array('Send24', 'send24_shipping_method_init' )); 
			// Woocommerce filter.
			add_filter( 'woocommerce_shipping_methods',  array('Send24', 'add_send24_shipping_method')); 

		}
	} 
	
	// Init class shipping.
	public static function send24_shipping_method_init(){ 
		require_once(S_PLUGIN_CLASS .'class.shipping_method.php'); 
	}
	
	// Add method shipping.
	public static function add_send24_shipping_method($methods) {
		 $methods[] = 'WC_Send24_Shipping_Method';
		 return $methods;
	} 

	// Add link.
	public static function plugin_settings_link($links){
		$plugin_links = '<a href="' . admin_url('admin.php?page=wc-settings&tab=shipping&section=wc_send24_shipping_method').'">Settings</a>';
		array_unshift($links, $plugin_links);
		return $links; 
	}

	// Activate plugin.
	public static function plugin_activation(){}
	
	// Uninstal plugin.
	public static function plugin_uninstall(){}

	// Load resources for backend. 
	public static function load_resources_backend()
	{
		wp_register_style( 's-backend', S_PLUGIN_URL.'assets/css/backend-style.css', array(), S_VERSION); 
	 	wp_enqueue_style( 's-backend' ); 
	 	wp_register_script('send24-backend', S_PLUGIN_URL.'assets/js/backend-main.js', array('jquery'), S_VERSION);
	    wp_enqueue_script('send24-backend');
	}

	// Load resources for frontend. 
	public static function load_resources_frontend(){
		wp_register_script('send24-frontend', S_PLUGIN_URL.'assets/js/frontend-main.js', array('jquery'), S_VERSION);
	    wp_enqueue_script('send24-frontend');
	    wp_localize_script( 'send24-frontend', 'MyAjax', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' )
		));

		wp_register_style( 'send24-popup', S_PLUGIN_URL.'assets/css/popup.css', array(), S_VERSION); 
	 	wp_enqueue_style( 'send24-popup' ); 
	}

	// Checl postcode.
	public static function check_postcode(){
		extract($_POST);
		// Check zip.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://send24.com/wc-api/v3/get_service_area/".$zip);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			"Content-Type: application/json",
			"Authorization: Basic ".self::$auth
			));
		$zip_area = curl_exec($ch);
		echo $zip_area;
		curl_close($ch);
		die();
	}

	public static function add_cart_weight($order_id) {
        global $woocommerce;
        $woocommerce->cart->calculate_totals();
        $weight = $woocommerce->cart->cart_contents_weight;
        update_post_meta($order_id, '_cart_weight', $weight);
    }

    // Sort arr. 
	public static function sort_distance($a, $b) {
	    if ($a['distance'] == $b['distance']) {
	        return 0;
	    }
	    return ($a['distance'] < $b['distance']) ? -1 : 1;
	}

	// Define the woocommerce_review_order_after_order_total callback(Get pickup list).
	public static function action_woocommerce_review_order_after_order_total() 
	{		
		$data = array();
		if(!empty($_POST)){
			extract($_POST);
			$data['address'] = $address;
			$data['zip'] = $postcode;
			$data['city'] = $city;
			$data['country'] = $country;
	
			$val = json_encode($data);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://send24.com/wc-api/v3/pickups_list");
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $val);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				"Authorization: Basic " . self::$auth,
				"Content-Type: application/json",
			));

			$response = json_decode(curl_exec($ch));
			if(!empty($response)){
				$res = array();
				foreach ($response as $key => $value){
					$a = (array)$value;
					$res[$key] = $a;
				}

				uasort($res, array('Send24', 'sort_distance'));
				$output = array_slice($res, 0, 7);

				curl_close($ch);
				$map_value = '[';
				foreach ($output as $key => $value) {
					$map_value .= '{lat: &apos;'.$value['latitude'].'&apos;, lng: &apos;'.$value['longitude'].'&apos;, title: &apos;'.$value['post_title'].'&apos;, id: &apos;'.$value['ID'].'&apos;},';
				}
				$map_value .= ']';
			}
		}
		// Get shipping.
		$chosen_shipping_methods = WC()->session->chosen_shipping_methods['0'];
		// Select shop.
		if(!empty($output)){
			// Check 
			if($chosen_shipping_methods == 'send24_shipping'){
				//	Show shops.
				echo '<tr class="select_shop"><th>Select shops</th><td>';
				if(self::$send24_settings['show_shops'] == 'select'){
					echo '<select id="send24_select_shops" name="shops"><option>- select shops -</option>';
					foreach ($output as $key => $value){
						echo '<option value="'.$value['ID'].'">'.$value['post_title'].'</option>';
					}
					echo '</select>';
				}else{
					echo '<a rel="'.$map_value.'" id="send24_map" href="#open_map">select on map</a><div class="send34_map_selected"></div>';
					?><a href="#" class="overlay" id="open_map"></a>
				      <div class="send24_popup" id="send24-popup-map">
				      <div id="map"></div>
				      <div id="send24_info_map"></div>
				      <div id="send24_selected_shop"></div>
				      </div><?php
				}
				echo '</td></tr>';
			}
		}

		// Coupons show
		// If active coupons 
		if(self::$send24_settings['enabled_coupons'] == 'yes'){
			if($chosen_shipping_methods == 'send24_shipping' || $chosen_shipping_methods == 'send24_shipping_express'){
				if(!empty($_SESSION['enable_coupon'])){
					echo '<tr class="select_discount"><th>Discount Send24:</th><td>';
					echo '<p id="coupons_block">Please enter coupons:<span id="check_valid_coupon" style="color: rgb(19, 142, 91);"> - success</span><br><input name="enter_coupons" id="enter_coupons" maxlength="19" value="'.$_SESSION['enable_coupon'].'"></p>';
					echo '</td></tr>';
					$_SESSION['enable_coupon_selected'] = base64_encode('send24-yes-coupons');
				}else{
					echo '<tr class="select_discount"><th>Discount Send24:</th><td>';
					echo '<p id="coupons_block">Please enter coupons:<span id="check_valid_coupon"></span><br><input name="enter_coupons" id="enter_coupons" maxlength="19"></p>';
					echo '</td></tr>';
				}
			}
		}
	}

	// Check cuopons.
	public static function action_select_coupons(){
		extract($_POST);
		// Check coupons.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://send24.com/wc-api/v3/check_coupon_code/".$coupon);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);

		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			"Authorization: Basic " . self::$auth,
			"Content-Type: application/json",
		));
		$response = curl_exec($ch);
		curl_close($ch);
		if($response == 'true'){
			$_SESSION['enable_coupon'] = $coupon;
		}else{
			unset($_SESSION['enable_coupon']);
		}
		echo $response;
		die;
	}

	// Get information shop - map.
	public static function action_show_map(){
		extract($_POST);
		$_SESSION['id_shop'] = $id;
		// Get info shop.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://send24.com/wc-api/v3/get_shop_full/".$id);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);

		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				"Authorization: Basic " . self::$auth,
				"Content-Type: application/json",
			));

		$shop = curl_exec($ch);
		curl_close($ch);
		// Get rating. 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://send24.com/wc-api/v3/get_rating_shop/".$id);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			"Authorization: Basic " . self::$auth,
			"Content-Type: application/json",
		));
		$rating = curl_exec($ch);
		curl_close($ch);

		$response = array(
			'shop_info' => $shop,
			'rating_info' => $rating,
			);
		echo json_encode($response);
		die;
	}

	// Select shop.
	public static function action_select_shops(){
		extract($_POST);
		$_SESSION['id_shop'] = $id;
		die;
	}

	// Save billing email.
	public function action_save_billing_email(){
		extract($_POST);
		$_SESSION['send24_billing_email'] = $billing_email;
		die;
	}

	// Add lib google map.
	public static function add_in_footer_scriptmap() {
	    echo '<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyC6foJNfczaYhv9CnaPcdr9TVNJiNuvmvY&signed_in=true&libraries=places&callback=initMap" async defer></script>';
	}
	
}

