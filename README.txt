com.webaccessglobal.veritrans
==========================

Veritrans payment processor extension for CiviCRM

This document gives brief description of integrating veritrans extension with civicrm

Resources:
=============
http://docs.veritrans.co.id/vtweb/index.html

Veritrans Brief:
=================
veritrans provides 2 methods : VT-Web and VT-direct.
VT-Web :  Payment will be redirected to the payment page, Veritrans Style and interface kits have been provided Veritrans
VT-direct : Payments will be processed on the merchant side and Merchant has full control over the style and interface.

This extension supports VT-Web method.

Installation:
=================
1. Go to Administer > System Settings > Manage Extensions.
2. Install and Enable the Veritrans extension.

Configuration:
=====================
		
	1. Payment Processor settings:	   	   	     
		-> Go to Administer > System Settings > Payment Processors.
		-> Add veritrans payment processor.		 
		-> Input Merchant Id and Merchant Hash key provided by Veritrans merchant account.
	2. Localization settings:(do these settings before running test cases too)
		-> Go to Administer > Localization > Languages, currencies, locations
		-> Default Currency: IDR
		-> Default Country: Indonesia			 
		-> Save.		
	3. Change the website currency to IDR.
	4. Set Payment Notification URL in merchant account settings which will always be <HOST URL>/civicrm/payment/ipn?processor_name=Veritrans
 





