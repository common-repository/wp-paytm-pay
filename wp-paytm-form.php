<?php
global $wpdb;
extract(
    array(
        'paytm_merchant_id' => trim(get_option('paytm_merchant_id')),
        'paytm_merchant_key' => trim(get_option('paytm_merchant_key')),
        'paytm_website' => trim(get_option('paytm_website')),
        'paytm_industry_type_id' => trim(get_option('paytm_industry_type_id')),
        'paytm_channel_id' => trim(get_option('paytm_channel_id')),
        'paytm_mode' => trim(get_option('paytm_mode')),
        'paytm_amount' => trim(get_option('paytm_amount')),		
        'paytm_content' => trim(get_option('paytm_content'))	 					
    )
);

if(isset($_POST['paytmcheckout'])){
	$valid = true;
	$html = '';
	$msg = ''; 

	if(trim($_POST['donor_name']) != ''){
		$donor_name = sanitize_text_field($_POST['donor_name']);
	}else{
		$valid = false;
		$msg .= 'Name is required</br>';
	}

	if(trim($_POST['donor_email']) != ''){
		$donor_email = sanitize_email($_POST['donor_email']);
		if( preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/" , $donor_email)){

		}else{
			$valid = false;
			$msg .= 'Invalid email format</br>';
		}
	} else {
		$valid = false;
		$msg .= 'E-mail is required</br>';
	}
		
	if(trim($_POST['donor_amount']) != ''){
		$donor_amount = $_POST['donor_amount']; 
		
		if(is_numeric($_POST['donor_amount'])){
			if($donor_amount < 1){
	            $valid = false;
	            $msg .= 'Amount cannot be less than 1</br>';        
	        } else if( $donor_amount >= 50000 && trim($_POST['donor_pan']) == ''){
	            $valid = false;
	            $msg .= 'Pan Card compulsory if amount greater than 50000.</br>';
	        }
		}else{
			$valid = false;
	        $msg .= 'Please enter valid amount.</br>';
		}
	} else {
		$valid = false;
		$msg .= 'Amount is required</br>'; 
	}
		
    if(trim($_POST['donor_pan']) != ''){    
        $value = $_POST['donor_pan']; //PUT YOUR PAN CARD NUMBER HERE
        $pattern = '/^([a-zA-Z]){5}([0-9]){4}([a-zA-Z]){1}?$/';
        $result = preg_match($pattern, $value);
        if($result){
            $findme = ucfirst(substr($value, 3, 1));
            $mystring = 'CPHFATBLJG';
            $pos = strpos($mystring, $findme);
            if($pos === false){
                $valid = false;    
                $msg .= "Pan Card Is Not valid</br>";
            }
        } else {
            $valid = false;    
            $msg .= "Pan Card Is Not valid</br>";
        }
    }
		
	if($valid){
		$table_name = $wpdb->prefix . "paytm_donation";
		$order_id = date('YmdHis'); //"ORDS" . rand(10000,99999999);
		$data = array(
            'name' => sanitize_text_field($_POST['donor_name']),
            'order_id' => sanitize_text_field($order_id),
            'email' => sanitize_text_field($_POST['donor_email']), 
            'phone' => sanitize_text_field($_POST['donor_phone']),
            'address' => sanitize_text_field($_POST['donor_address']),
            'city' => sanitize_text_field($_POST['donor_city']),
            'country' => sanitize_text_field($_POST['donor_country']),
            'state' => sanitize_text_field($_POST['donor_state']),
            'zip' => sanitize_text_field($_POST['donor_postal_code']),
            'amount' => sanitize_text_field($_POST['donor_amount']),
            'pan_no' => sanitize_text_field($_POST['donor_pan']),
            'payment_status' => 'Pending Payment',
            'date' => date('Y-m-d H:i:s'),
		);
		
		$wpdb->insert($table_name, $data);
		
		$post_params = array(
			'MID' => $paytm_merchant_id,
			'ORDER_ID' => $order_id,
			'WEBSITE' => $paytm_website,
			'CHANNEL_ID' => $paytm_channel_id,
			'INDUSTRY_TYPE_ID' => $paytm_industry_type_id,
			'TXN_AMOUNT' => sanitize_text_field($_POST['donor_amount']),
			'CUST_ID' => sanitize_text_field($_POST['donor_email']),
			'EMAIL' => sanitize_text_field($_POST['donor_email']), 
		);		

		
		$post_params["CALLBACK_URL"] = get_permalink();
		
		$checkSum = getChecksumFromArray($post_params,$paytm_merchant_key);
        $call = get_permalink();
        $action_url = "https://securegw-stage.paytm.in/order/process";
        if($paytm_mode == 'LIVE'){
            $action_url = "https://securegw.paytm.in/order/process";
        }

        $html= '<center>We’re processing your request. It may take more few seconds!<br />Please do not refresh or close this session.</center>
                <form method="post" action="'.$action_url.'" name="f1">
	                <table border="1">
                        <tbody>
                            <input type="hidden" name="MID" value="'.$paytm_merchant_id.'">
                            <input type="hidden" name="WEBSITE" value="'.$paytm_website.'">
                            <input type="hidden" name="CHANNEL_ID" value="'.$paytm_channel_id.'">
                            <input type="hidden" name="ORDER_ID" value="'.$order_id.'">
                            <input type="hidden" name="INDUSTRY_TYPE_ID" value="'.$paytm_industry_type_id.'">									
                            <input type="hidden" name="TXN_AMOUNT" value="'.$donor_amount.'">
                            <input type="hidden" name="CUST_ID" value="'.$donor_email.'">
                            <input type="hidden" name="EMAIL" value="'.$donor_email.'">
                            <input type="hidden" name="CALLBACK_URL" value="'.$call.'">
                            <input type="hidden" name="CHECKSUMHASH" value="'.$checkSum.'">
                        </tbody>
	                </table>
	                <script type="text/javascript">
	                    document.f1.submit();
	                </script>
            	</form>';		
		return $html;
	}else {
		return $msg;
	}
} else {
	$html = '<form name="frmTransaction" method="post">
				<p><label for="name"> Name:</label> <input type="text" name="donor_name"/></p>
				<p><label for="email"> Email:</label> <input type="text" name="donor_email"/></p>
				<p><label for="phone"> Phone:</label> <input type="text" name="donor_phone"/></p>
				<p><label for="amount"> Amount:</label> <input type="text" name="donor_amount" value="'.$paytm_amount.'"/></p>
                <p><label for="amount"> Pan No:</label> <input type="text" name="donor_pan"/></p>    
				<p><label for="address"> Address:</lable> <input type="text" name="donor_address"/></p>
				<p><label for="city"> City:</label> <input type="text" name="donor_city"/></p>
				<p><label for="state"> State:</label> <input type="text" name="donor_state"/></p>
				<p><label for="postal_code"> Postal Code:</lable> <input type="text" name="donor_postal_code"/></p>
				<p><label for="state"> Country:</label> <input type="text" name="donor_country"/></p>
				<input name="paytmcheckout" type="submit" value="' . $paytm_content .'"/>
			</form>';
	return $html;
}
?>