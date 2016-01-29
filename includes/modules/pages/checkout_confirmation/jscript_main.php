<?php
/**
 * jscript_main
 *
 * @package page
 * @copyright Copyright 2003-2010 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: jscript_main.php 15536 2010-02-20 06:11:54Z drbyte $
 */
?>
<script language="javascript" type="text/javascript"><!--
var submitter = null;
function popupWindow(url) {
  window.open(url,'popupWindow','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,copyhistory=no,width=450,height=320,screenX=150,screenY=150,top=150,left=150')
}

function couponpopupWindow(url) {
  window.open(url,'couponpopupWindow','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,copyhistory=no,width=450,height=320,screenX=150,screenY=150,top=150,left=150')
}

function submitFunction($gv,$total) {
   if ($gv >=$total) {
     submitter = 1;
   }
}
//quickpay changed start
function submitonce(form)
{

  if (!check_agree(form)) {
  var button = document.getElementById("btn_submit");
  button.style.cursor="wait";
  button.disabled = true;
  setTimeout('button_timeout()', 4000);
  return false;
}
}
//quickpay changed end
function button_timeout() {
  var button = document.getElementById("btn_submit");
  button.style.cursor="pointer";
  button.disabled = false;
}
//quickpay added start
function check_agree(TheForm) 
{
  if (TheForm.agree.checked) {
    return true;
  } else {
    alert('<?php echo CONDITION_AGREEMENT_ERROR; ?>');
    return false;
  }
}
//quickpay added end
//--></script>