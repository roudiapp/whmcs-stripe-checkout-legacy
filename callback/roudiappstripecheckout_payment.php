<?php
////////////////////////////////////////////////////
// WHMCS Payment gateway module for Stripe Checkout 
//
// Copyright (C) 2006 - 2018  RoudiApp.com - Bermouda Ltd.
//
// License:  license is commercial with the source code distributed under the GNU General Public License version 3.
// https://roudiapp.com/terms.html
// For support: hello@roudiapp.com
// Version 4.2
////////////////////////////////////////////////////

if(!class_exists('Stripe\Stripe')){
	require_once("../stripe674/init.php");
}

use Stripe\Util\Util as Util;

// WHMCS Required File Includes
include("../../../init.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");

if(isset($_POST['stripeToken'])){

	$gatewaymodule = "roudiappstripecheckout";

	$GATEWAY = getGatewayVariables($gatewaymodule);
	if (!$GATEWAY["type"]) die("Module Not Activated"); // Checks gateway module is active before accepting callback

	//get amount and invoice id
	$amount = intval((int)$_POST['amount']);
	$invoiceid = intval((int)$_POST['invoiceid']);

	//get publishable key 
	if($GATEWAY['testmode']){
		$publishablekey = $GATEWAY["testpublishablekey"];
    	$secretkey = $GATEWAY["testsecretkey"];
    } else {
    	$publishablekey = $GATEWAY["publishablekey"];
    	$secretkey = $GATEWAY["secretkey"];
    }

    //check if authorise payment is enabled, then set capture to false
    $authorisepayment = ($GATEWAY['authorisepayment'] == 'on')? false:true;

    //set user id
    $userid = intval((int)$_POST['userid']);

	//build the sha1 key to ensure the checkout amount is correct
	$mostripesha1_key = sha1("totalamount=".$amount."mostripeshakey=".$GATEWAY['roudiappsha'].$publishablekey.$invoiceid);

	if($mostripesha1_key != $_POST['mostripeshakey']) die("Paid amount does not match the invoice amount.");


	 //set Stripe secret key
    \Stripe\Stripe::setApiKey($secretkey);
    
	//create customer
	try{
		$customerprofile = \Stripe\Customer::create(array(
													"source" => $_POST['stripeToken'],
													"email" => $_POST['custemail'],
												  	"description" => $GATEWAY['itemdesc'])
												  	);
		
		//convert stripe response from json to object and then array
		Util::convertStripeObjectToArray(Util::convertToStripeObject($customerprofile, ""));
												  	
		// Charge the customer instead of the card
		$customercharge = \Stripe\Charge::create(array(
							  "amount" => $amount,
							  "currency" => $_POST['currency'],
							  "customer" => $customerprofile->id,
							  "capture" => $authorisepayment,
							  "description" => $GATEWAY['roudiappinvoicetag'] . $invoiceid)
						  	);
		
		//if authorise only
		if(!$authorisepayment){
			//authorise payment only
			if($customercharge->outcome->type == "authorized"){
				if($customercharge->status == "succeeded"){
					// Get Returned Variables
					$amount = $customercharge->amount / 100;

					$transaction_data = json_decode($customercharge, true);
					
					$transid = $customercharge->id;

					$invoiceid = checkCbInvoiceID($invoiceid,$GATEWAY["name"]); // Checks invoice ID is a valid invoice number or ends processing

					checkCbTransID($transid); // Checks transaction number isn't already in the database and ends processing if it does

					// Successful
					addInvoicePayment($invoiceid,$transid,$amount,0,$gatewaymodule); // Apply Payment to Invoice: invoiceid, transactionid, amount paid, fees, modulename
					logTransaction($GATEWAY["name"],$customercharge,"Successful"); // Save to Gateway Log: name, data array, status
					
					header( "Location: " . $CONFIG["SystemURL"] . ( "/viewinvoice.php?id=" . $invoiceid . "&paymentsuccess=true" ) );
					exit();
					return 1;
				}
			} else {
				// Unsuccessful
				logTransaction($GATEWAY["name"],$transaction_data,"Unsuccessful"); // Save to Gateway Log: name, data array, status
				echo "Sorry, something went wrong, please check your card balance or details and try again.";
				header( "Location: " . $CONFIG["SystemURL"] . ( "/viewinvoice.php?id=" . $invoiceid . "&paymentfailed=true" ) );
			}

		} else {
			//capture payment
			if($customercharge->captured){
				if($customercharge->paid){
					

					// Get Returned Variables
					$amount = $customercharge->amount / 100;

					$transaction_data = json_decode($customercharge, true);
					
					$transid = $customercharge->id;
					$balance_transaction = \Stripe\BalanceTransaction::retrieve($customercharge->balance_transaction);
			    	$chargefee = $balance_transaction->fee / 100;

					$invoiceid = checkCbInvoiceID($invoiceid,$GATEWAY["name"]); // Checks invoice ID is a valid invoice number or ends processing

					checkCbTransID($transid); // Checks transaction number isn't already in the database and ends processing if it does

					// Successful
					addInvoicePayment($invoiceid,$transid,$amount,$chargefee,$gatewaymodule); // Apply Payment to Invoice: invoiceid, transactionid, amount paid, fees, modulename
					logTransaction($GATEWAY["name"],$transaction_data,"Successful"); // Save to Gateway Log: name, data array, status
					
					header( "Location: " . $CONFIG["SystemURL"] . ( "/viewinvoice.php?id=" . $invoiceid . "&paymentsuccess=true" ) );
					exit();
					return 1;

				}
			} else {
				// Unsuccessful
				logTransaction($GATEWAY["name"],$transaction_data,"Unsuccessful"); // Save to Gateway Log: name, data array, status
				echo "Sorry, something went wrong, please check your card balance or details and try again.";
				header( "Location: " . $CONFIG["SystemURL"] . ( "/viewinvoice.php?id=" . $invoiceid . "&paymentfailed=true" ) );
			}
		}		  	
		
	} catch(Exception $e) {
		$error = $e->getMessage();
		echo "Sorry, there was an error: ". $error;
		header( "Location: " . $CONFIG["SystemURL"] . ( "/viewinvoice.php?id=" . $invoiceid . "&paymentfailed=true" ) );
	}
	
} else {
	header( "Location: " . $CONFIG["SystemURL"] . "/clientarea.php" );
}
?>