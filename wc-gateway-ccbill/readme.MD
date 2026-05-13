
<h1 align="center">
  <br>
  <a href="https://ccbill.com/doc/ccbill-woocommerce-module"><img src="https://user-images.githubusercontent.com/81383705/156415336-8e3aec07-7391-4b9b-b318-f91ec4e2cdc6.png" width="300"></a>
  <br>
  CCBill WooCommerce Plugin
  <br>
</h1>

<p align="center">
The CCBill payment gateway plugin for WooCommerce allows you to easily configure and accept CCBill
payments on your WooCommerce-enabled Wordpress Website.

There are dozens of providers who offer basic payment processing online, but CCBill is the payment services platform built to care for your buyers, automate your business and help you instantly grow into new markets.
Accept credit cards, debit cards, gift cards, online checks and bank transfers using CCBill – and protect your business with our leading fraud protection and 24/7 billing support for your buyers.
Offering a complete package at easy rates, CCBill’s service includes a payment gateway, merchant account, smart payment forms, mobile tools, multiple currencies and languages, PCI Level 1 compliance, direct settlements and a menu of tools to automate and expand your online business commerce.

With billions of consumers linked to a broad, flexible e-commerce platform, plus dozens of integrated software partners, CCBill represents reliability and integrity to consumers and merchants alike.

[Click here to get started with taking payments online.](https://ccbill.com/)

This plugin requires WooCommerce.

## Upgrade Notice

*Note: Version 3.0.0 includes breaking changes.*  When upgrading an existing installation, please make note of your CCBill account values in the WooCommerce -> Settings -> Payments -> CCBill section of the Wordpress admin, as these values may need to be reentered after upgrading.  Version 3.0.0 adds support for recurring payments when used with the official [WooCommerce Subscriptions](https://woocommerce.com/products/woocommerce-subscriptions/) plugin.

*Note: Version 3.0.1 (released January 12, 2026) contains a fix for a bug that affected version 3.0.0 when WooSubscriptions was not installed*
</p>

<p align="center">
  <a href="https://kb.ccbill.com/CCBill+API">API</a> •
  <a href="https://ccbill.com/kb/">Knowledge Base</a> •
  <a href="https://kb.ccbill.com/FlexForms">FlexForms</a> •
  <a href="https://kb.ccbill.com/CCBill+Pay+Merchant+FAQs">CCBill Pay</a> •
  <a href="https://ccbill.com/contact">Support</a>
</p>

## Installation

Installation involves the following steps:
* Installing the CCBill payment module for WooCommerce
* Configuring your CCBill account for use with WooCommerce
* Configuring the module with your CCBill account information

**Installation Options**

The CCBill payment module for WooCommerce can be installed either by searching for the hosted WordPress plugin, or by uploading the plugin downloaded from the [CCBill website](https://ccbill.com/doc/ccbill-woocommerce-module).

**Installing via WordPress Plugin Directory**

From the WordPress administration menu, navigate to "Plugins."
Type "CCBill" into the text field and click "Search Plugins."
Locate the official CCBill plugin for WooCommerce from the search
results and click the "Install Now" link next to the module title.

**Installing via File Upload**

From the WordPress administration menu, navigate to "Plugins"
and selected "Upload" from the top menu.  Click the "Choose File"
button and select the .zip file downloaded from the CCBill website
or Wordpress plugin directory.

Once the file is selected, click "Install Now" to complete
the installation process.

**Configuring your CCBill Account**

Before using the plugin, it’s necessary to configure a few things in your CCBill account.
Please ensure the CCBill settings are correct, or the payment module will not work.

**Enabling Dynamic Pricing**

Please work with your CCBill support representative to activate "Dynamic Pricing" for your account.
You can verify that dynamic pricing is active by selecting "Feature Summary" under the
"Account Info" tab of your CCBill admin menu.  Dynamic pricing status appears at the
bottom of the "Billing Tools" section.

**Creating a Salt / Encryption Key**

A "salt" is a string of random data used to make your encryption more secure.
You must contact CCBill Support to generate your salt.  Once set, it will be
visible under the "Advanced" section of the "Sub Account Admin" menu.  It will
appear in the "Encryption Key" field of the "Upgrade Security Setup Information"
section.

**Disabling User Management**

Since this account will be used for dynamic pricing transactions rather than
managing user subscription, user management must be disabled.
In your CCBill admin interface, navigate to "Sub Account Admin" and select
"User Management" from the left menu.
Select "Turn off User Management" in the top section.
Under "Username Settings," select "Do Not Collect Usernames and Passwords."

*Important*
CCBill provides two types of integration methods. Advanced Integration is our newest (and recommended) system, but FlexForms Integration is still supported. 
Please see [the official CCBill plugin page](https://ccbill.com/doc/ccbill-woocommerce-module) for detailed instructions for both integration methods.
Subscription support using either integration method will require some account configuration from CCBill support.

## External Services

This plugin connects to api.ccbill.com to collect payment information during checkout in order to process payment.  When a customer places an order in your WooCommerce store and selects CCBill as the payment method, a request is made to CCBill to generate a secure billing token (Advanced Integration) or the customer is then directed to a form hosted by CCBill where payment information is securely entered and processed (FlexForms integration).  The customer is then directed back to your WooCommerce website where the result of the payment (success or failure) is reflected in the order.  All payment information is processed by CCBill and no payment information is stored by or transmitted through your WooCommerce website for a secure checkout experience.  This service is provided by "CCBill, LLC": [terms of use](https://ccbill.com/html_forms/terms_and_conditions-english.html), [Privacy Policy](https://ccbill.com/cs/acf/html/privacy_policy-english.html).

## Changelog
= 1.0 =
* Initial Release

= 1.0.1 =
* Resolved repeated postback when communicating with CCBill

= 1.1.0 =
* Added support for CCBill FlexForms

= 1.1.1 =
* Fixed bug with order amount over 1,000

= 1.2.0 =
* Added support for WebHooks

= 1.3.0 =
* Added check for zero balance

= 1.4.0 =
* Classic forms deprecated

= 1.4.1 =
* Resolved incorrect 500 response code when communicating with CCBill

= 1.4.6 =
* Updated name, tested version, and documentation for compliance with Wordpress Marketplace guidelines

= 2.0.0 =
* Added support for blocks checkout.  NOTE: This version contains breaking changes.  Upgrading to this version from a 1.x version will require you to reenter your CCBill account settings in WooCommerce -> Settings -> Payments -> CCBill

= 3.0.0 =
* Added support for recurring payments with subscriptions using the official [WooCommerce Subscriptions](https://woocommerce.com/products/woocommerce-subscriptions/) plugin.  NOTE: This version contains breaking changes.  Upgrading to this version from a 1.x or 2.x version may require you to reenter your CCBill account settings in WooCommerce -> Settings -> Payments -> CCBill.
* Added support for Advanced Integration for a more seamless checkout experience.
* Currency is now set in WooCommerce rather than a manual settings in the CCBill plugin configuration.
* Added the ability to mark virtual orders as complete upon successful payment.

= 3.0.1 =
* Resolved payment error when Woo Subscriptions is not present

= 3.0.2 =
* Resolved an issue with logging

## CCBill Payment Gateway for WooCommerce
Contributors: CCBill
Tags: CCBill, payment, gateway, WooCommerce
Requires at least: 6.5.0
Tested up to: 6.9.4
Stable tag: trunk
License: Private
License URI: https://ccbill.com/

## CCBill Community

Become part of the CCBill community to get updates on new features, help us improve the platform, and engage with other merchants and developers.

* Follow [@CCBillBIZ on Twitter](https://twitter.com/CCBillBIZ).
* Visit [CCBill's Blog](https://ccbill.com/blog) and read about latest developments in payment processing.

### Resources

* [API Documentation](https://kb.ccbill.com/CCBill+RESTful+Transaction+API)
* [Webhooks User Guide](https://kb.ccbill.com/Webhooks+User+Guide)
* [Knowledge Base](https://kb.ccbill.com/Welcome)
* [Blog](https://ccbill.com/blog)

### Contact CCBill

Get in touch with us if you have questions or need help with CCBill RESTful API.

<p align="left">
  <a href="https://twitter.com/CCBillBIZ">Twitter</a> •
  <a href="https://www.facebook.com/ccbillBIZ/">Facebook</a> •
  <a href="https://ae.linkedin.com/company/ccbill">LinkedIn</a> •
  <a href="https://www.instagram.com/ccbillbiz/">Instagram</a> •
  <a href="https://www.youtube.com/c/CCBillBiz/featured">YouTube</a> •
  <a href="https://ccbill.com/contact">Support</a>
</p>

