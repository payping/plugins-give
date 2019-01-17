<?php

add_shortcode('payping_redirect', 'payping_redirect_handler');
function payping_redirect_handler() {
    
    global $givePaypingOptions;
    $settings = get_option('givePaypingOptions', $givePaypingOptions);
    
    $payment_id   = $_GET['clientrefid'];




    $Amount = give_get_payment_amount($payment_id);

    if ( give_get_currency() == 'IRR' )
	    $Amount = $Amount / 10 ;
    $data = array('refId' => $_GET['refid'], 'amount' => $Amount);
    try {
	    $curl = curl_init();
	    curl_setopt_array($curl, array(
		    CURLOPT_URL => "https://api.payping.ir/v1/pay/verify",
		    CURLOPT_RETURNTRANSFER => true,
		    CURLOPT_ENCODING => "",
		    CURLOPT_MAXREDIRS => 10,
		    CURLOPT_TIMEOUT => 30,
		    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		    CURLOPT_CUSTOMREQUEST => "POST",
		    CURLOPT_POSTFIELDS => json_encode($data),
		    CURLOPT_HTTPHEADER => array(
			    "accept: application/json",
			    "authorization: Bearer ".$settings['givePayping_PaypingG_Token'],
			    "cache-control: no-cache",
			    "content-type: application/json",
		    ),
	    ));
	    $response = curl_exec($curl);
	    $err = curl_error($curl);
	    $header = curl_getinfo($curl);
	    curl_close($curl);

	    if ($err) {
		    give_update_payment_status( $payment_id, 'failed' );
		    $_SESSION['payping_massage'] = $err ;
		    $_SESSION['payping_refid'] = $_GET['refid'] ;
		    wp_redirect(get_permalink( $settings['paypingPayFailedPage'] ));
	    } else {
		    if ($header['http_code'] == 200) {
			    $response = json_decode($response, true);
			    if (isset($_GET["refid"]) and $_GET["refid"] != '') {
				    give_update_payment_status( $payment_id, 'publish' );
				    $_SESSION['payping_massage'] = 'پرداخت موفقیت آمیز بود.' ;
				    $_SESSION['payping_refid'] = $_GET['refid'] ;
				    wp_redirect(get_permalink( $settings['paypingPaySuccessPage'] ));
			    } else {
				    $Message = status_message($header['http_code']) . '(' . $header['http_code'] . ')' ;
				    give_update_payment_status( $payment_id, 'failed' );
				    $_SESSION['payping_massage'] = $Message ;
				    $_SESSION['payping_refid'] = $_GET['refid'] ;
				    wp_redirect(get_permalink( $settings['paypingPayFailedPage'] ));
			    }
		    } elseif ($header['http_code'] == 400) {
			    $Message =  implode('. ',array_values (json_decode($response,true))) ;
			    give_update_payment_status( $payment_id, 'failed' );
			    $_SESSION['payping_massage'] = $Message ;
			    $_SESSION['payping_refid'] = $_GET['refid'] ;

			    wp_redirect(get_permalink( $settings['paypingPayFailedPage'] ));
		    }  else {
			    $Message = status_message($header['http_code']) . '(' . $header['http_code'] . ')';
			    give_update_payment_status( $payment_id, 'failed' );
			    $_SESSION['payping_massage'] = $Message ;
			    $_SESSION['payping_refid'] = $_GET['refid'] ;
			    wp_redirect(get_permalink( $settings['paypingPayFailedPage'] ));
		    }
	    }
    } catch (Exception $e){
	    give_update_payment_status( $payment_id, 'failed' );
	    $_SESSION['payping_massage'] = $e->getMessage() ;
	    $_SESSION['payping_refid'] = $_GET['refid'] ;
	    wp_redirect(get_site_url() . '/payping-pay-failed');
    }

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

