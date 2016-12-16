<?php

require_once(DIR_FS_CATALOG.DIR_WS_CLASSES.'QuickpayApi.php');

/*
  quickpay.php

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2008 Jakob Høy Biegel
  Copyright (c) 2009 LAiKA iT
  Copyright (c) 2013 Kim Løvendahl
*/

class quickpay_advanced{

 var $code, $title, $description, $enabled, $creditcardgroup, $num_groups;


// class constructor
  function quickpay_advanced() {

       global $order,$cardlock;
             $message ="";


        //$this->signature = 'quickpay_advanced|quickpay_advanced|1.0|2.2'; // no longer supported?

        $this->code = 'quickpay_advanced';
        $this->title = MODULE_PAYMENT_QUICKPAY_ADVANCED_TEXT_TITLE;
        $this->public_title = MODULE_PAYMENT_QUICKPAY_ADVANCED_TEXT_PUBLIC_TITLE;
        $this->description = MODULE_PAYMENT_QUICKPAY_ADVANCED_TEXT_DESCRIPTION;
        $this->sort_order = MODULE_PAYMENT_QUICKPAY_ADVANCED_SORT_ORDER;
        $this->enabled = ((MODULE_PAYMENT_QUICKPAY_ADVANCED_STATUS == 'True') ? true : false);
        $this->creditcardgroup = array();
	    $this->email_footer = ($cardlock == "ibill" || $cardlock == "viabill" ? DENUNCIATION : '');
		
        // CUSTOMIZE THIS SETTING FOR THE NUMBER OF PAYMENT GROUPS NEEDED
        $this->num_groups = 5;

        if ((int) MODULE_PAYMENT_QUICKPAY_ADVANCED_PREPARE_ORDER_STATUS_ID > 0) {
            $this->order_status = MODULE_PAYMENT_QUICKPAY_ADVANCED_PREPARE_ORDER_STATUS_ID;
        }

        if (is_object($order))
            $this->update_status;

 
        // Store online payment options in local variable
        for ($i = 1; $i <= $this->num_groups; $i++) {
            if (defined('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i) && constant('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i) != '') {
              /*  if (!isset($this->creditcardgroup[constant('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i . '_FEE')])) {
                  $this->creditcardgroup[constant('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i . '_FEE')] = array();
                }*/
                $payment_options = preg_split('[\,\;]', constant('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i));
                foreach ($payment_options as $option) {
                   $msg .= $option;
               //     $this->creditcardgroup[constant('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i . '_FEE')][] = $option;
                }
           
		   
            }
			
        }
//V10       
           if($_POST['quickpayIT'] == "go" && !isset($_SESSION['qlink'])) { 
			$this->form_action_url = 'https://payment.quickpay.net/';
		   }else{
            $this->form_action_url = zen_href_link(FILENAME_CHECKOUT_CONFIRMATION, '', 'SSL');
		   }
   
    }

// class methods

    function update_status() {
        global $order, $quickpay_fee, $HTTP_POST_VARS, $qp_card;

    if (($this->enabled == true) && ((int)MODULE_PAYMENT_QUICKPAY_ZONE > 0) ) {

      $check_flag = false;
      $check = $db->Execute("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_QUICKPAY_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");

      while (!$check->EOF) {

        if ($check->fields['zone_id'] < 1) {
          $check_flag = true;
          break;
        } elseif ($check->fields['zone_id'] == $order->billing->fields['zone_id']) {
          $check_flag = true;
          break;
        }

        $check->MoveNext();
      }

      if ($check_flag == false) {
        $this->enabled = false;
      }

    }

    if (!isset($_SESSION['qp_card'])) {
      $_SESSION['qp_card'] = '';
    }

    if (isset($_POST['qp_card'])) {
      $_SESSION['qp_card'] = $_POST['qp_card'];
    }

    if (isset($_GET['cart_QuickPay_ID']))
    $_SESSION['cart_QuickPay_ID'] = $_GET['cart_QuickPay_ID'];
  
    if (!isset($_SESSION['quickpay_fee'])) {
      $_SESSION['quickpay_fee'] = '';
    }

  }




  function javascript_validation() {
      $js = ' if (payment_value == "' . $this->code . '") {' . "\n" .
                '     var qp_card_value = null;' . "\n" .
                '      if (document.checkout_payment.qp_card.length) {' . "\n" .
                '        for (var i=0; i<document.checkout_payment.qp_card.length; i++) {' . "\n" .
                '          if (document.checkout_payment.qp_card[i].checked) {' . "\n" .
                '            qp_card_value = document.checkout_payment.qp_card[i].value;' . "\n" .
                '          }' . "\n" .
                '        }' . "\n" .
                '      } else if (document.checkout_payment.qp_card.checked) {' . "\n" .
                '        qp_card_value = document.checkout_payment.qp_card.value;' . "\n" .
                '      } else if (document.checkout_payment.qp_card.value) {' . "\n" .
                '        qp_card_value = document.checkout_payment.qp_card.value;' . "\n" .
                '        document.checkout_payment.qp_card.checked=true;' . "\n" .
                '      }' . "\n" .
                '    if (qp_card_value == null) {' . "\n" .
                '   //   error_message = error_message + "' . MODULE_PAYMENT_QUICKPAY_ADVANCED_TEXT_SELECT_CARD . '";' . "\n" .
                '   //   error = 1;' . "\n" .
                '    }' . "\n" .
				' if (document.checkout_payment.cardlock.value == "") {' . "\n" .
                '      error_message = error_message + "' . MODULE_PAYMENT_QUICKPAY_ADVANCED_TEXT_SELECT_CARD . '";' . "\n" .
                '      error = 1;' . "\n" .
                '    }' . "\n" .
                '  }' . "\n";
        return $js;
		
		
		
  }

  function selection() {

       global $order, $currencies, $qp_card,$cardlock, $fee;
	 
	   define('DIR_WS_ICONS', DIR_WS_IMAGES."/icons/");
	    $qty_groups = 0;
		//$fees =array();
        for ($i = 1; $i <= $this->num_groups; $i++) {
        /*    if (constant('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i) == '') {
                continue;
            }
			if (constant('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i. '_FEE') == '') {
                continue;
            }else{
			$fees[$i] = constant('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i . '_FEE');
			}*/
            $qty_groups++;
        }

 if($qty_groups>1) {
	 $selection = array('id' => $this->code,
         'module' => $this->title. zen_draw_hidden_field('cardlock', $cardlock ). zen_draw_hidden_field('qp_card', $fee ));
	// $selection['module'] .= tep_draw_hidden_field('qp_card', (isset($fees[1])) ? $fees[1] : '0');
	 
	 }
		
		       $selection['fields'] = array();
               $msg = '';
			   $optscount=0;
	 for ($i = 1; $i <= $this->num_groups; $i++) {
               $options_text = '';
      if (defined('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i) && constant('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i) != '') {
                $payment_options = preg_split('[\,\;]', constant('MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i));
			    foreach ($payment_options as $option) {
   
        $cost = (MODULE_PAYMENT_QUICKPAY_ADVANCED_AUTOFEE == "No" || $option == 'viabill' ? "0" : "1");         
			  if($option=="creditcard"){
			  $optscount++;
				  //You can extend the following cards-array and upload corresponding titled images to images/icons
				  $cards= array('dankort','visa','american-express','jcb','mastercard');
				      foreach ($cards as $optionc) {
			 				$iconc ="";
$iconc = (file_exists(DIR_WS_ICONS.$optionc.".png") ? DIR_WS_ICONS.$optionc.".png": $iconc);
$iconc = (file_exists(DIR_WS_ICONS.$optionc.".jpg") ? DIR_WS_ICONS.$optionc.".jpg": $iconc);
$iconc = (file_exists(DIR_WS_ICONS.$optionc.".gif") ? DIR_WS_ICONS.$optionc.".gif": $iconc);   
			//define payment icon width
			   $w= 25;
			   $h= 18;
			   $space = 5;		   
				   
				   $msg .= zen_image($iconc,$optionc,$w,$h,'style="position:relative;border:0px;float:left;margin-left:'.$space.'px;margin-top:0px;" ');
					 
					 
					  }
					  $options_text=$msg;
			 
				
				   // $cost = $this->calculate_order_fee($order->info['total'], $fees[$i]);	

				
 if($qty_groups==1){
		 
			 $selection = array('id' => $this->code,
         'module' => '<table width="100%" border="0">
                    <tr class="moduleRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectQuickPayRowEffect(this, ' . ($optscount-1) . ',\''.$option.'\')">
                        <td class="main" style="height:22px;vertical-align:middle;">' .$options_text.($cost !=0 ? '</td><td class="main" style="height:22px;vertical-align:middle;"> (+ '.MODULE_PAYMENT_QUICKPAY_ADVANCED_FEELOCKINFO.')' :'').'
                            </td>
                    </tr></table>'.zen_draw_hidden_field('cardlock', $option));
		 
		 
	 }else{
				
					$selection['fields'][] = array('title' => '<table width="100%" border="0">
                    <tr class="moduleRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectQuickPayRowEffect(this, ' . ($optscount-1) . ',\''.$option.'\')">
                        <td class="main" style="height:22px;vertical-align:middle;">' . $options_text.($cost !=0 ? '</td><td style="height:22px;vertical-align:middle;">(+ '.MODULE_PAYMENT_QUICKPAY_ADVANCED_FEELOCKINFO.')' :'').'
                            </td>
                    </tr></table>',
             /*    'field' => ($cost !=0 ? ' (+'.$currencies->format($cost, true, $order->info['currency'], $order->info['currency_value']).') ' :'')
*/

); 
					//.tep_draw_radio_field('qp_card', $fees[$i], ($option==$cardlock ? true : false), ' onClick="setQuickPay(); document.checkout_payment.cardlock.value = \''.$option.'\';" '));

	 }//end qty=1
			 
			  }
			  
			  if($option != "creditcard"){
				  //upload images to images/icons corresponding to your chosen cardlock groups in your payment module settings
				  //OPTIONAL image if different from cardlogo, add _payment to filename
			
			  $selectedopts = explode(",",$option);	
				$icon ="";
				foreach($selectedopts as $option){
				$optscount++;
				
$icon = (file_exists(DIR_WS_ICONS.$option.".png") ? DIR_WS_ICONS.$option.".png": $icon);
$icon = (file_exists(DIR_WS_ICONS.$option.".jpg") ? DIR_WS_ICONS.$option.".jpg": $icon);
$icon = (file_exists(DIR_WS_ICONS.$option.".gif") ? DIR_WS_ICONS.$option.".gif": $icon);   
$icon = (file_exists(DIR_WS_ICONS.$option."_payment.png") ? DIR_WS_ICONS.$option."_payment.png": $icon);
$icon = (file_exists(DIR_WS_ICONS.$option."_payment.jpg") ? DIR_WS_ICONS.$option."_payment.jpg": $icon);
$icon = (file_exists(DIR_WS_ICONS.$option."_payment.gif") ? DIR_WS_ICONS.$option."_payment.gif": $icon); 				   
		$space = 5;
		//define payment icon width
		if(strstr($icon, "_payment")){
			$w=120;
			$h= 27;
			if(strstr($icon, "3d")){
				$w=60;
			}
			
		}else{
			   $w= 35;
			   $h= 22;
			   
			
		}
		
	

	 //$cost = $this->calculate_order_fee($order->info['total'], $fees[$i]);
		 $options_text = '<table><tr><td>'.zen_image($icon,$this->get_payment_options_name($option),$w,$h,' style="position:relative;border:0px;float:left;margin:'.$space.'px;" ').'</td><td style="height: 27px;white-space:nowrap;vertical-align:middle;font-size: 18px;font-color:#666;" >'.$this->get_payment_options_name($option).'</td></tr></table>';
				
				   	
			
		 if($qty_groups==1){
		 
		 $selection = array('id' => $this->code,
         'module' => '<table width="100%" border="0">
                    <tr class="moduleRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectQuickPayRowEffect(this, ' . ($optscount-1) . ',\''.$option.'\')">
                        <td class="main" style="height: 27px;white-space:nowrap;vertical-align:middle;font-size: 18px;font-color:#666;">' .$options_text.($cost !=0 ? '</td><td style="height:22px;vertical-align:middle;"> (+ '.MODULE_PAYMENT_QUICKPAY_ADVANCED_FEELOCKINFO.')' :'').'
                            </td>
                    </tr></table>'.zen_draw_hidden_field('cardlock', $option).zen_draw_hidden_field('qp_card', (isset($fees[1])) ? $fees[1] : '0'));
		 
		 
	 }else{	
					$selection['fields'][] = array('title' => '<table width="100%" border="0">
                    <tr class="moduleRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="selectQuickPayRowEffect(this, ' . ($optscount-1) . ',\''.$option.'\'); document.checkout_payment.cardlock.value = \''.$option.'\'; document.checkout_payment.qp_card.value = \''.$fees[$i].'\'; setQuickPay();" >
                        
                        <td class="main" style="height: 27px;white-space:nowrap;vertical-align:middle;font-size: 18px;font-color:#666;">' . $options_text.($cost !=0 ? '</td><td style="height:22px;vertical-align:middle;"> (+ '.MODULE_PAYMENT_QUICKPAY_ADVANCED_FEELOCKINFO.')' :'').'
                            </td>
                    </tr></table>',
                    'field' => '');
				//zen_draw_radio_field('qp_card', $fees[$i], ($option==$cardlock ? true : false), ' onClick="setQuickPay();document.checkout_payment.cardlock.value = \''.$option.'\';" style="display:none;" id="'.$option.'" '));
				
	 }//end qty
				
				}
           
					}
				
				
				
				}
		    }
			
       
	                  
	   
	   
	   
	   
	    }
			
	
	

    
   
            $js_function = '
        <script language="javascript"><!-- 
     
		  function setQuickPay() {
			
			  
          	var radioLength = document.checkout_payment.payment.length;
          	for(var i = 0; i < radioLength; i++) {
				
          		document.checkout_payment.payment[i].checked = false;
          		if(document.checkout_payment.payment[i].value == "quickpay_advanced") {
				
          			document.checkout_payment.payment[i].checked = true;
					
          		}
          	}
          }
          function selectQuickPayRowEffect(object, buttonSelect, option) {
            if (!selected) {
              if (document.getElementById) {
                selected = document.getElementById("defaultSelected");
              } else {
                selected = document.all["defaultSelected"];
              }
            }
         
            if (selected) selected.className = "moduleRow";
            object.className = "moduleRowSelected";
            selected = object;
            document.checkout_payment.cardlock.value = option;
	
            setQuickPay();
          }
		  
		  function rowOverEffect(object) {
  if (object.className == "moduleRow") object.className = "moduleRowOver";
   
}

function rowOutEffect(object) {
  if (object.className == "moduleRowOver") object.className = "moduleRow";

}
		  
        //--></script>
        ';
            $selection['module'] .= $js_function;
      $styles = "<style> 
	  .moduleRowSelected{ background-color: #ccc;}
	  .moduleRow {background-color: none;}
	  .moduleRowOver {background-color: #ccc;}
	  LABEL.inputLabelPayment table{
		  width: 20em !important;
	  }
	   </style>";
	   
     $selection['module'] .= $styles;
       
	    return $selection;
  }



  function pre_confirmation_check() {
	  
	  
	  
   // $this->get_order_fee();
  }

 
function confirmation() {
 $_SESSION["cardlock"] = $_POST["cardlock"];

		$options_text = $this->get_payment_options_name($_SESSION['cardlock']);
if($this->email_footer !='' ){
        $confirmation =  array('title' => $this->email_footer);
    }else{
		
      $confirmation = array('title' => MODULE_PAYMENT_QUICKPAY_ADVANCED_TEXT_DESCRIPTION . ': ' . $options_text);
    }

    return $confirmation;
  }

 













function process_button() {
   
        global $db, $_POST, $customer_id, $order, $currencies, $currency, $languages_id, $language, $cart_QuickPay_ID, $order_total_modules;
   


   
    // We need order_id to pass to the gateway
    // But we do not have the order_id at this moment. 
    // So instead we create the order_id now and bypass the usual checkout_process.php function
    if (!isset($_SESSION['order_id']) && $_POST['callquickpay'] == "go") {
      //insert data in proforma (preparing ) order
      $order->info['payment_method_code'] = $this->code;
	  $order->info['payment_method'] = "Quickpay";
	  $order->info["order_status"] = MODULE_PAYMENT_QUICKPAY_ADVANCED_PREPARE_ORDER_STATUS_ID;

	  // Assign order_id
     $_SESSION['order_id'] = $order->create($order_total_modules->process());

	  //preparing order update
	 $order->create_add_products($_SESSION['order_id']);
       $sql_data_array = array( 'orders_status' => MODULE_PAYMENT_QUICKPAY_ADVANCED_PREPARE_ORDER_STATUS_ID);
      zen_db_perform(TABLE_ORDERS, $sql_data_array,'update',"orders_id=".$_SESSION['order_id']);
    }




    $process_button_string = '';
		$process_fields ='';
        $process_parameters = array();

        $qp_merchant_id = MODULE_PAYMENT_QUICKPAY_ADVANCED_MERCHANTID;
		$qp_aggreement_id = MODULE_PAYMENT_QUICKPAY_ADVANCED_AGGREEMENTID;


// TODO: dynamic language switching instead of hardcoded mapping
        $qp_language = "da";
        switch ($language) {
            case "english": $qp_language = "en";
                break;
            case "swedish": $qp_language = "se";
                break;
            case "norwegian": $qp_language = "no";
                break;
            case "german": $qp_language = "de";
                break;
            case "french": $qp_language = "fr";
                break;
        }
         $qp_branding_id = "";

	     $qp_subscription = (MODULE_PAYMENT_QUICKPAY_ADVANCED_SUBSCRIPTION == "Normal" ? "" : "1");
		 $qp_type = ($qp_subscription == "" ? "Payment" : "Subscription");
		 $qp_cardtypelock = $_POST['cardlock'];
		 $qp_autofee = (MODULE_PAYMENT_QUICKPAY_ADVANCED_AUTOFEE == "No" || $qp_cardtypelock == 'viabill' ? "0" : "1");
         $qp_description = "Merchant ".$qp_merchant_id." ".(MODULE_PAYMENT_QUICKPAY_ADVANCED_SUBSCRIPTION == "Normal" ? "Authorize" : "Subscription");
		// $order_id = substr($cart_QuickPay_ID, strpos($cart_QuickPay_ID, '-') + 1);
		 $qp_order_id = $qp_aggreement_id."_".sprintf('%04d', $_SESSION['order_id']);

// Calculate the total order amount for the order (the same way as in checkout_process.php)
        $qp_order_amount = 100 * $this->currencies_calculate($order->info['total'], true, $order->info['currency'], $order->info['currency_value'], '.', '');
        $qp_currency_code = $order->info['currency'];
	

	    $qp_callbackurl = HTTP_SERVER.DIR_WS_CATALOG.'callback10.php?securityToken=' . $_SESSION['securityToken']."&type=".$qp_type;
		//"dummy call" - ignore actions and keep session...
		$qp_continueurl = HTTP_SERVER.DIR_WS_CATALOG.'callback10.php';
        $qp_cancelurl = str_replace("&amp;","&", zen_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code, 'SSL'));
 
        $qp_autocapture = (MODULE_PAYMENT_QUICKPAY_ADVANCED_AUTOCAPTURE == "No" ? "0" : "1");
		$qp_version ="v10";
        $qp_apikey = MODULE_PAYMENT_QUICKPAY_ADVANCED_APIKEY;

	//		$qp_product_id = "P03";
	//		$qp_category = MODULE_PAYMENT_QUICKPAY_ADVANCED_PAII_CAT;
	//		$qp_reference_title = $qp_order_id;
	//		$qp_vat_amount = ($order->info['tax'] ? $order->info['tax'] : "0.00");

  //custom vars
	   $varsvalues = array('variables[customers_id]' => $order->customer['customers_id'],
                    'variables[customers_name]' => $order->customer['firstname'] . ' ' . $order->customer['lastname'],
                    'variables[customers_company]' => $order->customer['company'],
                    'variables[customers_street_address]' => $order->customer['street_address'],
                    'variables[customers_suburb]' => $order->customer['suburb'],
                    'variables[customers_city]' => $order->customer['city'],
                    'variables[customers_postcode]' => $order->customer['postcode'],
                    'variables[customers_state]' => $order->customer['state'],
                    'variables[customers_country]' => $order->customer['country']['title'],
                    'variables[customers_telephone]' => $order->customer['telephone'],
                    'variables[customers_email_address]' => $order->customer['email_address'],
                    'variables[delivery_name]' => $order->delivery['firstname'] . ' ' . $order->delivery['lastname'],
                    'variables[delivery_company]' => $order->delivery['company'],
                    'variables[delivery_street_address]' => $order->delivery['street_address'],
                    'variables[delivery_suburb]' => $order->delivery['suburb'],
                    'variables[delivery_city]' => $order->delivery['city'],
                    'variables[delivery_postcode]' => $order->delivery['postcode'],
                    'variables[delivery_state]' => $order->delivery['state'],
                    'variables[delivery_country]' => $order->delivery['country']['title'],
                    'variables[delivery_address_format_id]' => $order->delivery['format_id'],
                    'variables[billing_name]' => $order->billing['firstname'] . ' ' . $order->billing['lastname'],
                    'variables[billing_company]' => $order->billing['company'],
                    'variables[billing_street_address]' => $order->billing['street_address'],
                    'variables[billing_suburb]' => $order->billing['suburb'],
                    'variables[billing_city]' => $order->billing['city'],
                    'variables[billing_postcode]' => $order->billing['postcode'],
                    'variables[billing_state]' => $order->billing['state'],
                    'variables[billing_country]' => $order->billing['country']['title']);



                for ($i = 0, $n = sizeof($order->products); $i < $n; $i++) {
    
                    $order_products_id = zen_get_prid($order->products[$i]['id']);

//------insert customer choosen option to order--------
                    $attributes_exist = '0';
                    $products_ordered_attributes = '';
                    if (isset($order->products[$i]['attributes'])) {
                        $attributes_exist = '1';
                        for ($j = 0, $n2 = sizeof($order->products[$i]['attributes']); $j < $n2; $j++) {
                            if (DOWNLOAD_ENABLED == 'true') {
                                $attributes_query = "select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename 
                               from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa 
                               left join " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                on pa.products_attributes_id=pad.products_attributes_id
                               where pa.products_id = '" . $order->products[$i]['id'] . "' 
                                and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' 
                                and pa.options_id = popt.products_options_id 
                                and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' 
                                and pa.options_values_id = poval.products_options_values_id 
                                and popt.language_id = '" . $languages_id . "' 
                                and poval.language_id = '" . $languages_id . "'";
                                $attributes = $db->Execute($attributes_query);
                            } else {
                                $attributes = $db->Execute("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa where pa.products_id = '" . $order->products[$i]['id'] . "' and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . $languages_id . "' and poval.language_id = '" . $languages_id . "'");
                            }
                            $attributes_values = zen_db_fetch_array($attributes);
                            

                            if ((DOWNLOAD_ENABLED == 'true') && isset($attributes_values['products_attributes_filename']) && zen_not_null($attributes_values['products_attributes_filename'])) {
   
                               
                            }
                            $products_ordered_attributes .= "\n\t" . $attributes_values['products_options_name'] . ' ' . $attributes_values['products_options_values_name'];
                        }
                    }
//------insert customer choosen option eof ----
             
                      $total_weight += ( $order->products[$i]['qty'] * $order->products[$i]['weight']);
                      $total_tax += zen_calculate_tax($total_products_price, $products_tax) * $order->products[$i]['qty'];
                      $total_cost += $total_products_price;

                      $products_ordered[] = $order->products[$i]['qty'] . ' x ' . $order->products[$i]['name'] . ' (' . $order->products[$i]['model'] . ') = ' . $currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']) . $products_ordered_attributes . "\n";

                }
				$ps="";
		       while (list ($key, $value) = each($products_ordered)) {
		  $ps .= $value;
        }
		
        //$varsvalues["variables[products]"] = html_entity_decode($ps);
		$varsvalues["variables[orderlang]"] = '"'.$language.'"';
		$varsvalues["variables[shopsystem]"] = "ZenCart";
  

//end custom vars







// register fields to hand over

		$process_parameters = array(
					'agreement_id'                 => $qp_aggreement_id,
					'amount'                       => $qp_order_amount,
					'autocapture'                  => $qp_autocapture,
					'autofee'                      => $qp_autofee,
					//'branding_id'                  => $qp_branding_id,
					'callbackurl'                  => $qp_callbackurl,
					'cancelurl'                    => $qp_cancelurl,
					'continueurl'                  => $qp_continueurl,
					'currency'                     => $qp_currency_code,
					'description'                  => $qp_description,
					'google_analytics_client_id'   => $qp_google_analytics_client_id,
					'google_analytics_tracking_id' => $analytics_tracking_id,
					'language'                     => $qp_language,
					'merchant_id'                  => $qp_merchant_id,
					'order_id'                     => $qp_order_id,
					'payment_methods'              => $qp_cardtypelock,
					//'product_id'                   => $qp_product_id,
					//'category'                     => $qp_category,
					//'reference_title'              => $qp_reference_title,
					//'vat_amount'                   => $qp_vat_amount,
					'subscription'                 => $qp_subscription,
					'version'                      => 'v10'
						);
 
 
 
 
 $process_parameters = array_merge($process_parameters,$varsvalues);


		
if($_POST['callquickpay'] == "go") {
	    $apiorder= new QuickpayApi();
	$apiorder->setOptions(MODULE_PAYMENT_QUICKPAY_ADVANCED_USERAPIKEY);
	//set status request mode
	$mode = (MODULE_PAYMENT_QUICKPAY_ADVANCED_SUBSCRIPTION == "Normal" ? "" : "1");
	  	//been here before?
	    $exists = $this->get_quickpay_order_status($_SESSION['order_id'], $mode);
	
    $qid = $exists["qid"];
	//set to create/update mode
	$apiorder->mode = (MODULE_PAYMENT_QUICKPAY_ADVANCED_SUBSCRIPTION == "Normal" ? "payments/" : "subscriptions/");
	  
	  if($exists["qid"] == null){

      //create new quickpay order	
      $storder = $apiorder->createorder($qp_order_id, $qp_currency_code, $process_parameters);
      $qid = $storder["id"];

      }else{
       $qid = $exists["qid"];
       }
		$storder = $apiorder->link($qid, $process_parameters);
	
		if($storder['url']){
			$process_button_string .= "<script>
     
window.location.replace('".$storder['url']."');
      </script>";
		}else{
			$process_button_string .= "<script>
       alert('Quickpay error: Payment module is not properly configured');

      </script>";
			
			}
	//$process_button_string .=  "<input type='hidden' value='go' name='callquickpay' />". "\n".
            //	"<input type='hidden' value='" . $_POST['cardlock'] . "' name='cardlock' />
			//	<input type='hidden' value='" . $_POST['conditions'] . "' name='conditions' />";
			
	 
}
	$process_button_string .=  "<input type='hidden' value='go' name='callquickpay' />". "\n";
            //	"<input type='hidden' value='" . $_POST['cardlock'] . "' name='cardlock' />
			//	<input type='hidden' value='" . $_POST['conditions'] . "' name='conditions' />";
	foreach($_POST as $key=>$value){
				$process_button_string .= "<input type='hidden' value='".$value."' name='".$key."' />". "\n";
				
			}

  

        return $process_button_string;
  }


  function before_process() {

$mode = (MODULE_PAYMENT_QUICKPAY_ADVANCED_SUBSCRIPTION == "Normal" ? "" : "1");
 $checkorderid = $this->get_quickpay_order_status($_SESSION['order_id'], $mode);

 if($checkorderid["oid"] != $_SESSION['order_id']){
zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code, 'SSL'));
 }
//run callback operation triggered from callback form

	//not a dummy callback
	$this->process_callback();
	

	
  }
  
function process_callback(){
global $db, $order, $language;
	$api= new QuickpayApi();

	$api->setOptions(MODULE_PAYMENT_QUICKPAY_ADVANCED_USERAPIKEY);
    $order_id = $_SESSION['order_id'];
	
  try {

     $api->mode = (MODULE_PAYMENT_QUICKPAY_ADVANCED_SUBSCRIPTION == "Normal" ? "payments?order_id=" : "subscriptions?order_id=");
    // Commit the status request, checking valid transaction id
    $st = $api->status(MODULE_PAYMENT_QUICKPAY_ADVANCED_AGGREEMENTID."_".sprintf('%04d', $order_id));


if($st[0]["id"]){
   $st[0]["operations"] = array_reverse($st[0]["operations"]);
   $qp_id = $st[0]["id"];
    $qp_status = $st[0]["operations"][0]["qp_status_code"];
 $qp_status_msg = $st[0]["operations"][0]["qp_status_msg"];
 $qp_order_id = str_replace(MODULE_PAYMENT_QUICKPAY_ADVANCED_AGGREEMENTID."_","", $st[0]["order_id"]);
 $qp_aq_status_code = $st[0]["aq_status_code"];
 $qp_aq_status_msg = $st[0]["aq_status_msg"];
  $qp_cardtype = $st[0]["metadata"]["brand"];
  $qp_cardnumber = "xxxx-xxxxxx-".$st[0]["metadata"]["last4"];
  $qp_amount = $st[0]["operations"][0]["amount"];
  $qp_currency = $st[0]["currency"];
  $qp_pending = ($st[0]["pending"] == "true" ? " - pending ": "");
  $qp_expire = $st[0]["metadata"]["exp_month"]."-".$st[0]["metadata"]["exp_year"];
  $qp_type = $st[0]["type"];
  $qp_lang = $st[0]["variables"]["orderlang"];
  $qp_cardhash = $st[0]["operations"][0]["type"].(strstr($st[0]["description"],'Subscription') ? " Subscription" : "");
   
   
$qp_approved = false;
/*
20000	Approved
40000	Rejected By Acquirer
40001	Request Data Error
50000	Gateway Error
50300	Communications Error (with Acquirer)
*/

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


        // reject order by updating status
        zen_db_perform(TABLE_ORDERS, $sql_data_array, 'update', "orders_id = '" . $qp_order_id . "'");


        $sql_data_array = array('orders_id' => $qp_order_id,
            'orders_status_id' => MODULE_PAYMENT_QUICKPAY_ADVANCED_REJECTED_ORDER_STATUS_ID,
            'date_added' => 'now()',
            'customer_notified' => '0',
            'comments' => 'QuickPay Payment rejected [message:' . $qp_status_msg . ' - '.$qp_aq_status_msg.']');

        //zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

        break;

    default:

        $sql_data_array = array('cc_transactionid' => $qp_status,
            'last_modified' => 'now()');

        // approve order by updating status
        zen_db_perform(TABLE_ORDERS, $sql_data_array, 'update', "orders_id = '" . $qp_order_id . "'");


        $sql_data_array = array('orders_id' => $order_id,
            'orders_status_id' => MODULE_PAYMENT_QUICKPAY_ADVANCED_REJECTED_ORDER_STATUS_ID,
            'date_added' => 'now()',
            'customer_notified' => '0',
            'comments' => 'QuickPay Payment rejected [status code:' . $qp_status . ']');

        //zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

        break;
}
if ($qp_approved) {

    // payment approved
  			//update order info		
// set order status as configured in the module
            $order_status_id = (MODULE_PAYMENT_QUICKPAY_ADVANCED_ORDER_STATUS_ID > 0 ? (int) MODULE_PAYMENT_QUICKPAY_ADVANCED_ORDER_STATUS_ID : (int) DEFAULT_ORDERS_STATUS_ID);
$order->info['cc_number'] = $qp_cardnumber;
$order->info['cc_type'] = $qp_cardtype;
$order->info['cc_expires'] = ($qp_expire ? $qp_expire : 'N/A');
$order->info['order_status'] = $order_status_id;
 if(!$_SESSION['link'] && !$_SESSION['link'] == $qp_id){
  //clean up preparing order, let ZenCart create  order data as usual if it is not a linked order

                    $db->Execute('delete from ' . TABLE_ORDERS_TOTAL . ' where orders_id = "' . (int) $order_id . '"');
                    $db->Execute('delete from ' . TABLE_ORDERS_PRODUCTS . ' where orders_id = "' . (int) $order_id . '"');
                    $db->Execute('delete from ' . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . ' where orders_id = "' . (int) $order_id . '"');
                    $db->Execute('delete from ' . TABLE_ORDERS_PRODUCTS_DOWNLOAD . ' where orders_id = "' . (int) $order_id . '"');       
 }
	   // update order
    $sql = "select orders_status from " . TABLE_ORDERS . " where orders_id = '" . $order_id . "' and orders_status = '". MODULE_PAYMENT_QUICKPAY_ADVANCED_PREPARE_ORDER_STATUS_ID."' ";
    $order_query = $db->Execute($sql);

    if (!$order_query->EOF) {

            $sql_data_array = array('cc_transactionid' => $st[0]["id"],
			    'cc_cardhash' => $qp_cardhash,
				'orders_status' => $order_status_id,
                'last_modified' => 'now()');
	
            // approve order by updating status
            zen_db_perform(TABLE_ORDERS, $sql_data_array, 'update', "orders_id = '" . $qp_order_id . "'");

			
        }
    } 
	
	//subscription handling
if($qp_type == "subscription"){

	$api->mode = "subscriptions/";
	$addlink = $qp_id."/recurring/";
	$qp_autocapture = (MODULE_PAYMENT_QUICKPAY_ADVANCED_AUTOCAPTURE == "No" ? FALSE : TRUE);
	  //create new quickpay order
	  $process_parameters["amount"]= $qp_amount;
	  $process_parameters["order_id"]= $qp_order_id."-".$qp_id;
	  $process_parameters["auto_capture"]= $qp_autocapture;	
      $storder = $api->createorder($qp_order_id, $qp_currency, $process_parameters, $addlink);
	
}
	  
    $eval = $qp_lang;
	}else{
		
	    $sql_data_array = array('cc_transactionid' => zen_db_input(MODULE_PAYMENT_QUICKPAY_ADVANCED_ERROR_TRANSACTION_DECLINED),
		'orders_status' => MODULE_PAYMENT_QUICKPAY_ADVANCED_REJECTED_ORDER_STATUS_ID);
    zen_db_perform(TABLE_ORDERS, $sql_data_array, 'update', "orders_id = '" . $order_id . "'");
		
	$eval = "not valid";
		
	}
  
  } catch (Exception $e) {
   $eval = 'QuickPay Status: ';
		  	// An error occured with the status request
          $eval .= 'Problem: ' . $this->json_message_front($e->getMessage()) ;
		//todo: use message frontend...
  }

    return $eval;
	
  }
  
  
  /**
   *
   */
  function after_process() {
    unset($_SESSION['order_id']);
    unset($_SESSION['quickpay_fee']);
    unset($_SESSION['qp_card']);
	 unset($_SESSION['cardlock']);
	 unset($_SESSION['cardlock']);
  }


  /**
   *
   */
  

function get_error() {
    global $db;
 
    $transaction = $db->Execute("select cc_transactionid from " . TABLE_ORDERS . " where orders_id = '" . $_SESSION['order_id'] . "'");
    $errorcode = $transaction->fields['cc_transactionid'];

    // Remove transactionid for declined payment
    $sql_data_array = array('cc_transactionid' => NULL);
    zen_db_perform(TABLE_ORDERS, $sql_data_array, 'update', 'orders_id = '. $_SESSION['order_id']);

 $error_desc = '';
        switch (urldecode($errorcode)) {
            case 1: $error_desc = MODULE_PAYMENT_QUICKPAY_ADVANCED_ERROR_TRANSACTION_DECLINED;
                break;
            case 2: $error_desc = MODULE_PAYMENT_QUICKPAY_ADVANCED_ERROR_COMMUNICATION_FAILURE;
                break;
            case 3: $error_desc = MODULE_PAYMENT_QUICKPAY_ADVANCED_ERROR_CARD_EXPIRED;
                break;
            case 4: $error_desc = MODULE_PAYMENT_QUICKPAY_ADVANCED_ERROR_ILLEGAL_TRANSACTION;
                break;
            case 5: $error_desc = MODULE_PAYMENT_QUICKPAY_ADVANCED_ERROR_TRANSACTION_EXPIRED;
                break; // NO TRANSLATION!!!
            case 6: $error_desc = MODULE_PAYMENT_QUICKPAY_ADVANCED_ERROR_COMMUNICATION_FAILURE;
                break;
            case 7: $error_desc = MODULE_PAYMENT_QUICKPAY_ADVANCED_ERROR_SYSTEM_FAILURE;
                break;
            default:if ($errorcode == '' || $errorcode == 0) {
                    $error_desc = MODULE_PAYMENT_QUICKPAY_ADVANCED_ERROR_CANCELLED;
				
					
                } else {
                    $error_desc = nl2br(urldecode($errorcode)); //ERROR_CARDNO_NOT_VALID;
                }
        }
        $error = array('title' => MODULE_PAYMENT_QUICKPAY_ADVANCED_TEXT_ERROR,
            'error' => $error_desc);
			
		//unset($_SESSION['order_id']);
	
	
        return $error;
  }
   function output_error() {
        return false;
    }



  /**
   * Evaluate installation status of this module. Returns true if the status key is found.
   */
  function check() {
    global $db;

    if (!isset($this->_check)) {
      $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_QUICKPAY_ADVANCED_STATUS'");
      $this->_check = !$check_query->EOF;
    }

    return $this->_check;
  }

  /**
   * Installs all the configuration keys for this module
   */
  

function install() {
    global $db;
   // new status for quickpay prepare orders
        $check_query = $db->Execute("select orders_status_id from " . TABLE_ORDERS_STATUS . " where orders_status_name = 'Preparing [Quickpay]' limit 1");

        if ($check_query->EOF) {
            $status_query = $db->Execute("select max(orders_status_id) as status_id from " . TABLE_ORDERS_STATUS);
            

            $status_id = $status_query->fields['status_id'] + 1;

            $languages = zen_get_languages();

            for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
                $db->Execute("insert into " . TABLE_ORDERS_STATUS . " (orders_status_id, language_id, orders_status_name) values ('" . $status_id . "', '" . $languages[$i]['id'] . "', 'Preparing [Quickpay]')");
            }

        }


        // new status for quickpay rejected orders
        $check_query = $db->Execute("select orders_status_id from " . TABLE_ORDERS_STATUS . " where orders_status_name = 'Rejected [Quickpay]' limit 1");

        if ($check_query->EOF) {
            $status_query = $db->Execute("select max(orders_status_id) as status_id from " . TABLE_ORDERS_STATUS);

            $status_rejected_id = $status_query->fields['status_id'] + 1;

            $languages = zen_get_languages();

            for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
                $db->Execute("insert into " . TABLE_ORDERS_STATUS . " (orders_status_id, language_id, orders_status_name) values ('" . $status_rejected_id . "', '" . $languages[$i]['id'] . "', 'Rejected [Quickpay]')");
            }

		}
		
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Quickpay payments', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_STATUS', 'False', 'Do you want to accept quickpay payments?', '6', '3', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'tep_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");
       
	    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Quickpay Merchant ID', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_MERCHANTID', '', 'Enter Merchant ID (As stated in your quickpay manager \"integrations\" tab)', '6', '6', now())"); 
		
		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Quickpay PAYMENT WINDOW Agreement ID', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_AGGREEMENTID', '', 'Enter Payment Window Agreement id (As stated in your quickpay manager \"integrations\" tab)', '6', '6', now())");

		 
		 $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('API USER key', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_USERAPIKEY', '', 'Enter API USER key (As stated in your quickpay manager \"integrations\" tab)', '6', '6', now())");
		
$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Subscription payment', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_SUBSCRIPTION', 'Normal', 'Set Subscription payment as default (normal is single payment).', '6', '0', 'zen_cfg_select_option(array(\'Normal\', \'Subscription\'), ',now())");

		
$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Autofee', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_AUTOFEE', 'No', 'Does customer pay the cardfee?<br>Set up fees in <a href=\"https://manage.quickpay.net/\" target=\"_blank\"><u>Quickpay manager</u></a>', '6', '0', 'zen_cfg_select_option(array(\'Yes\', \'No\'), ',now())");

$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Autocapture', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_AUTOCAPTURE', 'No', 'Use autocapture?', '6', '0', 'zen_cfg_select_option(array(\'Yes\', \'No\'), ',now())");     
        for ($i = 1; $i <= $this->num_groups; $i++) {
			if($i==1){
				$defaultlock='viabill';
		//		$qp_groupfee = '0:0';
			}else if($i==2){
				$defaultlock='creditcard';
	//			$qp_groupfee = '0:0';
		
			}else{
				$defaultlock='';
		//		$qp_groupfee ='0:0';
			}
 

    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Group " . $i . " Payment Options ', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP" . $i . "', '" . $defaultlock . "', 'Comma seperated Quickpay payment options that are included in Group " . $i . ", maximum 255 chars (<a href=\'http://tech.quickpay.net/appendixes/payment-methods\' target=\'_blank\'><u>available options</u></a>)<br>Example: creditcard OR ibill OR dankort,danske-dk<br>', '6', '6', now())");

        }

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");


						
//$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Paii shop category', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_PAII_CAT','', 'Shop category must be set, if using Paii cardlock (paii), ', '6', '0','zen_cfg_pull_down_paii_list(', now())");
	   
	    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Quickpay preparing Order Status', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_PREPARE_ORDER_STATUS_ID', '" . $status_id . "', 'Set the status of preparing orders made with this payment module to this value', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
      
	    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Quickpay Acknowledged Order Status', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
       
	    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Quickpay Rejected Order Status', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_REJECTED_ORDER_STATUS_ID', '" . $status_rejected_id . "', 'Set the status of rejected orders made with this payment module to this value', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");


    $check_query = $db->Execute("describe " . TABLE_ORDERS . " cc_transactionid");
        if ($check_query->EOF) {
            $db->Execute("ALTER TABLE " . TABLE_ORDERS . " ADD cc_transactionid VARCHAR( 64 ) NULL default 'NULL'");
        }
		$check_query = $db->Execute("describe " . TABLE_ORDERS . " cc_cardhash");
        if ($check_query->EOF) {
            $db->Execute("ALTER TABLE " . TABLE_ORDERS . " ADD cc_cardhash TEXT NOT NULL");
        } 
  
       $db->Execute("ALTER TABLE  " . TABLE_ORDERS . " CHANGE  cc_expires  cc_expires VARCHAR( 8 )  NULL DEFAULT NULL"); 
          
   
                
  }

  /**
   * De-install this module
   */
  function remove() {
    global $db;

    $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");

  }

  /**
   *
   */
  function keys() {
        $keys = array('MODULE_PAYMENT_QUICKPAY_ADVANCED_STATUS', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_ZONE', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_SORT_ORDER','MODULE_PAYMENT_QUICKPAY_ADVANCED_MERCHANTID','MODULE_PAYMENT_QUICKPAY_ADVANCED_AGGREEMENTID', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_USERAPIKEY','MODULE_PAYMENT_QUICKPAY_ADVANCED_PREPARE_ORDER_STATUS_ID', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_ORDER_STATUS_ID', 'MODULE_PAYMENT_QUICKPAY_ADVANCED_REJECTED_ORDER_STATUS_ID','MODULE_PAYMENT_QUICKPAY_ADVANCED_SUBSCRIPTION','MODULE_PAYMENT_QUICKPAY_ADVANCED_AUTOFEE','MODULE_PAYMENT_QUICKPAY_ADVANCED_AUTOCAPTURE');

		
        for ($i = 1; $i <= $this->num_groups; $i++) {
            $keys[] = 'MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i;
            $keys[] = 'MODULE_PAYMENT_QUICKPAY_ADVANCED_GROUP' . $i . '_FEE';
        }

        return $keys;
  }

  // ------------- Internal help functions -------------------------    
  /**
   *
   */
   
   


  /**
   *
   * $order_total parameter must be total amount for current order including tax
   * format of $fee parameter: "[fixed fee]:[percentage fee]"
   */
  function calculate_order_fee($order_total, $fee) {
    list($fixed_fee, $percent_fee) = explode(':', $fee);
    return ((float)$fixed_fee + (float)$order_total*$percent_fee/100);
  }


  /**
   *
   */
  function get_order_fee() {
    global $order, $currencies;
    $_SESSION['quickpay_fee'] = 0.0;

    if (isset($_POST['qp_card']) && strpos($_POST['qp_card'], ":")) {
      $_SESSION['quickpay_fee'] = $this->calculate_order_fee($order->info['total'], $_POST['qp_card']);
    }
  }

  /**
   *
   */
  function get_payment_options_name($payment_option) {
    switch ($payment_option) {
                case '3d-jcb': return MODULE_PAYMENT_QUICKPAY_ADVANCED_JCB_3D_TEXT;
            case '3d-maestro': return MODULE_PAYMENT_QUICKPAY_ADVANCED_MAESTRO_3D_TEXT;
            case '3d-maestro-dk': return MODULE_PAYMENT_QUICKPAY_ADVANCED_MAESTRO_DK_3D_TEXT;
            case '3d-mastercard': return MODULE_PAYMENT_QUICKPAY_ADVANCED_MASTERCARD_3D_TEXT;
            case '3d-mastercard-dk': return MODULE_PAYMENT_QUICKPAY_ADVANCED_ADVANCED_MASTERCARD_DK_3D_TEXT;
            case '3d-visa': return MODULE_PAYMENT_QUICKPAY_ADVANCED_ADVANCED_VISA_3D_TEXT;
            case '3d-visa-dk': return MODULE_PAYMENT_QUICKPAY_ADVANCED_ADVANCED_VISA_DK_3D_TEXT;
            case '3d-visa-electron': return MODULE_PAYMENT_QUICKPAY_ADVANCED_ADVANCED_VISA_ELECTRON_3D_TEXT;
            case '3d-visa-electron-dk': return MODULE_PAYMENT_QUICKPAY_ADVANCED_VISA_ELECTRON_DK_3D_TEXT;
            case '3d-visa-debet': return MODULE_PAYMENT_QUICKPAY_ADVANCED_VISA_DEBET_3D_TEXT;
			case '3d-visa-debet-dk': return MODULE_PAYMENT_QUICKPAY_ADVANCED_VISA_DEBET_DK_3D_TEXT;
			case '3d-creditcard': return MODULE_PAYMENT_QUICKPAY_ADVANCED_CREDITCARD_3D_TEXT;
            case 'american-express': return MODULE_PAYMENT_QUICKPAY_ADVANCED_AMERICAN_EXPRESS_TEXT;
            case 'american-express-dk': return MODULE_PAYMENT_QUICKPAY_ADVANCED_AMERICAN_EXPRESS_DK_TEXT;
            case 'dankort': return MODULE_PAYMENT_QUICKPAY_ADVANCED_DANKORT_TEXT;
            case 'danske-dk': return MODULE_PAYMENT_QUICKPAY_ADVANCED_DANSKE_DK_TEXT;
            case 'diners': return MODULE_PAYMENT_QUICKPAY_ADVANCED_DINERS_TEXT;
            case 'diners-dk': return MODULE_PAYMENT_QUICKPAY_ADVANCED_DINERS_DK_TEXT;
            case 'edankort': return MODULE_PAYMENT_QUICKPAY_ADVANCED_EDANKORT_TEXT;
            case 'jcb': return MODULE_PAYMENT_QUICKPAY_ADVANCED_JCB_TEXT;
            case 'mastercard': return MODULE_PAYMENT_QUICKPAY_ADVANCED_MASTERCARD_TEXT;
            case 'mastercard-dk': return MODULE_PAYMENT_QUICKPAY_ADVANCED_MASTERCARD_DK_TEXT;
			case 'mastercard-debet': return MODULE_PAYMENT_QUICKPAY_ADVANCED_MASTERCARD_DEBET_TEXT;
            case 'mastercard-debet-dk': return MODULE_PAYMENT_QUICKPAY_ADVANCED_MASTERCARD_DEBET_DK_TEXT;
            case 'nordea-dk': return MODULE_PAYMENT_QUICKPAY_ADVANCED_NORDEA_DK_TEXT;
            case 'visa': return MODULE_PAYMENT_QUICKPAY_ADVANCED_VISA_TEXT;
            case 'visa-dk': return MODULE_PAYMENT_QUICKPAY_ADVANCED_VISA_DK_TEXT;
            case 'visa-electron': return MODULE_PAYMENT_QUICKPAY_ADVANCED_VISA_ELECTRON_TEXT;
            case 'visa-electron-dk': return MODULE_PAYMENT_QUICKPAY_ADVANCED_VISA_ELECTRON_DK_TEXT;
		    case 'creditcard': return MODULE_PAYMENT_QUICKPAY_ADVANCED_CREDITCARD_TEXT;
			case 'ibill':  return MODULE_PAYMENT_QUICKPAY_ADVANCED_IBILL_DESCRIPTION;
			case 'viabill':  return MODULE_PAYMENT_QUICKPAY_ADVANCED_IBILL_DESCRIPTION;
            case 'fbg1886': return MODULE_PAYMENT_QUICKPAY_ADVANCED_FBG1886_TEXT;
            case 'paypal': return MODULE_PAYMENT_QUICKPAY_ADVANCED_PAYPAL_TEXT;
            case 'sofort': return MODULE_PAYMENT_QUICKPAY_ADVANCED_SOFORT_TEXT;
            //case 'paii': return MODULE_PAYMENT_QUICKPAY_ADVANCED_PAII_TEXT;
			case 'mobilepay': return MODULE_PAYMENT_QUICKPAY_ADVANCED_MOBILEPAY_TEXT;
 
   }
    return '';
  }
  /**
   *
   */
  function currencies_calculate($number, $calculate_currency_value = true, $currency_type = '', $currency_value = '', $currency_decimal_point = '', $currency_thousands_point = '@') {
    global $currency;
    global $currencies;

    if (empty($currency_type)) {
      $currency_type = $currency;
    }

    if (empty($currency_decimal_point)) {
      $currency_decimal_point = $currencies->currencies[$currency_type]['decimal_point'];
    }

    if ($currency_thousands_point == '@') {
      $currency_thousands_point = $currencies->currencies[$currency_type]['thousands_point'];
    }

    $number_display = $number; 

    if ($calculate_currency_value) {
      $rate = (zen_not_null($currency_value)) ? $currency_value : $currencies->currencies[$currency_type]['value'];
      $number_display = $number * $rate;
    }

    return number_format($number_display, $currencies->currencies[$currency_type]['decimal_places'], $currency_decimal_point, $currency_thousands_point); 
  }

public function sign($params, $api_key) {
    ksort($params);
   $base = implode(" ", $params);
 
   return hash_hmac("sha256", $base, $api_key);
 }
 
private function get_quickpay_order_status($order_id, $mode="") {
global $db, $order, $currencies;
 
	$api= new QuickpayApi();

	$api->setOptions(MODULE_PAYMENT_QUICKPAY_ADVANCED_USERAPIKEY);
    $api->mode = ($mode=="" ? "payments?order_id=" : "subscriptions?order_id=");
  try {
	
   // Commit the status request, checking valid transaction id
    $st = $api->status(MODULE_PAYMENT_QUICKPAY_ADVANCED_AGGREEMENTID."_".sprintf('%04d', $order_id));
	
		
	
		$eval = array();
	if($st[0]["id"]){

    $eval["oid"] = str_replace(MODULE_PAYMENT_QUICKPAY_ADVANCED_AGGREEMENTID."_","", $st[0]["order_id"]);
	$eval["qid"] = $st[0]["id"];
	}else{
	$eval["oid"] = null;
	$eval["qid"] = null;	
	}
  
  } catch (Exception $e) {
   $eval = 'QuickPay Status: ';
		  	// An error occured with the status request
          $eval .= 'Problem: ' . $this->json_message_front($e->getMessage()) ;
		//todo: use message frontend...
  }

    return $eval;
  } 

private function json_message_front($input){
	
	$dec = json_decode($input,true);
	
	$message= $dec["message"];

	return $message;
	
	
}
}


  /* // deprecated, maybe reuseable...
  function zen_cfg_pull_down_paii_list() {
	  global $db;
	//Paii categories
$paiioptions = array(
								''	   => '',
							'SC00' => 'Ringetoner, baggrundsbilleder m.v.',
							'SC01' => 'Videoklip og	tv',
							'SC02' => 'Erotik og voksenindhold',
							'SC03' => 'Musik, sange og albums',
							'SC04' => 'Lydb&oslash;ger	og podcasts',
							'SC05' => 'Mobil spil',
							'SC06' => 'Chat	og dating',
							'SC07' => 'Afstemning og konkurrencer',
							'SC08' => 'Mobil betaling',
							'SC09' => 'Nyheder og information',
							'SC10' => 'Donationer',
							'SC11' => 'Telemetri og service sms',
							'SC12' => 'Diverse',
							'SC13' => 'Kiosker & sm&aring; k&oslash;bm&aelig;nd',
							'SC14' => 'Dagligvare, F&oslash;devarer & non-food',
							'SC15' => 'Vin & tobak',
							'SC16' => 'Apoteker	og medikamenter',
							'SC17' => 'T&oslash;j, sko og accessories',
							'SC18' => 'Hus, Have, Bolig og indretning',
							'SC19' => 'B&oslash;ger, papirvare	og kontorartikler',
							'SC20' => 'Elektronik, Computer & software',
							'SC21' => '&Oslash;vrige forbrugsgoder',
							'SC22' => 'Hotel, ophold, restaurant, cafe & v&aelig;rtshuse, Kantiner og catering',
							'SC24' => 'Kommunikation og konnektivitet, ikke via telefonregning',
							'SC25' => 'Kollektiv trafik',
							'SC26' => 'Individuel trafik (Taxik&oslash;rsel)',
							'SC27' => 'Rejse (lufttrafik, rejser, rejser med ophold)',
							'SC28' => 'Kommunikation og konnektivitet, via telefonregning',
							'SC29' => 'Serviceydelser',
							'SC30' => 'Forlystelser og underholdning, ikke digital',
							'SC31' => 'Lotteri- og anden spillevirksomhed',
							'SC32' => 'Interesse- og hobby (Motion, Sport, udendørsaktivitet, foreninger, organisation)',
							'SC33' => 'Personlig pleje (Fris&oslash;r, sk&oslash;nhed, sol og helse)',
							'SC34' => 'Erotik og voksenprodukter(fysiske produkter)'
						);
	
	
	$options = '';	
	$paiicat_values =  $db->Execute("select configuration_value  from ".TABLE_CONFIGURATION. " WHERE configuration_key  =  'MODULE_PAYMENT_QUICKPAY_ADVANCED_PAII_CAT' ");
   // $paiicat_values = zen_db_fetch_array($paiique);
    $selectedcat = $paiicat_values->fields['configuration_value'];

	$option_array=array();	
foreach($paiioptions as $arrid => $val){

	 $selected ='';
	  if ($selectedcat == $arrid) {
        $selected = ' selected="selected"';
      }							
	 $options .= '<option value="'.$arrid.'" '.$selected.' >'.utf8_encode($val).'</option>';
}  
	  

    return "<select name='configuration[MODULE_PAYMENT_QUICKPAY_ADVANCED_PAII_CAT]' />
	".$options."	
	</select>";
  }
  */