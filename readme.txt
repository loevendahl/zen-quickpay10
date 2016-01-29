README for Quickpay Advanced Payment module protocol 10



Please contact kl@blkom.dk for questions, comments, feature requests and professional support.


ACKNOWLEDGEMENT
The Quickpay Advanced Payment module was developed by Jakob Høy Biegel. This Module is in parts based
on the code of the Quickpay Standard Module.

Version of this module was elaborated by Leika IT.

Upgrades of Zen Cart Quickpay advanced payment module to protocol 7 and 10 are made by Kim Løvendahl, kl@blkom.dk 2013-2015.
addition of Paii payment module made by Kim Løvendahl, kl@blkom.dk 2014.

IMPORTANT:
Using this module you acknowledge, that the Author can not be made responsible for any kind of damages, 
errors or problems caused by wrong or correct implementation of this module.



REQUIREMENTS:
You must have the following:
- Zen-Cart 1.5.x based webshop 
- QuickPay Payment login (get it here: https.//manage.quickpay.net).
- The php-extensions Curl and SimpleXml must be installed on your webserver in order to use the module for payments.

  
INSTALLATION INSTRUCTIONS:

#####################################################################################
STEP 0
BACKUP BACKUP BACKUP BACKUP BACKUP and lastly BACKUP


#####################################################################################
STEP 2
for new installation copy all files from quickpay_zencart-protocol10 to the webshop directory.

for modified installations, make sure you apply all modifications - marked with "quickpay..." in your webshop files:
	(admin)/orders.php
	(admin)/classes/order.php
	includes/classes/order.php
	includes/templates/tmplate_default/templates/tpl_checkout_confirmation_default.php
	includes/modules/pages/checkout_confirmation/jscript_main.php
 
   
Then copy all other (new) files from quickpay_zencart-protocol10 to the webshop directory:
 
#####################################################################################
STEP 3
Go into the Shop Administration->Modules->Payment, and enable the Online payment module.
(If you have a previous installation of the QuickPay-module then first remove the old version )
Provide Agreement ID/Merchant ID (You wil find these in the QuickPay manager->Integration). 
Provide API keys and private key (You find/generate these in the QuickPay manager->Integration). 
Select which Aquirers and cards/payment options you want to accept, also set up the fee for each payment card/payment option
(Up to 5 options available. But if you need more cards/payment options, 
then please feel free to change the number in line 37 of includes/modules/payment/quickpay_advanced.php)


#####################################################################################
STEP 4
Test your new QuickPay Payment enabled website...

available test cards, see:
http://tech.quickpay.net/appendixes/test/

You can manage the online payment transaction from within your webshop (Administration)->Orders 
 
