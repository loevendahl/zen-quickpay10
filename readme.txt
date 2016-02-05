README for Quickpay Advanced Payment module protocol 10, payment link version



Please contact kl@blkom.dk for questions, comments, feature requests and professional support.

MAJOR IMPROVEMENTS IN PAYMENT LINK VERSION
A. This version supports basic subscription payment and payment links.
B. There is no data posted to Quickpay gateway through client browser. Payments are handled by API server to server communication and reuseable payment links . This means, that this module is independent of user browser and user technology limitations.

C. Merchant can send a reuseable payment link (from order admin) to a customer if a  transaction has been abrupted for some reason.

D. Simplicity. The basic concept of this version is 

1. get order status from API
2. create order in gateway if no order and payment exists
3. create or update reusable payment link to payment window (same order number)
4. handle webshop order in callback according to status from gateway API

E. Payment status records are not added to order history comments. They are output in order admin seperately from an API status log function instead

Please note:
If using payment links from admin, you could take advantage in implementing a PDF invoice contribution . As is now, the callback function (callback10.php) redirects customers to their account order overview page after succesful payment using admin provided payment links.

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
	includes/templates/tmplate_default/templates/tpl_checkout_confirmation_default.php //please note: Apply to your theme too, if different from standard
	includes/modules/pages/checkout_confirmation/jscript_main.php //please note: Apply to your theme needs, if different from standard (handles agreement to conditions)
 
   
Then copy all other (new) files from quickpay_zencart-protocol10 to your webshop directory:
You wil find the files needed for copying to your admin directory in the folder named "qp10admin".

#####################################################################################
STEP 3
Go into the Shop Administration->Modules->Payment, and enable the Online payment module.
(If you have a previous installation of the QuickPay-module then first remove the old version )
Provide Agreement ID and Merchant ID (You wil find these in the QuickPay manager->Integration). 
Agreement ID is your Payment window Agreement ID
Provide API key for your  API user (You find/generate this in the QuickPay manager->Integration). 
Select which Aquirers and cards/payment options you want to accept, also set up the fee for each payment card/payment option group
(Up to 5 options available. But if you need more cards/payment options, 
then please feel free to change the number in line 42 of includes/modules/payment/quickpay_advanced.php)


#####################################################################################
STEP 4
Test your new QuickPay Payment enabled website...

available test cards, see:
http://tech.quickpay.net/appendixes/test/

You can manage the online payment transaction from within your webshop (Administration)->Orders 
 
