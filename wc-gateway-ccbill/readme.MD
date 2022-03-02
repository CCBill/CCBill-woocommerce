
<h1 align="center">
  <br>

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

The CCBill WooCommerce module can be installed either by searching for the hosted WordPress plugin, or by uploading the plugin downloaded from the CCBill GitHub page.

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

**Creating a New Billing Form**

The billing form is the CCBill form that will be displayed to customers after they choose to check out using CCBill. The billing form accepts customer payment information, processes the payment, and returns the customer to your WooCommerce store where a confirmation message is displayed.

*Important*
CCBill provides two types of billing forms. FlexForms is our newest (and recommended) system, but standard forms are still supported. Please select a form type to use with WooCommerce and proceed according to the section for Option 1 or Option 2, according to your selection.

**Option 1: Creating a New Billing Form - FlexForms**

Note: Skip this section if using standard forms

To create a FlexForm form for use with WooCommerce, first ensure "all" is selected in the top Client Account dropdown. FlexForms are not specific to sub accounts, and cannot be managed when a sub account is selected.

Navigate to the FlexSystems tab in the top menu bar and select "FlexForms Payment Links."

All existing forms will be displayed in a table.

Click the "Add New" button in the upper-left to create a new form.

The New Form dialog displays.

** Payment Flow Name **

At the top, enter a name for the new payment flow (this will be different than the form name, as a single form can be used in multiple flows).

** Form Name **

Under Form Name, enter a name for the form.

** Dynamic Pricing **

Under Pricing, check the box to enable dynamic pricing.

**Layout**

Select your desired layout, and save the form.

** Edit the Flow **

Click the arrow button to the left of your new flow to view the details.

Under the green Approve arrow, click the square to modify the action.

** Approval URL **

In the left menu, select "A URL."

Select "Add A New URL" and enter the base URL for your WooCommerce store, followed by: /?wc-api=WC_Gateway_CCBill&Action=CheckoutSuccess

For example, if your WooCommerce store is located at [http://www.test.com](http://www.test.com/), the Approval URL would be: [http://www.test.com/?wc-api=WC_Gateway_CCBill&Action=CheckoutSuccess](http://www.test.com/?wc-api=WC_Gateway_CCBill&Action=CheckoutSuccess)

** URL Name **

Enter a name for this URL. This should be a descriptive name such as "Woo Checkout Success."

** Redirect Time **
Select a redirect time of 1 second using the slider at the bottom and save the form.

** Promote to Live **

Click the "Promote to Live" button to enable your new form to accept payments.

** Note the Flex ID **

Make note of the Flex ID: this value will be entered into the form name when completing the configuration in WooCommerce.

**WebHooks (FlexForms Only)**

Note: Skip this section if using standard forms

As a final step for configuring a FlexForm, select the sub account to be used with WooCommerce from the top Client Account dropdown.

Navigate to the Account Info tab in the top menu bar and select "Sub Account Admin."

Select "Webhooks" from the left menu, then select "Add" to add a new webhook. Webhook URL

Under Webhook URL, enter the base URL for your WooCommerce store, followed by: /?wc-api=WC_Gateway_CCBill

For example, if your WooCommerce store is located at [http://www.test.com](http://www.test.com/), the Approval URL would be: [http://www.test.com//?wc-api=WC_Gateway_CCBill](http://www.test.com//?wc-api=WC_Gateway_CCBill)

Select "NewSaleFailure" and "NewSaleSuccess," then click the Update button to save the Webhook information.

Continue to the Configuration - WooCommerce section of this document.


**Option 2: Creating a New Billing Form - Standard Forms**

Note: Skip this section if using FlexForms.

To create a standard billing form for use with WooCommerce, navigate to the "Form Admin" section of your CCBill admin interface. All existing forms will be displayed in a table.

Click "Create New Form" in the left menu to create your new form.

Select the appropriate option under "Billing Type."  (In most cases, this will
be "Credit Card.")

Select "Standard" under "Form Type" unless you intend to customize your form.

Select the desired layout, and click "Submit" at the bottom of the page.

Your new form has been created, and is visible in the table under "View All Forms."
In this example, our new form is named "201cc."  Be sure to note the name of
your new form, as it will be required in the WooCommerce configuration section.


**Configuring the New Billing Form**

Click the title of the newly-created form to edit it.  In the left menu,
click "Basic."

Under "Basic," select an Approval Redirect Time of 3 seconds, and a
Denial Redirect Time of "Instant."


**Configuring Your CCBill Account**

In your CCBill admin interface, navigate to "Sub Account Admin" and select "Basic" from the left menu.
Site Name

Enter the URL of your WooCommerce store under "Site Name"

**Approval URL**

Under Approval URL, enter the base URL for your WooCommerce store, followed by:
/?wc-api=WC_Gateway_CCBill&Action=CheckoutSuccess

For example, if your WooCommerce store is located at [http://www.test.com](http://www.test.com/), the Approval URL would be:
[http://www.test.com/?wc-api=WC_Gateway_CCBill&Action=CheckoutSuccess](http://www.test.com/?wc-api=WC_Gateway_CCBill&Action=CheckoutSuccess)

If your WooCommerce store is located at [http://www.test.com/woo](http://www.test.com/woo), then the Approval URL would be:
[http://www.test.com/woo/?wc-api=WC_Gateway_CCBill&Action=CheckoutSuccess](http://www.test.com/woo/?wc-api=WC_Gateway_CCBill&Action=CheckoutSuccess)

**Denial URL**

Under Denial URL, enter the base URL for your WooCommerce store, followed by:
/?wc-api=WC_Gateway_CCBill&Action=CheckoutFailure

For example, if your WooCommerce store is located at [http://www.test.com](http://www.test.com/), the Denial URL would be:
[http://www.test.com/?wc-api=WC_Gateway_CCBill&Action=CheckoutFailure](http://www.test.com/?wc-api=WC_Gateway_CCBill&Action=CheckoutFailure)

If your WooCommerce store is located at [http://www.test.com/woo](http://www.test.com/woo), then the Denial URL would be:
[http://www.test.com/woo/?wc-api=WC_Gateway_CCBill&Action=CheckoutFailure](http://www.test.com/woo/?wc-api=WC_Gateway_CCBill&Action=CheckoutFailure)

**Redirect Time**

Select an approval redirect time of 3 seconds, and a denial redirect time of "Instant."

**Background Post**

While still in the "Sub Account Admin" section, select "Advanced" from the left menu.  Notice the top section titled "Background Post Information."  We will be modifying the Approval Post URL and Denial Post URL fields.

**Approval Post URL**
Under Approval Post URL, enter the base URL for your WooCommerce store, followed by:
/?wc-api=WC_Gateway_CCBill&Action=Approval_Post

For example, if your WooCommerce store is located at [http://www.test.com](http://www.test.com/), the Approval URL would be:
[http://www.test.com//?wc-api=WC_Gateway_CCBill&Action=Approval_Post](http://www.test.com//?wc-api=WC_Gateway_CCBill&Action=Approval_Post)

If your WooCommerce store is located at [http://www.test.com/woo](http://www.test.com/woo), then the Approval URL would be:
[http://www.test.com/woo//?wc-api=WC_Gateway_CCBill&Action=Approval_Post](http://www.test.com/woo//?wc-api=WC_Gateway_CCBill&Action=Approval_Post)

**Denial Post URL**
Under Denial Post URL, enter the base URL for your WooCommerce store, followed by:
/?wc-api=WC_Gateway_CCBill&Action=Denial_Post

For example, if your WooCommerce store is located at [http://www.test.com](http://www.test.com/), the Denial URL would be:
[http://www.test.com/?wc-api=WC_Gateway_CCBill&Action=Denial_Post](http://www.test.com/?wc-api=WC_Gateway_CCBill&Action=Denial_Post)

If your WooCommerce store is located at [http://www.test.com/woo](http://www.test.com/woo), then the Denial URL would be:
[http://www.test.com/woo/?wc-api=WC_Gateway_CCBill&Action=Denial_Post](http://www.test.com/woo/?wc-api=WC_Gateway_CCBill&Action=Denial_Post)

## Confirmation
Your CCBill account is now configured. In your CCBill admin interface, navigate to "Sub Account Admin" and ensure the information displayed is correct.

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

## Plugin Name
Contributors: CCBill
Tags: CCBill, payment, gateway, WooCommerce
Requires at least: 3.0.1
Tested up to: 5.8.1
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

