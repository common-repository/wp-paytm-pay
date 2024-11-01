<?php
global $wpdb;
extract(
    array(
        'paytm_merchant_id' => get_option('paytm_merchant_id'),
        'paytm_merchant_key' => get_option('paytm_merchant_key'),
        'paytm_website' => get_option('paytm_website'),
        'paytm_industry_type_id' => get_option('paytm_industry_type_id'),
        'paytm_channel_id' => get_option('paytm_channel_id'),
        'paytm_mode' => get_option('paytm_mode'),
        'paytm_amount' => get_option('paytm_amount'),					 							
        'paytm_thanks_page_url' => get_option('paytm_thanks_page_url')					 							
    )
);

$isSuccess = false;
if(verifychecksum_e($_POST,$paytm_merchant_key,$_POST['CHECKSUMHASH']) === "TRUE"){ 
	if($_POST['RESPCODE'] == "01"){
		// Create an array having all required parameters for status query.
		$requestParamList = array("MID" => $paytm_merchant_id , "ORDERID" => $_POST['ORDERID']);
		
		// Call the PG's getTxnStatus() function for verifying the transaction status.
		$check_status_url = 'https://securegw-stage.paytm.in/order/status';
		if($paytm_mode == 'LIVE'){
			$check_status_url = 'https://securegw.paytm.in/order/status';
		}

		$responseParamList = callAPI($check_status_url, $requestParamList);
        if($responseParamList['STATUS'] == 'TXN_SUCCESS' && $responseParamList['TXNAMOUNT'] == $_POST['TXNAMOUNT']){
			$returnOrderId = $_POST['ORDERID'];
			$wpdb->query(" UPDATE ".$wpdb->prefix . "paytm_donation SET payment_status = 'Complete Payment' WHERE  order_id = $returnOrderId "); 

			$msg = "Thank you for donation. Your transaction has been successful.";
			$isSuccess = true;
		} else {
			$returnOrderId = $_POST['ORDERID'];
			$wpdb->query(" UPDATE ".$wpdb->prefix . "paytm_donation SET payment_status = 'Fraud Payment' WHERE  order_id = $returnOrderId "); 

			$msg = "Sorry. The transaction has been Failed";
		}
	} else {
		$returnOrderId = $_POST['ORDERID'];
		$wpdb->query(" UPDATE ".$wpdb->prefix . "paytm_donation SET payment_status = 'Canceled Payment' WHERE  order_id = $returnOrderId "); 

		$msg = "Sorry. The transaction has been Failed For Reason  : "  . sanitize_text_field($_POST['RESPMSG']);
	}
} else {
	$returnOrderId = $_POST['ORDERID'];
	$wpdb->query(" UPDATE ".$wpdb->prefix . "paytm_donation SET payment_status = 'Payment Error' WHERE  order_id = $returnOrderId "); 

	$msg = "Security error!";
}

$redirect_url = get_permalink(get_the_ID());
if($isSuccess){
	if(trim($paytm_thanks_page_url) != ''){
		$redirect_url = trim($paytm_thanks_page_url);
	}
}

$redirect_after_payment = add_query_arg( array('donation_msg' => urlencode($msg)), $redirect_url);
echo "<script type='text/javascript'>document.location='".$redirect_after_payment."';</script>";
exit();
?>