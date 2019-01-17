<?php

//--------------------------------- ACTIONS ------------------------------------
add_action ('wp_loaded', 'give_payping_do_after_wp_load');
function give_payping_do_after_wp_load() {
	if ( isset( $_SESSION['redirect-to'] )) {
		if ($redirectTo = $_SESSION['redirect-to']) {
			$_SESSION['redirect-to'] = NULL;
			wp_redirect($redirectTo);
			exit();
		}
	}
}

add_action( 'give_payping_cc_form', 'give_payping_payping_cc_form' );
function give_payping_payping_cc_form()
{
	return false;
}

add_action( 'give_gateway_payping', 'give_payping_process_payping_purchase' );
function give_payping_process_payping_purchase( $purchase_data )
{
	global $givePaypingOptions;
	$settings = get_option('givePaypingOptions', $givePaypingOptions);

	if (!isset($_POST['ResCode'])){
		if ( ! wp_verify_nonce( $purchase_data['gateway_nonce'], 'give-gateway' ) ) {
			wp_die( esc_html__( 'Nonce verification has failed.', 'give' ), esc_html__( 'Error', 'give' ), array( 'response' => 403 ) );
		}

		$form_id = intval( $purchase_data['post_data']['give-form-id'] );
		$price_id = isset($purchase_data['post_data']['give-price-id']) ? $purchase_data['post_data']['give-price-id'] : '';

		// Collect payment data
		$payment_data = array(
			'price'           => $purchase_data['price'],
			'give_form_title' => $purchase_data['post_data']['give-form-title'],
			'give_form_id'    => $form_id,
			'give_price_id'   => $price_id,
			'date'            => $purchase_data['date'],
			'user_email'      => $purchase_data['user_email'],
			'purchase_key'    => $purchase_data['purchase_key'],
			'currency'        => give_get_currency(),
			'user_info'       => $purchase_data['user_info'],
			'status'          => 'pending',
			'gateway'         => 'payping'
		);

		// Record the pending payment
		$payment = give_insert_payment( $payment_data );

		// Check payment
		if ( ! $payment ) {
			// Record the error
			give_record_gateway_error(
				esc_html__( 'Payment Error', 'give' ),
				sprintf(
				/* translators: %s: payment data */
					esc_html__( 'Payment creation failed before sending buyer to payping. Payment data: %s', 'give' ),
					json_encode( $payment_data )
				),
				$payment
			);
			// Problems? send back
			give_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['give-gateway'] );

		} else {
			// Only send to payping if the pending payment is created successfully
			$listener_url = add_query_arg( 'give-listener', 'IPN', home_url( 'index.php' ) );

			// Get the success url
			$return_url = add_query_arg( array(
				'payment-confirmation' => 'payping',
				'payment-id'           => $payment

			), get_permalink( give_get_option( 'success_page' ) ) );

			// Get the payping redirect uri
			//$payping_redirect = trailingslashit( give_payping_get_payping_redirect() ) . '?';

			//Item name - pass level name if variable priced
			$item_name = $purchase_data['post_data']['give-form-title'];

			//Verify has variable prices
			if ( give_has_variable_prices( $form_id ) && isset( $purchase_data['post_data']['give-price-id'] ) ) {

				$item_price_level_text = give_get_price_option_name( $form_id, $purchase_data['post_data']['give-price-id'] );

				$price_level_amount = give_get_price_option_amount( $form_id, $purchase_data['post_data']['give-price-id'] );

				//Donation given doesn't match selected level (must be a custom amount)
				if ( $price_level_amount != give_sanitize_amount( $purchase_data['price'] ) ) {
					$custom_amount_text = get_post_meta( $form_id, '_give_custom_amount_text', true );
					//user custom amount text if any, fallback to default if not
					$item_name .= ' - ' . ( ! empty( $custom_amount_text ) ? $custom_amount_text : esc_html__( 'Custom Amount', 'give' ) );

				} //Is there any donation level text?
				elseif ( ! empty( $item_price_level_text ) ) {
					$item_name .= ' - ' . $item_price_level_text;
				}

			} //Single donation: Custom Amount
			elseif ( give_get_form_price( $form_id ) !== give_sanitize_amount( $purchase_data['price'] ) ) {
				$custom_amount_text = get_post_meta( $form_id, '_give_custom_amount_text', true );
				//user custom amount text if any, fallback to default if not
				$item_name .= ' - ' . ( ! empty( $custom_amount_text ) ? $custom_amount_text : esc_html__( 'Custom Amount', 'give' ) );
			}





			function getCurrentURL(){
				$currentURL = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
				$currentURL .= $_SERVER["SERVER_NAME"];

				if($_SERVER["SERVER_PORT"] != "80" && $_SERVER["SERVER_PORT"] != "443")
				{
					$currentURL .= ":".$_SERVER["SERVER_PORT"];
				}

				$currentURL .= $_SERVER["REQUEST_URI"];
				return $currentURL;
			}


			$callBackUrl = get_permalink( $settings['paypingRedirectPage'] ) ;

			if ( give_get_currency() == 'IRR' )
				$Amount = $purchase_data['price'] / 10 ;
			else
				$Amount = $purchase_data['price']  ;
			$dataSend = array( 'Amount' => $Amount,'payerIdentity'=> $purchase_data['user_email'] , 'returnUrl' => $callBackUrl, 'Description' => $purchase_data['post_data']['give-form-title'] , 'clientRefId' => $payment );
			try {
				$curl = curl_init();
				curl_setopt_array($curl, array(CURLOPT_URL => "https://api.payping.ir/v1/pay", CURLOPT_RETURNTRANSFER => true, CURLOPT_ENCODING => "", CURLOPT_MAXREDIRS => 10, CURLOPT_TIMEOUT => 30, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_CUSTOMREQUEST => "POST", CURLOPT_POSTFIELDS => json_encode($dataSend), CURLOPT_HTTPHEADER => array("accept: application/json", "authorization: Bearer " . $settings['givePayping_PaypingG_Token'], "cache-control: no-cache", "content-type: application/json"),));
				$response = curl_exec($curl);
				$header = curl_getinfo($curl);
				$err = curl_error($curl);
				curl_close($curl);
				if ($err) {
					echo "cURL Error #:" . $err;
				} else {
					if ($header['http_code'] == 200) {
						$response = json_decode($response, true);
						if (isset($response["code"]) and $response["code"] != '') {
							wp_redirect(sprintf('https://api.payping.ir/v1/pay/gotoipg/%s', $response["code"]));
							exit;
						} else {
							$Message = 'عدم وجود کد ارجاع ';
							$_SESSION['payping_massage'] = $Message ;
							wp_redirect(get_site_url() . '/payping-pay-failed');
						}
					} elseif ($header['http_code'] == 400) {
						$Message =  implode('. ',array_values (json_decode($response,true))) ;
						$_SESSION['payping_massage'] = $Message ;
						wp_redirect(get_site_url() . '/payping-pay-failed');
					} else {
						$Message = status_message($header['http_code']) . '(' . $header['http_code'] . ')';
						$_SESSION['payping_massage'] = $Message ;
						wp_redirect(get_site_url() . '/payping-pay-failed');
					}
				}
			} catch (Exception $e){
				$Message = $e->getMessage();
				$_SESSION['payping_massage'] = $Message ;
				wp_redirect(get_site_url() . '/payping-pay-failed');
			}

		}

	}
}

//---------------------------------- HOOKS -------------------------------------
add_filter('give_payment_gateways', 'give_payping_payment_gateways', 10, 3);
function give_payping_payment_gateways($gateways)
{
	if (!isset($gateways['payping'])) {
		$gateways['payping'] = array(
			'admin_label'    => esc_html__( 'payping Standard', 'give' ),
			'checkout_label' => esc_html__( 'payping', 'give' ),
			'supports'       => array( 'buy_now' )
		);
	}

	return $gateways;
}

if ( !function_exists('status_message')) {
	function status_message($code) {
		switch ($code) {
			case 200 :
				return 'عملیات با موفقیت انجام شد';
				break;
			case 400 :
				return 'مشکلی در ارسال درخواست وجود دارد';
				break;
			case 500 :
				return 'مشکلی در سرور رخ داده است';
				break;
			case 503 :
				return 'سرور در حال حاضر قادر به پاسخگویی نمی‌باشد';
				break;
			case 401 :
				return 'عدم دسترسی';
				break;
			case 403 :
				return 'دسترسی غیر مجاز';
				break;
			case 404 :
				return 'آیتم درخواستی مورد نظر موجود نمی‌باشد';
				break;
		}
	}
}



