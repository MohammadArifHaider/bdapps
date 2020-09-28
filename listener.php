<?php

ini_set('error_log', 'ussd-app-error.log');
require 'bdapps_cass_sdk.php';

$appid = "APP_004858";
$apppassword = "afdcff5c2dbcb3ae7b68ced9172a068d";

 function connect(){
	$conn = mysqli_connect('127.0.0.1', 'root', '','test2');	
	return $conn;
}

 function checkSession($session){
	$conn = connect();
	
	$sqlSelect = "SELECT * FROM `session` WHERE sessionID = ".$session.";";
	$result = mysqli_query($conn, $sqlSelect);
	if (mysqli_num_rows($result) > 0){
		$row = mysqli_fetch_array($result);
		return $row['state'];
	}
	else{
		return 1;
	}
	
}

 function checkAmount($session){
	$conn = connect();
	
	$sqlSelect = "SELECT * FROM `session` WHERE sessionID = ".$session.";";
	$result = mysqli_query($conn, $sqlSelect);
	if (mysqli_num_rows($result) > 0){
		$row = mysqli_fetch_array($result);
		switch ($row['selected']) {
				case "1":
					return 200000;
					break;
				case "2":
					return 500000;
					break;
				case "3":
					return 1000000;
					break;
				case "4":
					return 2000000;
					break;
				case "5":
					return 5000000;
					break;
				default:
					return 1000;
					break;
		}
	}
	else{
		return 1;
	}
	
}

function generateToken(){
	
		$jsonStream = "grant_type=password&username=MIFE_BDApps&password=B%7C%29@Pp%24_O3To20&scope=PRODUCTION";
		$ch = curl_init("https://api.robi.com.bd/token");
		//echo $ch;
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Content-Type: application/x-www-form-urlencoded",
    "Authorization: BASIC UVRWYzY3NXpBdmNsVDlmYkRFZGkxSUV4TVE4YTpWYk42YWR6WTk1S3Zxc1JyMUtSam04c3Zpd2dh"
  ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStream);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
		$res = (explode('"',$res));
		curl_close($ch);
		return $res[3];
}

function charge($amount,$address,$session){
	$response = generateToken(); 
	$amount = $amount;
	$address = substr($address,7);
	$session = $session;
	$jsonStream ="Version=1&BusinessCode=1&MessageSeq=$session&BEID=101&OperatorID=351&PrimaryIdentity=$address&DeductSerialNo=12212444613445374&ChargeCode=C_FEE_DEDUCTION_CHARGE_CODE&ChargeAmt=$amount&CurrencyID=1012&AdditionalInfo=BDApps%20Donation&ChargeSeq=124124151316&SalesTime=".time()."&InvoiceTime=".time();

	$ch = curl_init("https://api.robi.com.bd/cbs/v1/cbsFeeDeduction");
		//echo $ch;
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Content-Type: application/x-www-form-urlencoded",
    "Authorization: Bearer $response"
  ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStream);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
		echo $res;
		curl_close($ch);
		return $res;
}

 function updateState($state,$sessionId){
	$conn = connect();
	if($state == 0 ){
		$sql = "INSERT INTO `session` (`id`, `sessionID`, `state`, `selected`, `timestamp`) VALUES (NULL, '".$sessionId."', '1', '0', CURRENT_TIMESTAMP);";
		$result = mysqli_query($conn, $sql);
	}
	else{
		$sql = "UPDATE `session` SET `state` = '2',`selected` = ".$state." WHERE `sessionID` = ".$sessionId.";";
		$result = mysqli_query($conn, $sql);
	}
}

$production=true;

	if($production==false){
		$ussdserverurl ='http://localhost:7000/ussd/send';
	}
	else{
		$ussdserverurl= 'https://developer.bdapps.com/ussd/send';
	}

try{
	$receiver 	= new UssdReceiver();
	$ussdSender = new UssdSender($ussdserverurl,$appid,$apppassword);
	$subscription = new Subscription('https://developer.bdapps.com/subscription/send',$apppassword,$appid);
	// file_put_contents('text.txt',$receiver->getRequestID());
	// $operations = new Operations();

	//$receiverSessionId  =   $receiver->getSessionId();
	$content 			= 	$receiver->getMessage(); // get the message content
	$address 			= 	$receiver->getAddress(); // get the ussdSender's address
	$requestId 			= 	$receiver->getRequestID(); // get the request ID
	$applicationId 		= 	$receiver->getApplicationId(); // get application ID
	$encoding 			=	$receiver->getEncoding(); // get the encoding value
	$version 			= 	$receiver->getVersion(); // get the version
	$sessionId 			= 	$receiver->getSessionId(); // get the session ID;
	$ussdOperation 		= 	$receiver->getUssdOperation(); // get the ussd operation

	$status = "REGISTERED";

	file_put_contents('sessionID.txt',$sessionId);

	$responseMsg = "Please select the amount you want to donate::\n1.20tk\n2.50tk\n3.100tk\n4.200tk\n5.500tk";


	if ($ussdOperation  == "mo-init") {
		try {
			$ussdSender->ussd($sessionId, $responseMsg,$address);
			updateState(0,$sessionId);
		} 
		catch (Exception $e) {
				$ussdSender->ussd($sessionId, 'Sorry error occured try again',$address );
		}
	}
	else {
		$state = checkSession($sessionId);
		
		if($state == 1){
			switch ($receiver->getMessage()) {
				case "1":
					$ussdSender->ussd($sessionId,"You are donating Tk.20. Press 1 to confirm.\n1. Confirm\n2. Reject",$address);
					updateState(1,$sessionId);
					break;
				case "2":
					$ussdSender->ussd($sessionId,"You are donating Tk.50. Press 1 to confirm.\n1. Confirm\n2. Reject",$address);
					updateState(2,$sessionId);
					break;
				case "3":
					$ussdSender->ussd($sessionId,"You are donating Tk.100. Press 1 to confirm.\n1. Confirm\n2. Reject",$address);
					updateState(3,$sessionId);
					break;
				case "4":
					$ussdSender->ussd($sessionId,"You are donating Tk.200. Press 1 to confirm.\n1. Confirm\n2. Reject",$address);
					updateState(4,$sessionId);
					break;
				case "5":
					$ussdSender->ussd($sessionId,"You are donating Tk.500. Press 1 to confirm.\n1. Confirm\n2. Reject",$address);
					updateState(5,$sessionId);
					break;
					
				default:
					$ussdSender->ussd($sessionId, $responseMsg,$address);
					break;
			}
		}
		else {
			switch ($receiver->getMessage()) {
				case "1":
					$amount = checkAmount($sessionId);
					$resMessage = charge($amount,$address,$sessionId);
					$ussdSender->ussd($sessionId,"We have received your donation. Thank you.",$address,'mt-fin');
					$myfile = fopen("charge.txt", "a+");
					fwrite($myfile, date("d-m-Y h:i:s").", Mobile no:  $address , $amount".$resMessage."\n");
					fclose($myfile);
					$server = "https://developer.bdapps.com/sms/send";
					$sender = new SMSSender($server,$appid,$apppassword);
					$amount = $amount / 10000;
					$sender->sms("We have received Tk. $amount as your donation. Thank you.",$address);
					break;
				case "2":
					$ussdSender->ussd($sessionId,"You did not donate yet. To donate please dial *213*002#.\nThank you.",$address,'mt-fin');
					break;
				default:
					$ussdSender->ussd($sessionId, $responseMsg,$address);
					break;
			}
		}
		
	}
}
catch (Exception $e){
 file_put_contents('USSDERROR.tct','Some error occured');   
}
?>



