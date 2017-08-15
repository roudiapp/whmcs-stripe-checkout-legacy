<?php
////////////////////////////////////////////////////
// WHMCS Payment gateway module for Stripe Checkout 
//
// Copyright (C) 2006 - 2017  RoudiApp.com - Bermouda Ltd.
//
// License:  license is commercial with the source code distributed under the GNU General Public License version 3.
// https://roudiapp.com/terms.html
// For support: hello@roudiapp.com
// Version 4
////////////////////////////////////////////////////

if (!defined( "WHMCS" )) {
	exit( "This file cannot be accessed directly" );
}

require_once("stripe512/init.php");
use Stripe\Util\Util as Util;

function roudiappstripecheckout_config() {
    $configarray = array(
     "FriendlyName" => array("Type" => "System", "Value"=>"RoudiApp Stripe Checkout"),
     "publishablekey" => array("FriendlyName" => "Live Publishable Key", "Type" => "text", "Size" => "45", ),
     "secretkey" => array("FriendlyName" => "Live Secret Key", "Type" => "text", "Size" => "45", ),
     "testpublishablekey" => array("FriendlyName" => "Test Publishable Key", "Type" => "text", "Size" => "45", ),
     "testsecretkey" => array("FriendlyName" => "Test Secret Key", "Type" => "text", "Size" => "45", ),
     "roudiapplogo" => array("FriendlyName" => "Site Logo", "Value"=>"https://yourdomain.com/logo.png", "Description" => "Logo on Strip Checkout Form, include https.", "Type" => "text", "Size" => "100", ),
     "maindomain" => array("FriendlyName" => "WHMCS Installation", "Value"=>"https://yourdomain.com/billing", "Description" => "URL to WHMCS installation, no forward slash at the end.", "Type" => "text", "Size" => "100", ),
     "bizname" => array("FriendlyName" => "Business name", "Value"=>"RoudiApp", "Description" => "Your business name on stripe checkout form.", "Type" => "text", "Size" => "45", ),
     "itemdesc" => array("FriendlyName" => "Item Description", "Value"=>"Company Services", "Description" => "Generic item description on stripe checkout form.", "Type" => "text", "Size" => "45", ),
     "roudiappsha" => array("FriendlyName" => "SHA Key", "Value"=>"somethinghere", "Description" => "This key is used for transaction encryption. Use mix of alphabet and numbers with no space.", "Type" => "text", "Size" => "45", ),
     "roudiappinvoicetag" => array("FriendlyName" => "Invoice Tag", "Value"=>"domain.com INVOICE # ", "Description" => "This will accompany invoice id on stripe record for easier tracking.", "Type" => "text", "Size" => "35", ),
     "roudiappbutton" => array("FriendlyName" => "Pay Button Text", "Value"=>"Pay Now", "Description" => "", "Type" => "text", "Size" => "45", ),
     "testmode" => array("FriendlyName" => "Test Mode", "Type" => "yesno", "Description" => "Enable Test Mode", ),
     "bitcoin" => array("FriendlyName" => "Bitcoin", "Type" => "yesno", "Description" => "Enable Bitcoin", ),
     "locale" => array("FriendlyName" => "Stripe Checkout Language", "Type" => "text", "Size" => "5", "Value"=>"en", "Description" => "Supported languages are: Simplified Chinese (zh), Dutch (nl), English (en), French (fr), German (de), Italian (it), Japanese (ja), Spanish (es)", ),
	 "currencycode" => array("FriendlyName" => "Currency Code", "Type" => "text", "Size" => "5", "Value"=>"gbp", "Description" => "Supports all currencies supported by Stripe.", ),
	 "addresscheck" => array("FriendlyName" => "Address Check", "Type" => "yesno", "Description" => "Enable address fields in stripe checkout form.", ),
	 "transactionfeeper" => array("FriendlyName" => "Transaction Fee %", "Type" => "text", "Size" => "5", "Value"=>"2.75", "Description" => "Value in percentage.", ),
	 "transactionfeefix" => array("FriendlyName" => "Transaction Fee Fix", "Type" => "text", "Size" => "5", "Value"=>"20", "Description" => "Fix value in pence or cents.", ),
     "instructions" => array("FriendlyName" => "Payment Instructions", "Type" => "textarea", "Rows" => "7", "Description" => "The moStripe Checkout payment module (100% PCI OK) and its SMS Delivery services are developed and provided by Bermouda Limited. PomidorCart.com, Pomidka.com are Trading names of Bermouda Limited.", ),
     );
	return $configarray;
}

function roudiappstripecheckout_link($params) {

	//get publishable key 
	if($params['testmode']){
    	$publishablekey = $params["testpublishablekey"];
    } else {
    	$publishablekey = $params["publishablekey"];
    }

    //set Stripe secret key
    \Stripe\Stripe::setApiKey($secretkey);

	# Invoice Variables
	$invoiceid = $params['invoiceid'];
	$amount = $params['amount'] * 100; # must be in pence/cents

	/*if($params['currencycode'] == strtolower($params['currency']))
	{
		$currency = strtolower($params['currencycode']); # moStripe Currency Code 
	} else {
		$currency = $params['currency']; # WHMCS Currency Code
	}*/

	$currency = strtolower($params['currency']); # WHMCS Currency Code

	//check if we should set the address fields in stripe checkout form
	if($params['addresscheck']){
		$addresscheck_status = "true";
	}	else {
		$addresscheck_status = "false";
	}
	
	//check if bitcoin is enabled
	$bitcoin = "false";
	if($params['bitcoin']){
		$bitcoin = "true";
	}
	
	//check if alipay is enabled
	$alipay = "false";
	if($params['alipay']){
		$alipay = "true";
	}

	//build the sha1 key to ensure the checkout amount is correct
	$mostripesha1_key = sha1("totalamount=".$amount."mostripeshakey=".$params['roudiappsha'].$publishablekey.$invoiceid);

	$code = '<!-- MoStripe New buy process -->
	<br /><br />
	<style>
		.paynow {
			-moz-box-shadow: 0px 1px 0px 0px #f0f7fa;
			-webkit-box-shadow: 0px 1px 0px 0px #f0f7fa;
			box-shadow: 0px 1px 0px 0px #f0f7fa;
			background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #33bdef), color-stop(1, #019ad2));
			background:-moz-linear-gradient(top, #33bdef 5%, #019ad2 100%);
			background:-webkit-linear-gradient(top, #33bdef 5%, #019ad2 100%);
			background:-o-linear-gradient(top, #33bdef 5%, #019ad2 100%);
			background:-ms-linear-gradient(top, #33bdef 5%, #019ad2 100%);
			background:linear-gradient(to bottom, #33bdef 5%, #019ad2 100%);
			filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=#33bdef, endColorstr=#019ad2,GradientType=0);
			background-color:#33bdef;
			-moz-border-radius:6px;
			-webkit-border-radius:6px;
			border-radius:6px;
			border:1px solid #057fd0;
			display:inline-block;
			cursor:pointer;
			color:#ffffff;
			font-family:Arial;
			font-size:20px;
			font-weight:bold;
			padding:6px 24px;
			text-decoration:none;
			text-shadow:0px -1px 0px #5b6178;
		}
		.paynow:hover {
			background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #019ad2), color-stop(1, #33bdef));
			background:-moz-linear-gradient(top, #019ad2 5%, #33bdef 100%);
			background:-webkit-linear-gradient(top, #019ad2 5%, #33bdef 100%);
			background:-o-linear-gradient(top, #019ad2 5%, #33bdef 100%);
			background:-ms-linear-gradient(top, #019ad2 5%, #33bdef 100%);
			background:linear-gradient(to bottom, #019ad2 5%, #33bdef 100%);
			filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=#019ad2, endColorstr=#33bdef,GradientType=0);
			background-color:#019ad2;
		}
		.paynow:active {
			position:relative;
			top:1px;
		}
		
	</style>
	<script src="https://code.jquery.com/jquery-1.10.2.min.js"></script>
	<script src="https://checkout.stripe.com/checkout.js"></script>
	<p><button id="mostripeOBT" class="paynow" title="moStripe Pay Now">'. $params['roudiappbutton'] .'</button></p>
	
	<form action="'. $params['maindomain'] .'/modules/gateways/callback/roudiappstripecheckout_payment.php" method="post" id="roudifrm">
		<input type="hidden" name="invoiceid" value="'. $invoiceid .'" />
		<input type="hidden" name="amount" value="'. $amount .'" />
		<input type="hidden" name="mostripeshakey" value="'. $mostripesha1_key .'" />
		<input type="hidden" name="currency" value="'. $currency .'" />
		<input type="hidden" name="userid" value="'. $params["clientdetails"]["userid"] .'" />
		
	<script type="text/javascript">
		jQuery(function($) {
		    $(\'#mostripeOBT\').click(function(){
		      var token = function(res){
		        var $token = $(\'<input type=hidden name=stripeToken />\').val(res.id);
		        var $acctype = $(\'<input type=hidden name=acctype />\').val(res.type);
		        var $custemail = $(\'<input type=hidden name=custemail />\').val(res.email);

		        $(\'form#roudifrm\').append($acctype);
		        $(\'form#roudifrm\').append($custemail);
		        $(\'form#roudifrm\').append($token).submit();
		      };

		      var handler = StripeCheckout.configure({
		    	key:         \''. $publishablekey .'\',
		    	locale: \''. $params['locale'] .'\'
		  	  });
		
		      handler.open({
		      	key:         \''. $publishablekey .'\',
		    	image: \''. $params['roudiapplogo'] .'\',
		        billingAddress:     '. $addresscheck_status .',
		        amount:      '. $amount .',
		        currency:    \''. $currency .'\',
		        name: \''. $params['bizname'] .'\',
		      	description: \''. $params['itemdesc'] . $invoiceid .'\',
		        panelLabel:  \''. $params['roudiappbutton'] .'\',
		        bitcoin: '. $bitcoin .',
		        token:       token
		      });
		
		      return false;
		    });
		});
	</script>
	</form>
	<!-- MoStripe New buy process ends -->';
			
	return $code;
}


function roudiappstripecheckout_refund($params) {

    //get publishable key 
	if($params['testmode']){
    	$secretkey = $params["testsecretkey"];
    } else {
    	$secretkey = $params["secretkey"];
    }

    //set Stripe secret key
    \Stripe\Stripe::setApiKey($secretkey);

    # Invoice Variables
	$transid = $params['transid']; # Transaction ID of Original Payment
	$amount = $params['amount'] * 100; # must be in pence/cents
    $currency = $params['currency']; # Currency Code

    $results = array();

	try{

    	// Refund a transaction
    	
    	if($amount > 0 && !empty($transid)){
    		//refund partial amount by providing the amount value
    		//refund customer
    		$refundedcharge = \Stripe\Refund::create(array(
    													"amount" => $amount,
    													"charge" => $transid	
    												));
    	}
    	
    	//refund object is returned if the refund is successful.
    	if($refundedcharge->status === "succeeded"){
    	    $results["transid"] = $refundedcharge->id;
			$results["status"] = "success";
		} else {
			$results["status"] = "error";
		}

    } catch(\Stripe\Error\Card $e) {
		// Stripe_CardError will be caught
		$body = $e->getJsonBody();
		$httpstatuscode = $e->getHttpStatus();
		$err  = $body['error'];
		if($err['code'] == "card_declined"){
			$results["status"] = "declined";
		} else {
			$results["status"] = "error";
		}
	} catch (\Stripe\Error\InvalidRequest $e) {
	  // Invalid parameters were supplied to Stripe's API
	  //$body = $e->getJsonBody();
	  $httpstatuscode = $e->getHttpStatus();
	  $results["status"] = "error";
	} catch (\Stripe\Error\Authentication $e) {
	  // Authentication with Stripe's API failed
	  //$body = $e->getJsonBody();
	  $httpstatuscode = $e->getHttpStatus();
	  $results["status"] = "error";
	} catch (\Stripe\Error\ApiConnection $e) {
	  // Network communication with Stripe failed
	  //$body = $e->getJsonBody();
	  $httpstatuscode = $e->getHttpStatus();
	  $results["status"] = "error";
	} catch (\Stripe\Error\Base $e) {
	  //$body = $e->getJsonBody();
	  $httpstatuscode = $e->getHttpStatus();
	  $results["status"] = "error";
	} catch (Exception $e) {
	  // Something else happened, completely unrelated to Stripe
	  //$body = $e->getJsonBody();
	  $httpstatuscode = $e->getHttpStatus();
	  $results["status"] = "error";
	}

	# Return Results
	if ($results["status"]=="success") {
		return array("status"=>"success","transid"=>$results["transid"],"rawdata"=>$refundedcharge);
	} elseif ($results["status"]=="declined") {
        return array("status"=>"declined","rawdata"=>$e ." #Status Code: ". $httpstatuscode);
    } else {
		return array("status"=>"error","rawdata"=>$e ." #Status Code: ". $httpstatuscode);
	}

}

?>