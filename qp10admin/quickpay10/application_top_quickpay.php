<?php
include('quickpay10.php');

include($_SERVER["DOCUMENT_ROOT"].DIR_WS_CATALOG.DIR_WS_CLASSES.'QuickpayApi.php');
	$api= new QuickpayApi();
	
	if (!defined('MODULE_PAYMENT_QUICKPAY_ADVANCED_USERAPIKEY')) {
    define('MODULE_PAYMENT_QUICKPAY_ADVANCED_USERAPIKEY', MODULE_PAYMENT_QUICKPAY_ADVANCED_APIKEY);
}
	$api->setOptions(MODULE_PAYMENT_QUICKPAY_ADVANCED_USERAPIKEY);


?>