<?php
function encrypt_data($input, $ky){
    $key   = html_entity_decode($ky);
    $iv = "@@@@&&&&####$$$$";
    $data = openssl_encrypt ( $input , "AES-128-CBC" , $key, 0, $iv );
    return $data;
}

function decrypt_data($crypt, $ky){
   $key   = html_entity_decode($ky);
    $iv = "@@@@&&&&####$$$$";
    $data = openssl_decrypt ( $crypt , "AES-128-CBC" , $key, 0, $iv );
    return $data;
}

function generateSalt_e($length){
    $random = "";
    srand((double) microtime() * 1000000);
    $data = "AbcDE123IJKLMN67QRSTUVWXYZ";
    $data .= "aBCdefghijklmn123opq45rs67tuv89wxyz";
    $data .= "0FGH45OP89";
    for ($i = 0; $i < $length; $i++) {
        $random .= substr($data, (rand() % (strlen($data))), 1);
    }
    return $random;
}

function checkString_data($value){
    $myvalue = ltrim($value);
    $myvalue = rtrim($myvalue);
    if ($myvalue == 'null')
        $myvalue = '';
    return $myvalue;
}

function getChecksumFromArray($arrayList, $key, $sort = 1){
    if($sort != 0){
        ksort($arrayList);
    }
    $str         = getArray2Str($arrayList);
    $salt        = generateSalt_e(4);
    $finalString = $str . "|" . $salt;
    $hash        = hash("sha256", $finalString);
    $hashString  = $hash . $salt;
    $checksum    = encrypt_data($hashString, $key);
    return $checksum;
}

function verifychecksum_e($arrayList, $key, $checksumvalue){
    $arrayList = removeCheckSumParam($arrayList);
    ksort($arrayList);
    $str        = getArray2Str($arrayList);
    $paytm_hash = decrypt_data($checksumvalue, $key);
    $salt       = substr($paytm_hash, -4);
    $finalString = $str . "|" . $salt;
    $website_hash = hash("sha256", $finalString);
    $website_hash .= $salt;
    $validFlag = "FALSE";

    if($website_hash == $paytm_hash){
        $validFlag = "TRUE";
    } else {
        $validFlag = "FALSE";
    }
    return $validFlag;
}

function getArray2Str($arrayList){
    $paramStr = "";
    $flag     = 1;
    foreach($arrayList as $key => $value){
        if($flag){
            $paramStr .= checkString_data($value);
            $flag = 0;
        } else {
            $paramStr .= "|" . checkString_data($value);
        }
    }
    return $paramStr;
}

function removeCheckSumParam($arrayList){
    if(isset($arrayList["CHECKSUMHASH"])){
        unset($arrayList["CHECKSUMHASH"]);
    }
    return $arrayList;
}

function callAPI($apiURL, $requestParamList){
    $jsonResponse      = "";
    $responseParamList = array();
    $JsonData          = json_encode($requestParamList);
    $apiURL = $apiURL.'?JsonData='.urlencode($JsonData);
    $response = wp_remote_get( $apiURL);
	$body = wp_remote_retrieve_body( $response );
    $responseParamList = json_decode($body, true);
    return $responseParamList;
}
?>