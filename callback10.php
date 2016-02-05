<?php
ini_set("display_errors", "on");
error_reporting(E_ALL);
//quickpay callback
//include($_SERVER["DOCUMENT_ROOT"].dirname($_SERVER["PHP_SELF"])."/includes/configure.php");
include($_SERVER["DOCUMENT_ROOT"].dirname($_SERVER["PHP_SELF"])."/includes/application_top.php");
$type = strtolower($_GET["type"])."s/";
	//Zen don't accept remote call if POST["security_token"] is not provided. v10 do not use POST...;
//Quickpay transaction	validity is checked in Quickpay payment module before_process() if customer is logged in.
 

if(isset($_GET["transactionid"])){
	//payment link sent from admin. customer might not be logged in. No session.
require_once(DIR_FS_CATALOG.DIR_WS_MODULES.'payment/quickpay_advanced.php');
require_once(DIR_FS_CATALOG.DIR_WS_CLASSES.'order.php');
    $_SESSION['order_id'] = $_GET["oid"];
	$_SESSION['link'] = $_GET["transactionid"];
	$order = new order($_SESSION['order_id']);
	$payment = new quickpay_advanced();
    $tr = $payment->process_callback();
	mail("kl@blkom.dk","linkback",json_encode($tr).json_encode($order));

$db->Execute('delete from ' . TABLE_CUSTOMERS_BASKET . ' where customers_id = "' . $order->customer["id"] . '"');
	header("location:".($_SERVER['SERVER_PROTOCOL'] == "HTTPS" ? HTTPS_SERVER.DIR_WS_HTTPS_CATALOG : HTTP_SERVER.DIR_WS_CATALOG)."index.php?main_page=account_history_info&order_id=".$_SESSION['order_id']);

	
}else{
	?>
	<body  style="background-color:#e5edf5;">
<form name="checkout_confirmation" action="<?php echo ($_SERVER['SERVER_PROTOCOL'] == "HTTPS" ? HTTPS_SERVER.DIR_WS_HTTPS_CATALOG : HTTP_SERVER.DIR_WS_CATALOG); ?>index.php?main_page=checkout_process" method="post" id="checkout_confirmation" ><input type="hidden" name="securityToken" value="<?php echo $_GET["securityToken"]; ?>"><input type="hidden" name="type" value="<?php echo $type; ?>">
</form>
<script>
document.forms['checkout_confirmation'].submit();
</script>
</body>

<?php	
	//not a valid quickpay transaction
//	header("location: index.php");
}

?>