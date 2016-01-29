<?php
/**
 * Checkout Process Page
 *
 * @package page
 * @copyright Copyright 2003-2010 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: header_php.php 17018 2010-07-27 07:25:41Z drbyte $
 */
// This should be first line of the script:
  $zco_notifier->notify('NOTIFY_HEADER_START_CHECKOUT_PROCESS');

   ini_set('display_errors','On');

ini_set('error_log',$_SERVER['DOCUMENT_ROOT'].'/error'.date('d-m-Y').'.log');
function sign($base, $private_key) {
  return hash_hmac("sha256", $base, $private_key);
}		  
//callback start			  
if($_SERVER["HTTP_QUICKPAY_CHECKSUM_SHA256"]){
$request_body = file_get_contents("php://input");
$checksum     = sign($request_body, MODULE_PAYMENT_QUICKPAY_ADVANCED_PRIVATEKEY);
 $str = json_decode($request_body,true);


 $qp_status = $str["operations"][0]["qp_status_code"];
 $qp_status_msg = $str["operations"][0]["qp_status_msg"];
 $qp_order_id = str_replace(MODULE_PAYMENT_QUICKPAY_ADVANCED_AGGREEMENTID."_","", $str["order_id"]);
 $qp_aq_status_code = $str["aq_status_code"];
 $qp_aq_status_msg = $str["aq_status_msg"];
  $qp_cardtype = $str["metadata"]["brand"];
  $qp_cardnumber = "xxxx-xxxxxx-".$str["metadata"]["last4"];
  $qp_amount = $str["operations"][0]["amount"];
  $qp_currency = $str["currency"];
  $qp_pending = ($str["pending"] == "true" ? " - pending ": "");
  $qp_expire = $str["metadata"]["exp_month"]."-".$str["metadata"]["exp_year"];
 
  $qp_cardhash = $str["operations"][0]["type"].(strstr($str["description"],'Subscription') ? " Subscription" : "");

    $transaction = $db->Execute("select cc_session from " . TABLE_ORDERS . " where orders_id = '" . $qp_order_id . "'");
    $sessdata = json_decode($transaction->fields['cc_session'], true);
$data="";
foreach($sessdata as $key=>$value){
	$data .= $key.'-'.$sessdata[$key];
	$_SESSION[$key] = $sessdata[$key];
}
	
//mail("kl@blkom.dk","checkout_proceshead", $request_body ."session_pay:".$_SESSION['payment']."order_id:".$_SESSION['order_id'].$sess."data:".$data);

 
 }

$qp_approved = false;
/*
20000	Approved
40000	Rejected By Acquirer
40001	Request Data Error
50000	Gateway Error
50300	Communications Error (with Acquirer)
*/
 if ($checksum == $_SERVER["HTTP_QUICKPAY_CHECKSUM_SHA256"]) {
   // Request is authenticated
   $qp_status = '20000';
 }
switch ($qp_status) {
    case '20000':
        // approved
        $qp_approved = true;
        break;
    case '40000':
	case '40001':
        // Error in request data.
        // write status message into order to retrieve it as error message on checkout_payment

        $sql_data_array = array('cc_transactionid' => zen_db_input($qp_status_msg),
            'last_modified' => 'now()');


        // approve order by updating status
        zen_db_perform(TABLE_ORDERS, $sql_data_array, 'update', "orders_id = '" . $qp_order_id . "'");


        $sql_data_array = array('orders_id' => $qp_order_id,
            'orders_status_id' => MODULE_PAYMENT_QUICKPAY_ADVANCED_REJECTED_ORDER_STATUS_ID,
            'date_added' => 'now()',
            'customer_notified' => '0',
            'comments' => 'QuickPay Payment rejected [message:' . $qp_status_msg . ' - '.$qp_aq_status_msg.']');

        zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

        break;

    default:

        $sql_data_array = array('cc_transactionid' => $qp_status,
            'last_modified' => 'now()');

        // approve order by updating status
        zen_db_perform(TABLE_ORDERS, $sql_data_array, 'update', "orders_id = '" . $qp_order_id . "'");


        $sql_data_array = array('orders_id' => $qp_order_id,
            'orders_status_id' => MODULE_PAYMENT_QUICKPAY_ADVANCED_REJECTED_ORDER_STATUS_ID,
            'date_added' => 'now()',
            'customer_notified' => '0',
            'comments' => 'QuickPay Payment rejected [status code:' . $qp_status . ']');

        zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

        break;
}

if ($qp_approved) {
    // payment approved


    $comment_status = "Transaction: ".$str["id"] . $qp_pending.' (' . $qp_cardtype . ' ' . $currencies->format($qp_amount / 100, false, $qp_currency) . ') '. $qp_status_msg;

            
			// set order status as configured in the module
            $order_status_id = (MODULE_PAYMENT_QUICKPAY_ADVANCED_ORDER_STATUS_ID > 0 ? (int) MODULE_PAYMENT_QUICKPAY_ADVANCED_ORDER_STATUS_ID : (int) DEFAULT_ORDERS_STATUS_ID);

            $sql_data_array = array('cc_transactionid' => $str["id"],
               'cc_type' => $qp_cardtype,
			   'cc_number' => $qp_cardnumber,
			    'cc_expires' => ($qp_expire ? $qp_expire : 'N/A'),
			    'cc_cardhash' => $qp_cardhash,
				'orders_status' => $order_status_id,
                'last_modified' => 'now()');
	
            // approve order by updating status
            zen_db_perform(TABLE_ORDERS, $sql_data_array, 'update', "orders_id = '" . $qp_order_id . "'");


            // write into order history
            $sql_data_array = array('orders_id' => $qp_order_id,
                'orders_status_id' => $order_status_id,
                'date_added' => 'now()',
                'customer_notified' => '0',
                'comments' => 'QuickPay Payment Verification successful [ ' . $comment_status . ']');

            zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

    }



		  
require(DIR_WS_MODULES . zen_get_module_directory('checkout_process.php'));

// load the after_process function from the payment modules
  $payment_modules->after_process();

  $_SESSION['cart']->reset(true);

// unregister session variables used during checkout
  unset($_SESSION['sendto']);
  unset($_SESSION['billto']);
  unset($_SESSION['shipping']);
  unset($_SESSION['payment']);
  unset($_SESSION['comments']);
  $order_total_modules->clear_posts();//ICW ADDED FOR CREDIT CLASS SYSTEM

  // This should be before the zen_redirect:
  $zco_notifier->notify('NOTIFY_HEADER_END_CHECKOUT_PROCESS');

  zen_redirect(zen_href_link(FILENAME_CHECKOUT_SUCCESS, (isset($_GET['action']) && $_GET['action'] == 'confirm' ? 'action=confirm' : ''), 'SSL'));

  require(DIR_WS_INCLUDES . 'application_bottom.php');
