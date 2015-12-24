<table class="form-table service-send24" style="<?php echo $visibility; ?>">
<tbody>
<tr valign="top" id="service_options">
	<th scope="row" class="titledesc">Services</th>
	<td class="forminp">
		<table class="wc_shipping widefat wp-list-table" cellspacing="0">
					<thead>
						<tr>
							<th class="sort">&nbsp;</th>
							<th class="service_code">Service Code</th>
							<th class="service_name">Name</th>
							<th class="service_enabled">Enabled</th>
							<th class="standart_price">Standart price</th>
							<th class="your_price">Your price</th>
						</tr>
					</thead>
					<tbody class="ui-sortable">
					<form action="">
					<?php 
						$send24_settings = get_option('send24_settings');
		 				$auth = base64_encode($send24_settings['c_key'].':'.$send24_settings['c_secret']);

						if(!empty($_POST)){
							// Points sales.	
							// Check you_percent
							if($_POST['points_sales_you_percent'] > 7){
								$_POST['points_sales_you_percent'] = 7;
							}elseif($_POST['points_sales_you_percent'] < 1){
								$_POST['points_sales_you_percent'] = 1;
							}
							if(empty($_POST['points_sales_enabled'])){
								$_POST['points_sales_enabled'] = 'none';
							}
							$points_sales = array(
								'points_sales_name' => $_POST['points_sales_name'], 
								'points_sales_enabled' => $_POST['points_sales_enabled'], 
								'points_sales_standart_percent' => '7', 
								'points_sales_you_percent' => $_POST['points_sales_you_percent'], 
							);

							$get_points_sales = get_option('send24_points_sales');

							if(!empty($get_points_sales)){
								update_option('send24_points_sales', $points_sales);
								// Set discount.
								if(!empty($points_sales['points_sales_you_percent'])){
									$discount_percent = $points_sales['points_sales_you_percent'];
								}else{
									$discount_percent = $points_sales['points_sales_standart_percent'];
								}
						  		//$discount_percent = $get_points_sales['points_sales_you_percent'];
								$ch = curl_init();
								curl_setopt($ch, CURLOPT_URL, "https://send24.com/wc-api/v3/set_shop_discount");
								curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
								curl_setopt($ch, CURLOPT_HEADER, FALSE);
								curl_setopt($ch, CURLOPT_POST, TRUE);
								curl_setopt($ch, CURLOPT_POSTFIELDS, '
																{
																"Percent": "'.$discount_percent.'"
																}
																');
								curl_setopt($ch, CURLOPT_HTTPHEADER, array(
									"Authorization: Basic " . $auth,
									"Content-Type: application/json",
								));
								$response = curl_exec($ch);
								curl_close($ch);
							}else{
								add_option('send24_points_sales', $points_sales);
								// Set discount.
						  		if(!empty($points_sales['points_sales_you_percent'])){
									$discount_percent = $points_sales['points_sales_you_percent'];
								}else{
									$discount_percent = $points_sales['points_sales_standart_percent'];
								}
								$ch = curl_init();
								curl_setopt($ch, CURLOPT_URL, "https://send24.com/wc-api/v3/set_shop_discount");
								curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
								curl_setopt($ch, CURLOPT_HEADER, FALSE);
								curl_setopt($ch, CURLOPT_POST, TRUE);
								curl_setopt($ch, CURLOPT_POSTFIELDS, '
																{
																"Percent": "'.$discount_percent.'"
																}
																');
								curl_setopt($ch, CURLOPT_HTTPHEADER, array(
									"Authorization: Basic " . $auth,
									"Content-Type: application/json",
								));
								$response = curl_exec($ch);
								curl_close($ch);
							}
							//////// end points sales //////////

						}
						// Get service.
						$get_points_sales = get_option('send24_points_sales');

					?>
						<tr>
							<td width="1%" class="">
							</td>
							<td class="service_code">
								Points Sales
							</td>
							<td width="1%" class="service_name">
								<input type="text" name="points_sales_name" value="<?php echo $get_points_sales['points_sales_name'];?>" placeholder="sevice name">
							</td>
							<td class="service_enabled">	
								<input type="checkbox" name="points_sales_enabled" <?php if($get_points_sales['points_sales_enabled'] == 'on'){ echo 'checked'; }?>>							
							</td>
							<td class="standart_price">
								<input type="text" name="points_sales_standart_percent" value="<?php echo $get_points_sales['points_sales_standart_percent'];?>" disabled="disabled" placeholder="7">%							
							</td>
							<td class="your_price">
								<input type="number" max="7" min="1" name="points_sales_you_percent" id="points_sales_you_percent" value="<?php echo $get_points_sales['points_sales_you_percent'];?>" placeholder="7%">%
							</td>
						</tr>
					</form>											
					</tbody>
					
				</table>
	</td>
</tr>
</tbody>
</table>