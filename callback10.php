<?php

//Zen don't accept remote call if POST["security_token"] is not provided. v10 do not use POST...;
//Quickpay transaction	validity is checked in Quickpay payment module before_process()
include($_SERVER["DOCUMENT_ROOT"].dirname($_SERVER["PHP_SELF"])."/includes/configure.php");
?>
<body  style="background-color:#e5edf5;">
<form name="checkout_confirmation" action="<?php echo ($_SERVER['SERVER_PROTOCOL'] == "HTTPS" ? HTTPS_SERVER.DIR_WS_HTTPS_CATALOG : HTTP_SERVER.DIR_WS_CATALOG); ?>index.php?main_page=checkout_process" method="post" id="checkout_confirmation" ><input type="hidden" name="securityToken" value="<?php echo $_GET["securityToken"]; ?>">
</form>
<script>
document.forms['checkout_confirmation'].submit();
</script>
</body>