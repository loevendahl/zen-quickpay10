<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

// Only show quickpay transaction when we have an transacctionid from payment gateway
// Also check if we can access QuickPay API (only when Curl extentions are loaded)
	$subcription = false;

if(strstr($order->info['cc_cardhash'],"Subscription")){
	$subcription = true;
	$api->mode ="subscriptions";

	
}
if (zen_not_null($order->info['cc_transactionid']) && $api->init()) {

try {

  $statusinfo = $api->status($order->info['cc_transactionid']); 
  $ostatus['amount'] = $statusinfo["operations"][0]["amount"];
  $ostatus['balance'] = $statusinfo["balance"];
  $ostatus['currency'] = $statusinfo["currency"];
  //get the latest operation
  $operations= array_reverse($statusinfo["operations"]);
  $amount = $operations[0]["amount"];
  $ostatus['qpstat'] = $operations[0]["qp_status_code"];
  $ostatus['type'] = $operations[0]["type"];
  $resttocap = $ostatus['amount'] - $ostatus['balance'];
  $resttorefund = $statusinfo["balance"];
  $allowcapture = ($operations[0]["pending"] ? false : true);
  $allowcancel = true;

  //allow split payments and split refunds
  if(($ostatus['type'] == "capture" ) ){
				
					$allowcancel = false;


	  }
    if(($ostatus['type'] == "refund" ) ){
			         
					$resttocap = 0;
	             

	  }
	  
	
  $ostatus['time'] = $operations[0]["created_at"];
  $ostatus['qpstatmsg'] = $operations[0]["qp_status_msg"];
  
} catch (Exception $e) {
  $error = $e->getCode(); // The code is the http status code
  $error .= $e->getMessage(); // The message is json

}	

//var_dump($statusinfo);



    ?>

    <tr>
        <td class="main"><b><?php echo ENTRY_QUICKPAY_TRANSACTION; ?></b></td>
        <td class="main"><?php

    if ($statusinfo) {
        $statustext = array();
        $statustext["capture"] = INFO_QUICKPAY_CAPTURED;
        $statustext["cancel"] = INFO_QUICKPAY_REVERSED;
        $statustext["refund"] = INFO_QUICKPAY_CREDITED;
	  	$formatamount= explode(',',number_format($amount/100,2,',','.'));
	    $amount_big = $formatamount[0];
        $amount_small = $formatamount[1];

	    switch ($ostatus['type']) {
            case 'authorize': // Authorized
			case 'renew': //-not implemented in this version

                echo zen_draw_form('transaction_form', FILENAME_ORDERS, zen_get_all_get_params(array('action')) . 'action=quickpay_capture');
                echo zen_draw_hidden_field('oID', $oID), zen_draw_hidden_field('currency', $ostatus['currency']);
               
				echo zen_draw_input_field('amount_big', $amount_big, 'size="11" style="text-align:right" ', false, 'text', false);
			    echo ' , ';
                echo zen_draw_input_field('amount_small', $amount_small, 'size="3" ', false, 'text', false) . ' ' . $ostatus['currency'] . ' ';
					
if($allowcapture){
				echo '<a href="javascript:if (qp_check_capture(' . str_replace('.','',$amount_big) . ', ' . $amount_small . ')) document.transaction_form.submit();">' . zen_image(DIR_WS_IMAGES . 'icon_transaction_capture.gif', IMAGE_TRANSACTION_CAPTURE_INFO) . '</a>';
}else{
	echo PENDING_STATUS;
	
}
                echo '</form>';
                echo zen_draw_form('transaction_decline_form', FILENAME_ORDERS, zen_get_all_get_params(array('action')) . 'action=quickpay_reverse', 'post');
				if($allowcancel){
                echo '<a href="javascript:if (qp_check_confirm(\'' . CONFIRM_REVERSE . '\')) document.transaction_decline_form.submit();">' . zen_image(DIR_WS_IMAGES . 'icon_transaction_reverse.gif', IMAGE_TRANSACTION_REVERSE_INFO) . '</a>';
				}
                echo '</form>';
                $sevendayspast = date('Y-m-d', time() - (7 * 24 * 60 * 60));
                if ($sevendayspast == substr($ostatus['time'], 0, 10)) {
                    echo zen_image(DIR_WS_IMAGES . 'icon_transaction_time_yellow.gif', IMAGE_TRANSACTION_TIME_INFO_YELLOW);
                } else if (strcmp($sevendayspast, substr($ostatus['time'], 0, 10)) > 0) {
                    echo zen_image(DIR_WS_IMAGES . 'icon_transaction_time_red.gif', IMAGE_TRANSACTION_TIME_INFO_RED);
                } else {
                    echo zen_image(DIR_WS_IMAGES . 'icon_transaction_time_green.gif', IMAGE_TRANSACTION_TIME_INFO_GREEN);
                }
                break;
            case 'capture': // Captured or refunded
			case 'refund':
		
	
		if($resttocap > 0 ){
			echo "<br><b>".IMAGE_TRANSACTION_CAPTURE_INFO."</b><br>";
			$formatamount= explode(',',number_format($resttocap/100,2,',','.'));
	    $amount_big = $formatamount[0];
        $amount_small = $formatamount[1];
	         echo zen_draw_form('transaction_form', FILENAME_ORDERS, zen_get_all_get_params(array('action')) . 'action=quickpay_capture');
                echo zen_draw_hidden_field('oID', $oID), zen_draw_hidden_field('currency', $ostatus['currency']);

				echo zen_draw_input_field('amount_big', $amount_big, 'size="11" style="text-align:right" ', false, 'text', false);
			    echo ' , ';
                echo zen_draw_input_field('amount_small', $amount_small, 'size="3" ', false, 'text', false) . ' ' . $ostatus['currency'] . ' ';


				echo '<a href="javascript:if (qp_check_capture(' . str_replace('.','',$amount_big) . ', ' . $amount_small . ')) document.transaction_form.submit();">' . zen_image(DIR_WS_IMAGES . 'icon_transaction_capture.gif', IMAGE_TRANSACTION_CAPTURE_INFO) . '</a>';
                echo '</form>';
                echo zen_draw_form('transaction_decline_form', FILENAME_ORDERS, zen_get_all_get_params(array('action')) . 'action=quickpay_reverse', 'post');
				if($allowcancel){
                echo '<a href="javascript:if (qp_check_confirm(\'' . CONFIRM_REVERSE . '\')) document.transaction_decline_form.submit();">' . zen_image(DIR_WS_IMAGES . 'icon_transaction_reverse.gif', IMAGE_TRANSACTION_REVERSE_INFO) . '</a>';
				}else{
					    echo zen_image(DIR_WS_IMAGES . 'icon_transaction_reverse_grey.gif', IMAGE_TRANSACTION_REVERSE_INFO);
				}
                echo '</form>';
                $sevendayspast = date('Y-m-d', time() - (7 * 24 * 60 * 60));
                if ($sevendayspast == substr($ostatus['time'], 0, 10)) {
                    echo zen_image(DIR_WS_IMAGES . 'icon_transaction_time_yellow.gif', IMAGE_TRANSACTION_TIME_INFO_YELLOW);
                } else if (strcmp($sevendayspast, substr($ostatus['time'], 0, 10)) > 0) {
                    echo zen_image(DIR_WS_IMAGES . 'icon_transaction_time_red.gif', IMAGE_TRANSACTION_TIME_INFO_RED);
                } else {
                    echo zen_image(DIR_WS_IMAGES . 'icon_transaction_time_green.gif', IMAGE_TRANSACTION_TIME_INFO_GREEN);
                }

	echo "<br><br>";
		}
			$formatamount= explode(',',number_format($resttorefund/100,2,',','.'));
	    $amount_big = $formatamount[0];
        $amount_small = $formatamount[1];	
			if($resttorefund > 0){
	
			echo "<b>".IMAGE_TRANSACTION_CREDIT_INFO."</b><br>";	
                echo zen_draw_form('transaction_refundform', FILENAME_ORDERS, zen_get_all_get_params(array('action')) . 'action=quickpay_credit');
                echo zen_draw_hidden_field('oID', $oID), zen_draw_hidden_field('currency', $ostatus['currency']);
             
                echo zen_draw_input_field('amount_big', $amount_big, 'size="11" style="text-align:right" ', false, 'text', false);
                echo ' , ';
                echo zen_draw_input_field('amount_small', $amount_small, 'size="3" ', false, 'text', false) . ' ' . $ostatus['currency'] . ' ';
               
			   
			    echo zen_image(DIR_WS_IMAGES . 'icon_transaction_capture_gr	ey.gif', IMAGE_TRANSACTION_CAPTURE_INFO);
              
			  
			    echo zen_image(DIR_WS_IMAGES . 'icon_transaction_reverse_grey.gif', IMAGE_TRANSACTION_REVERSE_INFO);
                echo '<a href="javascript:if (qp_check_confirm(\'' . CONFIRM_CREDIT . '\')) document.transaction_refundform.submit();">' . zen_image(DIR_WS_IMAGES . 'icon_transaction_credit.gif', IMAGE_TRANSACTION_CREDIT_INFO) . '</a>';
                echo '</form>';
                echo zen_image(DIR_WS_IMAGES . 'icon_transaction_time_grey.gif', '');
              
			}else{
	
				
				        echo zen_draw_input_field('amount_big', $amount_big, 'size="11" style="text-align:right" disabled', false, 'text', false);
                echo ' , ';
                echo zen_draw_input_field('amount_small', $amount_small, 'size="3" disabled', false, 'text', false) . ' ' . $ostatus['currency'] . ' ';
				 echo ' (' . $statustext[$ostatus['type']].')';
			}
                break;
            case 'cancel': // Reversed
			
			
           
                echo zen_draw_input_field('amount_big', $amount_big, 'size="11" style="text-align:right" disabled', false, 'text', false);
                echo ' , ';
                echo zen_draw_input_field('amount_small', $amount_small, 'size="3" disabled', false, 'text', false) . ' ' . $ostatus['currency'] . ' ';
                echo zen_image(DIR_WS_IMAGES . 'icon_transaction_capture_grey.gif', IMAGE_TRANSACTION_CAPTURE_INFO);
                echo zen_image(DIR_WS_IMAGES . 'icon_transaction_reverse_grey.gif', IMAGE_TRANSACTION_REVERSE_INFO);
                echo zen_image(DIR_WS_IMAGES . 'icon_transaction_time_grey.gif', '');
                echo ' (' . $statustext[$ostatus['type']] .')';
                break;
            default:
                echo '<font color="red">' . $ostatus['qpstatmsg'] . '</font>';
                break;
        }
    } else {
        echo '<font color="red">' . $ostatus['qpstatmsg'] . '</font>';
    }
	
	if($error){
		echo '<font color="red">' . $error . '</font>';
		}
    ?> </td>
    <tr>
        <td class="main" valign="top"><b><?php echo ENTRY_QUICKPAY_TRANSACTION_ID; ?></b></td>
        <td class="main"><?php echo $order->info['cc_transactionid']; ?></b></td>
    </tr>
        
    <tr>
        <td class="main"><b><?php echo ENTRY_QUICKPAY_CARDHASH; ?></b></td>
        <td class="main"><?php echo $order->info['cc_cardhash']; ?></b></td>
    </tr>
    <?php
}
?>