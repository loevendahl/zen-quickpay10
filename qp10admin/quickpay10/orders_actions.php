<?php
 $oID = '';
 
if (zen_not_null($action)) {
	  $oID = $_GET['oID'];
      $amount = $_POST['amount_big'] . $_POST['amount_small'];
  
    switch ($action) {

        case 'quickpay_reverse':
          
            $result = get_quickpay_reverse($oID);

            zen_redirect(zen_href_link(FILENAME_ORDERS, zen_get_all_get_params(array('action')) . 'action=edit'));
            break;

        case 'quickpay_capture':
         
            if (!isset($_POST['amount_big']) || $_POST['amount_big'] == '' || $_POST['amount_big'] == 0) {
                zen_redirect(zen_href_link(FILENAME_ORDERS, zen_get_all_get_params(array('action')) . 'action=edit'));
            }
		    
		    $result = get_quickpay_capture($oID, $amount);

            zen_redirect(zen_href_link(FILENAME_ORDERS, zen_get_all_get_params(array('action')) . 'action=edit'));
            break;
        case 'quickpay_credit':
            
			
			$result = get_quickpay_credit($oID, $amount);

            zen_redirect(zen_href_link(FILENAME_ORDERS, zen_get_all_get_params(array('action')) . 'action=edit'));
            break;

    }
}


?>