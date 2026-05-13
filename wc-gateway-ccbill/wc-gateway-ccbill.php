<?php

/**
 * Plugin Name: CCBill Payment Gateway for WooCommerce
 * Plugin URI: https://ccbill.com/doc/ccbill-woocommerce-module
 * Description: Accept CCBill payments on your WooCommerce website.
 * Version: 3.0.2
 * Author: CCBill
 * Author URI: http://www.ccbill.com/
 * License: GPLv2 or later
 *
 * @package WordPress
 * @author CCBill
 * @since 1.0.0
 */
 
 
 use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
 //use CCBill\WC_Gateway_CCBill\WC_Gateway_CCBill_Blocks;
 
 if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
 
/* Defined minimums and constants */

define( 'WC_CCBILL_MAIN_FILE', __FILE__ );
define( 'WC_CCBILL_PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

add_action( 'plugins_loaded', 'wc_gateway_ccbill_init', 0 );

function wc_gateway_ccbill_init(){

  if (! class_exists('WC_Payment_Gateway') ) {
    return;
  }
  
  load_plugin_textdomain('wc-gateway-ccbill', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');

  class WC_Gateway_CCBill extends WC_Payment_Gateway {

    var $notify_url;
    
    var $liveurl;
    var $testurl;
    var $loading_icon;
    var $baseurl_flex;
    var $oauth_token_url;
    var $transaction_url;
    var $priceVarName;
    var $periodVarName;
    var $account_no;
    var $sub_account_no;
    var $sub_account_no_recurring;
    var $plugin_base_url;
    
    var $integration_method; // Form or Advanced
    var $account_no_advanced;
    var $sub_account_token_generation;
    var $sub_account_charge_nonrecurring;
    var $sub_account_charge_recurring;
    var $frontend_username;
    var $frontend_password;
    var $backend_username;
    var $backend_password;
    
    // var $currency_code;
    // var $test_mode;
    var $form_name;
    var $is_flexform;
    var $datalink_username;
    var $datalink_password;
    var $salt;
    var $markVirtualOrdersCompleteWhenPaid;
    var $debug;
    var $ccbill_currency_codes;
    // var $paymentaction;
    // var $identity_token;
    var $log;
    var $supports_classicforms_integration;
    var $supports_flexforms_integration;
    var $supports_advanced_integration;
    var $advanced_integration;
    var $paymentaction;
    // var $test_mode;
    
    /**
     * Constructor for the gateway.
     *
     * @access public
     * @return void
     */
    public function __construct() {
      
      // The ID has to be set before $this->get_option will work, since get_option relies on the ID being set
      $this->id   = 'wc_gateway_ccbill';
      
      $this->supports_classicforms_integration = array(
        'products'
      );
      
      $this->supports_flexforms_integration = array(
        'products',
        'subscriptions',
        'subscription_cancellation', 
      );
              
      $this->supports_advanced_integration = array(
        'products', 
        'refunds',
        'tokenization',
        'subscriptions',
        'multiple_subscriptions',
        'subscription_cancellation', 
        'subscription_reactivation',
        'subscription_suspension', 
        'subscription_amount_changes',
        'subscription_date_changes',
        /*
        'subscription_payment_method_change_admin',
        'subscription_payment_method_change_customer',
        'subscription_payment_method_change',
        */
      );
      
      $this->ccbill_currency_codes = array( 
          'USD' => '840',
          'EUR' => '978',
          'AUD' => '036',
          'CAD' => '124',
          'GBP' => '826',
          'JPY' => '392');
          
      $this->icon = WC_CCBILL_PLUGIN_URL . '/assets/images/icons/ccbill-50.png';
      $this->loading_icon = WC_CCBILL_PLUGIN_URL . '/assets/images/icons/loading-icon.gif';
      
      
      $this->liveurl           = 'https://bill.ccbill.com/jpost/signup.cgi';
      $this->testurl           = 'https://bill.ccbill.com/jpost/signup.cgi';
      $this->baseurl_flex      = 'https://api.ccbill.com/wap-frontflex/flexforms/';
      $this->oauth_token_url   = 'https://api.ccbill.com/ccbill-auth/oauth/token';
      $this->transaction_url   = 'https://api.ccbill.com/transactions';
      // $this->method_title      = 'CCBill';
      // $this->method_description = 'Pay with your credit card using CCBill';
      $this->method_title       = __('CCBill Payments for WooCommerce', 'woocommerce-payment-gateway-ccbill');
      $this->method_description = __('Pay with your credit card using CCBill', 'woocommerce-payment-gateway-ccbill');
      $this->notify_url        = WC()->api_request_url( 'WC_Gateway_CCBill' );
      $this->priceVarName      = 'formPrice';
      $this->periodVarName     = 'formPeriod';

      // Load the settings.
      $this->init_form_fields();
      $this->init_settings();

      // Define user set variables
      $this->title              = $this->get_option( 'title' );
      $this->description        = $this->get_option( 'description' );
      $this->account_no         = $this->get_option( 'account_no' );
      $this->sub_account_no     = $this->get_option( 'sub_account_no' );
      $this->sub_account_no_recurring = $this->get_option( 'sub_account_no_recurring' );
      
      $this->plugin_base_url = untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/';
      
      $this->integration_method               = $this->get_option( 'integration_method' );
      $this->advanced_integration             = $this->integration_method == 'advanced';
      $this->account_no_advanced              = $this->get_option( 'account_no_advanced' );
      $this->sub_account_token_generation     = $this->get_option( 'sub_account_token_generation' );
      $this->sub_account_charge_nonrecurring  = $this->get_option( 'sub_account_charge_nonrecurring' );
      $this->sub_account_charge_recurring     = $this->get_option( 'sub_account_charge_recurring' );
      $this->frontend_username                = $this->get_option( 'frontend_username' );
      $this->frontend_password                = $this->get_option( 'frontend_password' );
      $this->backend_username                 = $this->get_option( 'backend_username' );
      $this->backend_password                 = $this->get_option( 'backend_password' );      
      
      $this->datalink_username                = $this->get_option( 'datalink_username' );
      $this->datalink_password                = $this->get_option( 'datalink_password' );
      
      // FlexForms supports subscriptions if datalink username and password are provided
      if (isset($this->datalink_username) && !is_null($this->datalink_username) && strlen(trim($this->datalink_username)) > 0 && isset($this->datalink_password) && !is_null($this->datalink_password) && strlen(trim($this->datalink_password)) > 0)
        $this->supports_flexforms_integration[] = 'subscriptions';
      
      // $this->test_mode          = $this->get_option( 'test_mode' );
      $this->has_fields         = $this->advanced_integration; // was false with previous method
      
      /* translators: Proceed to Checkout button label */
      $order_button_text_default = $this->advanced_integration ? "Pay now with CCBill" : "Proceed to Checkout";
      $this->order_button_text = __( $order_button_text_default, 'woocommerce-payment-gateway-ccbill' );
      
      // $this->currency_code      = $this->get_option( 'currency_code' );
      $this->form_name          = $this->get_option( 'form_name' );
      $this->is_flexform        = $this->get_option( 'is_flexform' ) != 'no';
      $this->salt               = $this->get_option( 'salt' );
      $this->debug              = $this->get_option( 'debug' );
      $this->markVirtualOrdersCompleteWhenPaid = $this->get_option( 'markVirtualOrdersCompleteWhenPaid' );

      if($this->is_flexform){
        $this->liveurl = $this->baseurl_flex . $this->form_name;
        $this->priceVarName   = 'initialPrice';
        $this->periodVarName  = 'initialPeriod';
      }// end if

      $this->paymentaction    = $this->get_option( 'paymentaction', 'sale' );

      // Logs
      $this->log = new WC_Logger();
      
      $this->supports = $this->advanced_integration
                      ? $this->supports_advanced_integration : 
                        ($this->is_flexform ? $this->supports_flexforms_integration : $this->supports_classicforms_integration);

      // Actions ---------------------------------------------------      
        
      // Payment listener/API webhook
      // woocommerce_api_wc_gateway_ccbill
       add_action( 'woocommerce_api_' . $this->id, array( $this, 'check_ccbill_response' ) );


      if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
        
        // Save Settings
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        
        // Scheduled subscription payment
        if ($this->advanced_integration) {
            
            add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array( $this, 'scheduled_subscription_payment' ), 10, 2 );
            
            add_action( 'woocommerce_subscriptions_changed_failing_payment_method_' . $this->id, array( $this, 'update_failing_payment_method' ), 10, 2 );
        }
        
        add_action( 'woocommerce_subscription_status_updated', function( $subscription, $newStatus, $oldStatus ) {
          
          $this->logMessage("woocommerce_subscription_status_updated hit.  new status = " . $newStatus);
          
          // If this is not one of the statuses we're interested in, cancel
          if ( $newStatus !== 'cancelled' && $newStatus !== 'pending-cancel' && $newStatus !== 'pending-cancellation' && $newStatus !== 'expired' ) {
            $this->logMessage("woocommerce_subscription_status_updated | this status is not one we handle.  Exiting.");
              return;
          }
          
          $this->logMessage("woocommerce_subscription_status_updated | triggering cancellation of subscription " . $subscription->get_id());
            $this->triggerCCBillCancellation($subscription);
        }, 10, 3 );
        
      } else {
        add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
      }

      if ( ! $this->is_valid_for_use() ) {
        $this->enabled = false; 
      }
      
      add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
    }

    /**
     * Check if this gateway is enabled and available in the user's country
     *
     * @access public
     * @return bool
     */
    function is_valid_for_use() {
      
      if ( ! in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_wc_gateway_ccbill_supported_currencies', array_keys($this->ccbill_currency_codes) ) ) ) {
        return false;
      }

      return true;
    }
    
    public function getCCBillDatalinkUrl ($action, $ccbillSubscriptionId) {
      
        $encodedDatalinkUsername = urlencode(html_entity_decode($this->datalink_username));
        $encodedDatalinkPassword = urlencode(html_entity_decode($this->datalink_password));
        
        $url = 'https://datalink.ccbill.com/utils/subscriptionManagement.cgi?username=' . $encodedDatalinkUsername . '&password=' . $encodedDatalinkPassword . '&clientAccnum=' . $this->account_no . '&action=' . $action . '&subscriptionId=' . $ccbillSubscriptionId;
        
        return $url;
    }
    
    public function triggerCCBillCancellation ( $subscription ) {
      
      $this->logMessage("triggerCCBillCancellation hit.  Retrieving CCBill subscription ID...");      
      
      // Get the subscription and meta data
      $ccbillSubscriptionId = $subscription->get_meta( '_ccbill_subscription_id' );
      $ccbillSyncing = $subscription->get_meta( '_ccbill_syncing' );
      
      // If the order status is currently being updated by CCBill, leave it alone
      if ($ccbillSyncing != null && $ccbillSyncing > 0) {
        $this->logMessage("CCBill Sync in progress.  Exiting.");     
        return;
      }
      
      $this->logMessage("triggerCCBillCancellation | ccbill subscription ID: $ccbillSubscriptionId"); 
      
      $url = $this->getCCBillDatalinkUrl('cancelSubscription', $ccbillSubscriptionId);
      
      $this->logMessage("triggerCCBillCancellation hit.  Issuing request: $url");
      
      $response = wp_remote_get( $url, array() );
      
      // Check for errors
      if ( is_wp_error( $response ) ) {
          $error_message = $response->get_error_message();
          echo "Something went wrong: $error_message";
          $this->logMessage("triggerCCBillCancellation | Something went wrong: $error_message");
      } else {
          // Request was successful, retrieve the body and decode if it's JSON
          $body = wp_remote_retrieve_body( $response );
          $this->logMessage("triggerCCBillCancellation | Request was successful: $body");
          
          $resultValues = $this->parseCCBillDataLinkResult($body);
          
          return $resultValues["results"] == "1";
      }
    }
    
    public function ccbillSubscriptionIsActive ( $subscription ) {
      
      $this->logMessage("ccbillSubscriptionIsActive hit.  Retrieving CCBill subscription ID...");      
      
      // Get the subscription and meta data
      $ccbillSubscriptionId = $subscription->get_meta( '_ccbill_subscription_id' );
      $ccbillSyncing = $subscription->get_meta( '_ccbill_syncing' );
      
      // If the order status is currently being updated by CCBill, leave it alone
      if ($ccbillSyncing != null && $ccbillSyncing > 0) {
        $this->logMessage("CCBill Sync in progress.  Exiting.");     
        return;
      }
      
      $this->logMessage("ccbillSubscriptionIsActive | ccbill subscription ID: $ccbillSubscriptionId"); 
      
      $url = $this->getCCBillDatalinkUrl('viewSubscriptionStatus', $ccbillSubscriptionId);
      
      $this->logMessage("ccbillSubscriptionIsActive hit.  Issuing request: $url");
      
      $response = wp_remote_get( $url, array() );
      
      // Check for errors
      if ( is_wp_error( $response ) ) {
          $error_message = $response->get_error_message();
          echo "Something went wrong: $error_message";
          $this->logMessage("ccbillSubscriptionIsActive | Something went wrong: $error_message");
      } else {
          // Request was successful, retrieve the body and decode if it's JSON
          $body = wp_remote_retrieve_body( $response );
          $this->logMessage("ccbillSubscriptionIsActive | Request was successful: $body");
          
          $resultValues = $this->parseCCBillDataLinkResult($body);
          
          $this->logMessage("ccbillSubscriptionIsActive | Result data: " . json_encode($resultValues));
          
          return $resultValues['subscriptionStatus'] == "1" || $resultValues['subscriptionStatus'] == "2";
      }
    }
    
    public function parseCCBillDataLinkResult ($body) {
      
      $rows = explode("\n", $body);
      
      $keys = explode(",", $rows[0]);
      $values = explode(",", $rows[1]);
      
      $resultValues = [];
      
      for ($i = 0; $i < count($keys); $i++) {
          $resultValues[trim($keys[$i], "\"")] = trim($values[$i], "\"");
      }
      
      return $resultValues;      
    }

    /**
     * Admin Panel Options
     * - Options for bits like 'title' and availability on a country-by-country basis
     *
     * @since 1.0.0
     */
    public function admin_options() {

      ?>
      <h3><?php esc_html_e( 'CCBill Official', 'woocommerce-payment-gateway-ccbill' ); ?></h3>
      <p><?php esc_html_e( 'The CCBill Official plugin allows your customers to check out using CCBill.  The CCBill Official plugin offers two methods of integration: Flex Forms and Advanced.', 'woocommerce-payment-gateway-ccbill' ); ?></p>
      <p><strong>FlexForms</strong> <?php esc_html_e( 'integration is the simplest to set up.  When selected, your customers are taken to a CCBill-hosted form during checkout and returned to your website once payment is complete.  FlexForms can support subscriptions using WooSubscriptions if a Datalink username and password are provided.', 'woocommerce-payment-gateway-ccbill' ); ?></p>
      <p><strong>Advanced</strong> <?php esc_html_e( 'integration provides a more streamlined checkout experience and supports subscriptions using WooSubscriptions by default.  CCBill is integrated invisibly, and your customers never leave your website.  Advanced is the preferred method for subscriptions.', 'woocommerce-payment-gateway-ccbill' ); ?></p>
      <p><strong>Note:</strong> <?php esc_html_e( 'While both Flex Forms and Advanced methods support subscriptions (using WooSubscriptions), switching from one integration method to the other can cause problems if subscriptions are already active.  Please contact CCBill support if this is necessary.', 'woocommerce-payment-gateway-ccbill' ); ?></p>

      <?php if ( $this->is_valid_for_use() ) : ?>

        <table class="form-table">
            <?php
              // Generate the HTML For the settings form.
              $this->generate_settings_html();
            ?>
            
            <script type="text/javascript">
              const ccbillFieldPrefix = 'woocommerce_wc_gateway_ccbill_';
              
              function getCcbillInputElement(elementId) {
                return document.getElementById(ccbillFieldPrefix + elementId);
              }
              
              const isFlexFormCheckbox = getCcbillInputElement('is_flexform');
              var isFlexForm = isFlexFormCheckbox.checked;
              
              const integrationMethodSelect = getCcbillInputElement('integration_method');
              const clientAccountNumberFormsTextField = getCcbillInputElement('account_no');
              const clientAccountNumberAdvancedTextField = getCcbillInputElement('account_no_advanced');
              const clientSubAccountNumberFormsTextField = getCcbillInputElement('sub_account_no');
              const clientSubAccountNumberFormsRecurringTextField = getCcbillInputElement('sub_account_no_recurring');
              const clientSubAccountNumberForTokenGenerationTextField = getCcbillInputElement('sub_account_token_generation');
              const clientSubAccountNumberForNonRecurringTextField = getCcbillInputElement('sub_account_charge_nonrecurring');
              const clientSubAccountNumberForRecurringTextField = getCcbillInputElement('sub_account_charge_recurring');
              const frontendUsernameTextField = getCcbillInputElement('frontend_username');
              const frontendPasswordTextField = getCcbillInputElement('frontend_password');
              const backendUsernameTextField = getCcbillInputElement('backend_username');
              const backendPasswordTextField = getCcbillInputElement('backend_password');
              const formNameTextField = getCcbillInputElement('form_name');
              const datalinkUsernameTextField = getCcbillInputElement( 'datalink_username' );
              const datalinkPasswordTextField = getCcbillInputElement( 'datalink_password' );
              const saltTextField = getCcbillInputElement('salt');
              
              const advancedMethodFields = [
                clientAccountNumberAdvancedTextField,
                clientSubAccountNumberForTokenGenerationTextField,
                clientSubAccountNumberForNonRecurringTextField,
                clientSubAccountNumberForRecurringTextField,
                frontendUsernameTextField,
                frontendPasswordTextField,
                backendUsernameTextField,
                backendPasswordTextField
              ];
              
              const formsMethodFields = [
                clientAccountNumberFormsTextField,
                isFlexFormCheckbox,
                formNameTextField,
                clientSubAccountNumberFormsTextField,
                clientSubAccountNumberFormsRecurringTextField,
                datalinkUsernameTextField,
                datalinkPasswordTextField,
                saltTextField
              ];
              
              function setFieldRowVisibility(inputElement, isVisible) {
                
                if (isVisible) {
                  inputElement.closest('tr').style.display = '';
                }
                else {
                  inputElement.closest('tr').style.display = 'none';
                }                
              }
              
              function setFieldRowsVisibility(inputElements, isVisible) {
                
                inputElements.forEach(function(inputElement, index) {
                  setFieldRowVisibility(inputElement, isVisible);
                });
              }
              
              function updateCcbillFormVisibility(selectedValue)
              {
                if (selectedValue == "forms") {
                  setFieldRowsVisibility(advancedMethodFields, false);
                  setFieldRowsVisibility(formsMethodFields, true);
                  
                  if (isFlexFormCheckbox.checked) {
                    integrationMethodSelect.options[0].textContent = "Flex Forms";
                    setFieldRowVisibility(isFlexFormCheckbox, false);
                    formNameTextField.labels[0].innerHTML = 'FlexForm ID <span class="woocommerce-help-tip" data-tip="The ID of the CCBill FlexForm used to collect payment" aria-label="The ID of the CCBill FlexForm used to collect payment"></span>';
                    // var formNameLabel = document.querySelector("label[for='" + ccbillFieldPrefix + "form_name']");
                    // formNameLabel.innerHTML('FlexForm ID <span class="woocommerce-help-tip" data-tip="The ID of the CCBill FlexForm used to collect payment"></span>');
                  }
                  else {
                    integrationMethodSelect.options[0].textContent = "Forms";
                  }
                }
                else {
                  setFieldRowsVisibility(advancedMethodFields, true);
                  setFieldRowsVisibility(formsMethodFields, false);
                }
                
                // Update the integration method dropdown to FlexForms unless the isFlexForm box is not checked
                if (isFlexFormCheckbox.checked) {
                    integrationMethodSelect.options[0].textContent = "Flex Forms";
                }
                
                var consoleMessage = (selectedValue == "forms" ? (isFlexFormCheckbox.checked ? "Flex" : "Classic") + " Forms" : "Advanced") + " integration selected.";
                
                console.log(consoleMessage);
              }
              
              integrationMethodSelect.addEventListener('change', (event) => {
                const selectedValue = event.target.value;
                updateCcbillFormVisibility(selectedValue);
                console.log('Selected value:', selectedValue);
                // Perform other actions with the selected value
              });
              
              updateCcbillFormVisibility(integrationMethodSelect.value);
              
            </script>

            <?php if ($this->is_flexform == true) : ?>
            <script type="text/javascript">
            /*
                isFlexForm = true;
                isFlexFormCheckbox.closest('tr').style.display = 'none';
                // .parentElement.parentElement.parentElement.parentElement.style.display = 'none';
                // var formNameLabel = jQuery("label[for='" + ccbillFieldPrefix + "form_name']");
                var formNameLabel = document.querySelector("label[for='" + ccbillFieldPrefix + "form_name']");
                // formNameLabel.html('FlexForm ID <span class="woocommerce-help-tip" data-tip="The ID of the CCBill FlexForm used to collect payment"></span>');
                formNameLabel.innerHTML = 'FlexForm ID <span class="woocommerce-help-tip" data-tip="The ID of the CCBill FlexForm used to collect payment"></span>';
                */
            </script>
            <?php endif; ?>

        </table><!--/.form-table-->

      <?php else : ?>
        <div class="inline error"><p><strong><?php esc_html_e( 'Gateway Disabled', 'woocommerce-payment-gateway-ccbill' ); ?></strong>: <?php esc_html_e( 'CCBill does not support your store currency.  CCBill supports the following currencies: ' . array_keys($this->ccbill_currency_codes), 'woocommerce-payment-gateway-ccbill' ); ?></p></div>
      <?php
        endif;
    }
        
    public function get_year_options() {
      
      $currentYear = date("Y");
      
      $output = "";
      
      for ($i = 0; $i < 15; $i++) {
        $yearValue = $currentYear + $i;
        $output .= "<option value=\"$yearValue\">$yearValue</option>\r\n";
      }
      
      return $output;
    }
    
    function get_user_ip()
    {
        // Get real visitor IP behind CloudFlare network
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
                  $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
                  $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        }
        $client  = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote  = $_SERVER['REMOTE_ADDR'];
    
        if(filter_var($client, FILTER_VALIDATE_IP))
        {
            $ip = $client;
        }
        elseif(filter_var($forward, FILTER_VALIDATE_IP))
        {
            $ip = $forward;
        }
        else
        {
            $ip = $remote;
        }
    
        return $ip;
    }
    
    public function get_store_ccbill_currency_code() { 
      
      // Get the store currency
      $wooCurrency = get_woocommerce_currency();
      
      return $this->get_ccbill_currency_code($wooCurrency);
    }
    
    public function get_ccbill_currency_code($wooCurrency) {
      
      // Return the CCBill currency code for the store currency, if it exists
      if (array_key_exists($wooCurrency, $this->ccbill_currency_codes)) {
        return $this->ccbill_currency_codes[$wooCurrency];
      }
      return null;
    }
    
    public function get_recurring_cart_items() {
        $recurring_items = array();
        
        if ( ! WC()->cart ) {
            return $recurring_items; // Return empty array if cart is not initialized
        }
        
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            $product = $cart_item['data'];
        
            // Check if the product is a subscription product type
            if ( $product->is_type( 'subscription' ) || $product->is_type( 'variable-subscription' ) ) {
                $recurring_items[ $cart_item_key ] = $cart_item;
            }
        }
        
        return $recurring_items;
    }
    
    public function get_cart_recurring_item_count() {
      
        if ( ! $this->cart_contains_recurring_items() )
            return 0;
      
        // Get the cart
        $cart = WC()->cart;
        
        if ( !isset( $cart) )
            return 0;
        
        $recurringItems = $this->get_recurring_cart_items();
        
        return count($recurringItems);        
    }
    
    public function cart_contains_recurring_items() {
      
        // Return false if the WooSubscriptions plugin is not active        
        if ( ! is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) )
            return false;
      
        return WC_Subscriptions_Cart::cart_contains_subscription();
    }
    
    public function get_order_subscription_initial_period_days( $orderId )
    {
        $subscriptions = wcs_get_subscriptions_for_order( $orderId );
        
        // If there is no subscription, return zero
        if ( count( array_values( $subscriptions ) ) == 0 )
            return 0;
            
        $subscription = array_values( $subscriptions )[0];        
        $trialEndDate = $subscription->get_date( 'next_payment' );
        $trialPeriodInDays = $this->get_days_from_present($trialEndDate);
                        
        return $trialPeriodInDays;
    }
    
    public function get_days_from_present ( $date ) {
      
        $currentDate = date_create( date("Y-m-d") );
        $targetDate = date_create( $date );
        
        $diff = date_diff( $currentDate, $targetDate, true );
        
        return $diff->format("%d");
    }
    
    public function payment_scripts() {
      
        // Token script are only used on cart and checkout pages
        if( ! is_cart() && ! is_checkout() && ! isset( $_GET[ 'pay_for_order' ] ) ) { 
          return;
        }
        
        // If our payment gateway is disabled or required fields are not yet, exit now
        if ( 'no' === $this->enabled  ||
              empty( $this->frontend_username ) ||
              empty( $this->frontend_password ) ||
              ! is_ssl()) {
          return;
        }
        
        wp_enqueue_script( 'ccbill_advanced_widget_js', 'https://js.ccbill.com/v1.9.0/ccbill-advanced-widget.js' );
      
    }
    
    /**
     * Output payment form: CCBill Advanced Widget
     * Note: without this function, the module displays the payment method description
     */
    public function payment_fields() {
      
        $recurringItemCount = 0;
      
        if ($this->cart_contains_recurring_items()) {
          
            // Fill in recurring data
            $recurringItemCount = $this->get_cart_recurring_item_count();
            // $cart = WC()->cart->get_cart();
          
            if(WC_Subscriptions_Cart::cart_contains_subscription()) {
                // echo('<br/>this cart contains a subscription');
            }
        }
      
        // If the advanced method is not used, echo the description and exit
        if ( !$this->advanced_integration ) {
          echo wpautop( wp_kses_post( $this->description) );
          
          // echo wpautop( wp_kses_post( 'The cart contains ' . $recurringItemCount . ' recurring items.' ) );
          
          // echo json_encode($cart);
          
          return;
        }          
       
        // Get the OAuth token for the front end
        $authToken = $this->get_oauth_token_frontend();
        $authTokenUsername = esc_js( $this->frontend_username );
        $authTokenPassword = esc_js( $this->frontend_password );
        $currencyCode = $this->get_store_ccbill_currency_code();
        $userIpAddress = $this->get_user_ip();        
      
        ?>
        <script>
            window.ccbillDebug = <?php echo ($this->debug == true ? "true" : "false"); ?>;
        
            function ccbillDebugLog(message) {
                if (window.ccbillDebug == true)
                    console.log(message);
            }
        </script>
        
        <style>
          label {
            /* display:block; */
          }
        </style>
        <div id="ccbill-payment-form"></div>
        <input type="hidden" name="ccbill_token" id="ccbill_token" data-ccbill="ccbill_token" />
        <input type="hidden" name="email" id="email" data-ccbill="email" />
        <input type="hidden" name="firstName" id="firstName" data-ccbill="firstName" />
        <input type="hidden" name="lastName" id="lastName" data-ccbill="lastName" />
        <input type="hidden" name="address1" id="address1" data-ccbill="address1" />
        <input type="hidden" name="address2" id="address2" data-ccbill="address2" />
        <input type="hidden" name="city" id="city" data-ccbill="city" />
        <input type="hidden" name="country" id="country" data-ccbill="country" />
        <input type="hidden" name="state" id="state" data-ccbill="state" />
        <input type="hidden" name="postalCode" id="postalCode" data-ccbill="postalCode" />
        <input type="hidden" name="phoneNumber" id="phoneNumber" data-ccbill="phoneNumber" />
        <input type="hidden" name="ipAddress" id="ipAddress" data-ccbill="ipAddress" value="<?php echo esc_js( $userIpAddress ); ?>" />
        <input type="hidden" name="currencyCode" id="currencyCode" data-ccbill="currencyCode" value="<?php echo esc_js( $currencyCode ); ?>" />
        
        <label for="nameOnCard">Name on Card</label><br/>
        <input type="text" id="nameOnCard" data-ccbill="nameOnCard" /><br/>
        
        <label for="cardNumber">Card Number</label><br/>
        <input type="text" id="cardNumber" data-ccbill="cardNumber" /><br/>
        
        <label for="expMonth">Expiration</label><br/>
        <select id="expMonth" data-ccbill="expMonth">
          <option value="chooseOne" selected>Select Month</option>
          <option value="01">01 - January</option>
          <option value="02">02 - February</option>
          <option value="03">03 - March</option>
          <option value="04">04 - April</option>
          <option value="05">05 - May</option>
          <option value="06">06 - June</option>
          <option value="07">07 - July</option>
          <option value="08">08 - August</option>
          <option value="09">09 - September</option>
          <option value="10">10 - October</option>
          <option value="11">11 - November</option>
          <option value="12">12 - December</option>
        </select>
        
        <select id="expYear" data-ccbill="expYear">
          <option value="chooseOne" selected>Select Year</option>
          <?php echo $this->get_year_options() ?>
        </select><br/>
        
        <label for="cvv2">CVV</label><br/>
        <input type="text" id="cvv2" data-ccbill="cvv2" />
        
        <style>
            #payment-processing {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background-color: white;
                padding: 20px;
                border-radius: 10px;
                z-index: 9999;
                font-size: 20px; 
                margin: 80px; 
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); 
                text-align: center; 
                display: none;
            }
            #payment-processing.active {
              display: block;
            }
        </style>
        
        <script>
        
        ccbillDebugLog('script loading...');
        
        var oauthToken = '<?php echo $authToken; ?>';
        
        var applicationId = '<?php echo $authTokenUsername; ?>';
        var clientAccnum = '<?php echo $this->account_no_advanced; ?>';
        var clientSubacc = '<?php echo $this->sub_account_token_generation; ?>';
        var clearPaymentInfo = null;
        var clearCustomerInfo = null;
        var timeToLive = null;
        var numberOfUse = null;
        
        var widget = new ccbill.CCBillAdvancedWidget(applicationId);
        
        var loadingElementCreated = false;
          
        function getPaymentToken() {
                    
          if (window.gettingToken || window.getPaymentTokenDisabled)
              return;
          else
              window.gettingToken = true;
              
          ccbillDebugLog('Disabling checkout button');
          document.getElementById('place_order').classList.add('disabled');
          document.getElementById('place_order').disabled = true;
          ccbillDebugLog('Checkout button disabled');
           
          // Set CCBill form fields from billing fields
          try {
            
            ccbillDebugLog('Setting CCBill form fields...');
            
            var billingFieldPrefix = 'billing_';
            
            var testField = document.getElementById(billingFieldPrefix + 'first_name') ;
            
            // Use a different field prefix if this is blocks checkout
            if (testField == null) {
              ccbillDebugLog("testField is null: ");
              billingFieldPrefix = 'billing-';
              document.getElementById('email').value = document.getElementById('email').value;
            }
            else {
              ccbillDebugLog("testField is not null: ");// JSON.stringify(testField));
              document.getElementById('email').value = document.getElementById('billing_email').value;
            }
            
            // Set field data
            document.getElementById('firstName').value   = document.getElementById(billingFieldPrefix + 'first_name').value;
            document.getElementById('lastName').value    = document.getElementById(billingFieldPrefix + 'last_name').value;
            document.getElementById('address1').value    = document.getElementById(billingFieldPrefix + 'address_1').value;
            document.getElementById('address2').value    = document.getElementById(billingFieldPrefix + 'address_2').value;
            document.getElementById('city').value        = document.getElementById(billingFieldPrefix + 'city').value;
            document.getElementById('country').value     = document.getElementById(billingFieldPrefix + 'country').value;
            document.getElementById('state').value       = document.getElementById(billingFieldPrefix + 'state').value;
            document.getElementById('postalCode').value  = document.getElementById(billingFieldPrefix + 'postcode').value;
            document.getElementById('phoneNumber').value = document.getElementById(billingFieldPrefix + 'phone').value;
            
            ccbillDebugLog('Form fields set.');
            
          }
          catch(ex){
            ccbillDebugLog('An error occurred while setting the form fields: ' + ex);
          }
          
          try {
            ccbillDebugLog('Retrieving a payment token...');
              
              const result = widget.createPaymentToken(oauthToken, clientAccnum, clientSubacc);
              result.then(
                  (data) => {
                      ccbillDebugLog("Payment token received successfully");
                       // Assign the token value to the form field
                       document.getElementById('ccbill_token').value = data.paymentTokenId;
                      return data.json();
                  },
                  (error) => {
                      ccbillDebugLog("An error occurred while retrieving the payment token");
                      document.getElementById('place_order').classList.remove('disabled');
                      document.getElementById('place_order').disabled = false;
                      return error.json();
                  }).then(json => {       
                       // Assign the token value to the form field
                       document.getElementById('ccbill_token').value = json.paymentTokenId; 
                       
                       successCallback(json);
                  }).catch((error) => {
                  console.error("An error occurred while retrieving the payment token (2): [" + error + "]");
                  document.getElementById('place_order').classList.remove('disabled');
                  document.getElementById('place_order').disabled = false;
              });
              ccbillDebugLog(`Payment token generation complete`);
          } catch (error) {
              const errors = [];
              
              error.forEach(function(item) {
                const msg = item.message.split(".");
                errors.push(msg[1]);
              });
              
              console.error(`An error occurred while retrieving the payment token ` + JSON.stringify(errors));
              alert("ERROR: Unable to generate Payment Token: " + JSON.stringify(errors));
          }
          
          document.getElementById('payment-processing').classList.remove('active');
          
          // TODO: remove this after troubleshooting
          window.gettingToken = false;
          return false;
          
        }
        
        var successCallback = function( data ) {
          
          ccbillDebugLog("Success callback.  Data: " + JSON.stringify(data));
          
          window.getPaymentTokenDisabled = true;
          
          ccbillDebugLog("Submitting form for payment");
          
          // Submit the form 
          window.checkoutForm.submit();
        }
        
        // window.formInitialized = false;
        // window.gettingToken = false;
        
        jQuery( function( $ ){
        
          if (!window.formInitialized) {
            window.formInitialized = true;
            ccbillDebugLog('Initializing form...');
            const checkoutForm = $( 'form.woocommerce-checkout' );
            ccbillDebugLog('Checkut Forms count: ' + checkoutForm.length);
            checkoutForm.on( 'checkout_place_order', getPaymentToken );
            
            window.checkoutForm = checkoutForm;
          }
        
        });
        
        ccbillDebugLog("Script loaded.")
        
        </script>
        <div id="payment-processing"><b>PLEASE WAIT</b><br>Processing your payment ...</div>
        <?php 
    } // end payment_fields
    
    public function validate_fields() {
      
      // Some fields are only required for advanced integration and not for forms
      if( $this->integration_method == 'advanced' ) {
        
        // The billing token should be automatically provided for advanced integration,
        // but check it here anyway.
        if( empty( $_POST[ 'ccbill_token' ]  ) ) {
          wc_add_notice( 'Error: Billing token not provided.', 'error' );
          return false;
        }
        if( empty( $_POST[ 'ipAddress' ] ) ) {
          wc_add_notice( 'IP Address was not captured successfully.', 'error' );
          return false;
        }
      }
      
      return true;
     
    }

    /**
     * Initialize Gateway Settings Form Fields
     *
     * @access public
     * @return void
     */
    function init_form_fields() {
      
      $this->form_fields = array(
        'enabled' => array(
          /* translators: Checkbox title to enable or disable the plugin */
          'title'   => __( 'Enable/Disable', 'woocommerce-payment-gateway-ccbill' ),
          'type'    => 'checkbox',
          /* translators: Checkbox label to enable or disable the plugin */
          'label'   => __( 'Enable CCBill', 'woocommerce-payment-gateway-ccbill' ),
          'default' => 'yes'
        ),
        'title' => array(
          /* translators: Plugin title that customers will see when checking out */
          'title'       => __( 'Title', 'woocommerce-payment-gateway-ccbill' ),
          'type'        => 'text',
          /* translators: Description of the plugin title */
          'description' => __( 'This is the title your customers will see during checkout.' ),
          /* translators: Plugin title default value */
          'default'     => __( 'CCBill', 'woocommerce-payment-gateway-ccbill' ),
          'desc_tip'    => true,
        ),
        'description' => array(
          /* translators: Plugin description that customers will see when checking out */
          'title'       => __( 'Description', 'woocommerce-payment-gateway-ccbill' ),
          'type'        => 'textarea',
          /* translators: Description of the plugin description */
          'description' => __( 'This is the description your customers will see during checkout.', 'woocommerce-payment-gateway-ccbill' ),
          /* translators: Plugin description default value */
          'default'     => __( 'Pay with your credit card via CCBill.', 'woocommerce-payment-gateway-ccbill' )
        ),
        /*
        'currency_code' => array(
          / * translators: The title for the currency form name field * /
          'title'       => __( 'Currency', 'woocommerce-payment-gateway-ccbill' ),
          'type'        => 'select',
          / * translators: The description for the currency form name field * /
          'description' => __( 'The currency in which payments will be made.', 'woocommerce-payment-gateway-ccbill' ),
          'options'     => $this->ccbill_currency_codes,
          'desc_tip'    => true
        ),
        */
        'integration_method' => array(
          /* translators: The title for the integration method form name field */
          'title'       => __( 'Integration Method', 'woocommerce-payment-gateway-ccbill' ),
          'type'        => 'select',
          /* translators: The description for the integration method form name field */
          'description' => __( 'Select Forms integration for the simplest approach, or Advanced integration for additional subscript management features.', 'woocommerce-payment-gateway-ccbill' ),
          'default'     => 'forms',
          'options'     => array( 'forms' => 'Forms',
                                  'advanced' => 'Advanced'),
          'desc_tip'    => true
        ),
        'account_no' => array(
          /* translators: The title for the CCBill client account number field */
          'title'       => __( 'Client Account Number', 'woocommerce-payment-gateway-ccbill' ),
          'type'        => 'text',
          /* translators: The description for the CCBill client account number field */
          'description' => __( 'Please enter your six-digit CCBill client account number; this is needed in order to take payment via CCBill.', 'woocommerce-payment-gateway-ccbill' ),
          'default'     => '',
          'desc_tip'    => true,
          'placeholder' => 'XXXXXX'
        ),
        'sub_account_no' => array(
          /* translators: The title for the CCBill client subaccount number field */
          'title'       => __( 'Client SubAccount Number for Non-Recurring Purchases', 'woocommerce-payment-gateway-ccbill' ),
          'type'        => 'text',
          /* translators: The description for the CCBill client subaccount number field */
          'description' => __( 'Please enter your four-digit CCBill client sub account number used for non-recurring purchases; this is needed in order to take payment via CCBill.', 'woocommerce-payment-gateway-ccbill' ),
          'default'     => '',
          'desc_tip'    => true,
          'placeholder' => 'XXXX'
        ),
        'sub_account_no_recurring' => array(
          /* translators: The title for the CCBill client subaccount number field */
          'title'       => __( 'Client SubAccount Number for Recurring Purchases', 'woocommerce-payment-gateway-ccbill' ),
          'type'        => 'text',
          /* translators: The description for the CCBill client subaccount number field */
          'description' => __( 'Please enter your four-digit CCBill client sub account number used for recurring purchases; this is needed in order to take payment via CCBill.', 'woocommerce-payment-gateway-ccbill' ),
          'default'     => '',
          'desc_tip'    => true,
          'placeholder' => 'XXXX'
        ),
        'account_no_advanced' => array(
          /* translators: The title for the CCBill client account number field */
          'title'       => __( 'Client Account Number', 'woocommerce-payment-gateway-ccbill' ),
          'type'        => 'text',
          /* translators: The description for the CCBill client account number field */
          'description' => __( 'Please enter your six-digit CCBill client account number; this is needed in order to take payment via CCBill.', 'woocommerce-payment-gateway-ccbill' ),
          'default'     => '',
          'desc_tip'    => true,
          'placeholder' => 'XXXXXX'
        ),
        'sub_account_token_generation' => array(
          /* translators: The title for the CCBill client subaccount number field used for payment token generation */
          'title'       => __( 'Client SubAccount Number for Token Generation', 'woocommerce-payment-gateway-ccbill' ),
          'type'        => 'text',
          /* translators: The description for the CCBill client subaccount number field */
          'description' => __( 'Please enter your four-digit CCBill client sub account number used for token generation; this is needed in order to take payment via CCBill.', 'woocommerce-payment-gateway-ccbill' ),
          'default'     => '',
          'desc_tip'    => true,
          'placeholder' => 'XXXX'
        ),
        'sub_account_charge_nonrecurring' => array(
          /* translators: The title for the CCBill client subaccount number field */
          'title'       => __( 'Client SubAccount Number for Non-Recurring Purchases', 'woocommerce-payment-gateway-ccbill' ),
          'type'        => 'text',
          /* translators: The description for the CCBill client subaccount number field */
          'description' => __( 'Please enter your four-digit CCBill client sub account number used for non-recurring purchases; this is needed in order to take payment via CCBill.', 'woocommerce-payment-gateway-ccbill' ),
          'default'     => '',
          'desc_tip'    => true,
          'placeholder' => 'XXXX'
        ),
        'sub_account_charge_recurring' => array(
          /* translators: The title for the CCBill client subaccount number field */
          'title'       => __( 'Client SubAccount Number for Recurring Purchases', 'woocommerce-payment-gateway-ccbill' ),
          'type'        => 'text',
          /* translators: The description for the CCBill client subaccount number field */
          'description' => __( 'Please enter your four-digit CCBill client sub account number used for recurring (subscription) purchases; this is needed in order to take payment via CCBill.', 'woocommerce-payment-gateway-ccbill' ),
          'default'     => '',
          'desc_tip'    => true,
          'placeholder' => 'XXXX'
        ),
        'frontend_username' => array(
          /* translators: The title for the frontend_username form name field */
          'title'       => __( 'Frontend Username', 'woocommerce-payment-gateway-ccbill' ),
          'type'        => 'text',
          /* translators: The description for the frontend_username form name field */
          'description' => __( 'The front end username used to create charges via CCBill.  This credential must be obtained from CCBill client support.', 'woocommerce-payment-gateway-ccbill' ),
          'default'     => '',
          'desc_tip'    => true,
          'placeholder' => ''
        ),
        'frontend_password' => array(
          /* translators: The title for the frontend_password form name field */
          'title'       => __( 'Frontend Password', 'woocommerce-payment-gateway-ccbill' ),
          'type'        => 'text',
          /* translators: The description for the frontend_password form name field */
          'description' => __( 'The front end password used to create charges via CCBill.  This credential must be obtained from CCBill client support.', 'woocommerce-payment-gateway-ccbill' ),
          'default'     => '',
          'desc_tip'    => true,
          'placeholder' => ''
        ),
        'backend_username' => array(
          /* translators: The title for the backend_username form name field */
          'title'       => __( 'Backend Username', 'woocommerce-payment-gateway-ccbill' ),
          'type'        => 'text',
          /* translators: The description for the backend_username form name field */
          'description' => __( 'The back end username used to create charges via CCBill.  This credential must be obtained from CCBill client support.', 'woocommerce-payment-gateway-ccbill' ),
          'default'     => '',
          'desc_tip'    => true,
          'placeholder' => ''
        ),
        'backend_password' => array(
          /* translators: The title for the backend_password form name field */
          'title'       => __( 'Backend Password', 'woocommerce-payment-gateway-ccbill' ),
          'type'        => 'text',
          /* translators: The description for the backend_password form name field */
          'description' => __( 'The back end password used to create charges via CCBill.  This credential must be obtained from CCBill client support.', 'woocommerce-payment-gateway-ccbill' ),
          'default'     => '',
          'desc_tip'    => true,
          'placeholder' => ''
        ),
        'form_name' => array(
          /* translators: The title for the CCBill form name field */
          'title'       => __( 'Form Name', 'woocommerce-payment-gateway-ccbill' ),
          'type'        => 'text',
          /* translators: The description for the CCBill form name field */
          'description' => __( 'The name of the CCBill form used to collect payment', 'woocommerce-payment-gateway-ccbill' ),
          'default'     => '',
          'desc_tip'    => true,
          'placeholder' => 'XXXcc'
        ),
        'datalink_username' => array(
          /* translators: The title for the CCBill datalink_username field */
          'title'       => __( 'Datalink Username', 'woocommerce-payment-gateway-ccbill' ),
          'type'        => 'text',
          /* translators: The description for the CCBill datalink_username field */
          'description' => __( 'required for using subscriptions with FlexForms', 'woocommerce-payment-gateway-ccbill' ),
          'default'     => '',
          // 'desc_tip'    => true,
          'placeholder' => 'XXXXXXXX'
        ),
        'datalink_password' => array(
          /* translators: The title for the CCBill datalink_password field */
          'title'       => __( 'Datalink Password', 'woocommerce-payment-gateway-ccbill' ),
          'type'        => 'text',
          /* translators: The description for the CCBill datalink_password field */
          'description' => __( 'required for using subscriptions with FlexForms', 'woocommerce-payment-gateway-ccbill' ),
          'default'     => '',
          /// 'desc_tip'    => true,
          'placeholder' => 'XXXXXXXX'
        ),
        'is_flexform' => array(
          /* translators: The title for the CCBill is_flexform field */
          'title'       => __( 'Flex Form', 'woocommerce-payment-gateway-ccbill' ),
          'type'        => 'checkbox',
          /* translators: The label for the CCBill is_flexform field */
          'label'       => __( 'Check this box if the form name provided is a CCBill FlexForm', 'woocommerce-payment-gateway-ccbill' ),
          'default'     => 'yes',
          'desc_tip'    => true,
          /* translators: The description for the CCBill is_flexform field */
          'description' => __( 'Check this box if the form name provided is a CCBill FlexForm', 'woocommerce-payment-gateway-ccbill' ),
        ),
        'salt' => array(
          /* translators: The title for the salt form name field */
          'title'       => __( 'Salt', 'woocommerce-payment-gateway-ccbill' ),
          'type'        => 'text',
          /* translators: The description for the salt form name field */
          'description' => __( 'The salt value is used by CCBill to verify the hash and can be obtained in one of two ways: (1) Contact client support and receive the salt value, OR (2) Create your own salt value (up to 32 alphanumeric characters) and provide it to client support.', 'woocommerce-payment-gateway-ccbill' ),
          'default'     => '',
          'desc_tip'    => true,
          'placeholder' => ''
        ),
        /*
        'test_mode' => array(
          / * translators: The title for the test_mode field * /
          'title'       => __( 'Test Mode', 'woocommerce-payment-gateway-ccbill' ),
          'type'        => 'checkbox',
          / * translators: The description for the test_mode  field * /
          'description' => __( 'Enable sandbox/test mode', 'woocommerce-payment-gateway-ccbill' ),
          'default'     => 'no',
          'desc_tip'    => true,
          'placeholder' => ''
        ),
        */
        'markVirtualOrdersCompleteWhenPaid' => array(
          /* translators: The title for the debug log field */
          'title'       => __( 'Virtual Orders', 'woocommerce-payment-gateway-ccbill' ),
          'type'        => 'checkbox',
          /* translators: The label for the debug log field */
          'label'       => __( 'Mark Virtual Orders Complete When Paid', 'woocommerce-payment-gateway-ccbill' ),
          'default'     => 'yes',
          /* translators: The description for the debug log field */
          'description' => sprintf( __( 'Automatically mark an order as <i>Completed</i> when paid if the order contains only virtual items', 'woocommerce-payment-gateway-ccbill' ), sanitize_file_name( wp_hash( 'ccbill' ) ) ),
        ),
        'debug' => array(
          /* translators: The title for the debug log field */
          'title'       => __( 'Debug Log', 'woocommerce-payment-gateway-ccbill' ),
          'type'        => 'checkbox',
          /* translators: The label for the debug log field */
          'label'       => __( 'Enable logging', 'woocommerce-payment-gateway-ccbill' ),
          'default'     => 'no',
          /* translators: The description for the debug log field */
          'description' => sprintf( __( 'Log CCBill events, such as IPN requests, inside <code>woocommerce/logs/ccbill-%s.txt</code>', 'woocommerce-payment-gateway-ccbill' ), sanitize_file_name( wp_hash( 'ccbill' ) ) ),
        )
      );
    }

    /*
        Generate an MD5 hash given the following parameters:
        - formattedInitialPrice: a decimal value with two places 
          representing the initial price (price charged now)
        - initialPeriodInDays: an integer representing the initial period in days 
          (set to 2 for non-recurring and between 2 and 365 for recurring)
        - currencyCode: an integer representing the 3-digit currency code to be used for the transaction
          (ccbill currency codes -- USD=840, EUR=978, GBP=826, CAD=124, AUD=036, JPY=392)
        - Encyrption Key (Salt): a unique encryption key created by the CCBill Merchant Support team
    */
    function get_digest($formattedInitialPrice, $initialPeriodInDays, $formattedRecurringPrice, $recurringPeriodInDays, $numRebills, $currencyCode, $salt) {

        $stringToHash = '';

        if ($formattedRecurringPrice && $formattedRecurringPrice != '0.00')
        {
            $stringToHash = '' . $formattedInitialPrice
                               . $initialPeriodInDays
                               . $formattedRecurringPrice
                               . $recurringPeriodInDays
                               . $numRebills
                               . $currencyCode
                               . $salt;
        }
        else {
            $stringToHash = '' . $formattedInitialPrice
                               . $initialPeriodInDays
                               . $currencyCode
                               . $salt;
        }
        
        return md5($stringToHash);

    }
    
    function get_period_in_days($number, $unit) {
    
      if (!$number || !$unit)
          return 0;
      if ($unit == 'day')
          return $number;
      if ($unit == 'week')
          return $number * 7;
      if ($unit == 'month')
          return $number * 30;
      if ($unit == 'year')
          return $number * 365;
    }

    /**
     * Process the payment and return the result
     *
     * @access public
     * @param int $order_id
     * @return array
     */
    function process_payment( $order_id ) {

        if ($this->advanced_integration)
            return $this->process_payment_advanced_integration( $order_id );
        else
            return $this->process_payment_forms_integration( $order_id );

    }    
    
    function process_payment_forms_integration( $order_id ) {
      
        global $woocommerce;            
        global $wp;
        
        $this->logMessage('process_payment_forms_integration 1');
        
        $orderPay = false;
        
        if ( isset($wp->query_vars['order-pay']) && absint($wp->query_vars['order-pay']) > 0 ) {
          $orderPay = 1;
          $order_id = absint($wp->query_vars['order-pay']); // The order ID
        }
        
        
        $this->logMessage('process_payment_forms_integration 1.1');
  
        //$order = new WC_Order( $order_id );
        $order = wc_get_order( $order_id );
        $orderItems = $order->get_items();
        
        $orderTotal = $order->get_total();
        $initialPeriodInDays = 2;// 4; // Use the default of 2 for non-recurring purchases
        $salt = $this->salt;
        $currencyCode = $this->get_ccbill_currency_code($order->get_currency());
        $recurringItemCount = $this->get_cart_recurring_item_count();
        $recurringTotal = 0;
        $recurringTotalFormatted = "";
        $recurringPeriodInDays = 0;
        $numRebills = 99;
        $wRecurringTotal = null;
        
        $this->logMessage('process_payment_forms_integration 2');
        
        // Forms checkout only supports one subscription at a time
        if ( $recurringItemCount > 0 ) {
          
          $this->logMessage('process_payment_forms_integration 3: cart contains recurring items');
          
            $subscriptions = [];
          
            if (function_exists('wcs_get_subscriptions_for_order')) {
              $subscriptions = wcs_get_subscriptions_for_order( $order_id );
            }
            
            if ( empty( $subscriptions ) )
            {
                $this->logMessage('Error: Subscriptions were indicated but none were found in order ' . $order->id);
                return;
            }
            
            $subscription = array_values( $subscriptions )[0];
          
            $trialEndDate = $subscription->get_date( 'next_payment' );
            
            $this->logMessage('process_payment_forms_integration 4');
          
            //$recurringTotal = $subscription->get_total();
            // $orderTotal = WC_Subscriptions_Order::get_total_initial_payment( $order );
            $orderTotal = $order->get_total();
            $recurringTotal = WC_Subscriptions_Order::get_recurring_total( $order );
              
            $trialStartTime = $subscription->get_time( 'start' );
            $trialEndTime = $subscription->get_time( 'trial_end' );
            
            $trialLength = wcs_estimate_periods_between( $trialStartTime, $trialEndTime, $subscription->get_trial_period() );
            
            
            $this->logMessage('process_payment_forms_integration 5');
            
            // $initialUnit = WC_Subscriptions_Order::get_subscription_trial_period( $order );
            $initialUnit = $subscription->get_trial_period();
            // $initialNumber = WC_Subscriptions_Order::get_subscription_trial_length( $order );
            $initialNumber = $trialLength;
            // $recurringUnit = WC_Subscriptions_Order::get_subscription_period( $order );
            $recurringUnit = $subscription->get_billing_period();
            // $recurringNumber = WC_Subscriptions_Order::get_subscription_interval( $order );
            $recurringNumber = $subscription->get_billing_interval();
            
            $initialPeriodInDays = $this->get_order_subscription_initial_period_days($order_id); 
            $recurringPeriodInDays = $this->get_period_in_days($recurringNumber, $recurringUnit);
            
            // If the initial period is zero (ex: no free trial), set the initial period to the recurring period
            if ($initialPeriodInDays == 0) {
                $initialPeriodInDays = $recurringPeriodInDays;
            }
            
            $numRebills = 99;
            
                 
            // $trialPeriodInDays = $this->get_days_from_present($trialEndDate);
            
            $wRecurringTotal = '' . number_format($recurringTotal, 2, '.', '');
            
            $subscriptionValues = '$trialEndDate = ' . $trialEndDate . '; '
                                . '$orderTotal = ' . $orderTotal . '; '
                                . '$recurringTotal = ' . $recurringTotal . '; '
                                . '$trialStartTime = ' . $trialStartTime . '; '
                                . '$trialEndTime = ' . $trialEndTime . '; '
                                . '$trialLength = ' . $trialLength . '; '
                                . '$initialUnit = ' . $initialUnit . '; '
                                . '$initialNumber = ' . $initialNumber . '; '
                                . '$recurringUnit = ' . $recurringUnit . '; '
                                . '$recurringNumber = ' . $recurringNumber . '; '
                                . '$initialPeriodInDays = ' . $initialPeriodInDays . '; '
                                . '$recurringPeriodInDays = ' . $recurringPeriodInDays . '; ';
            
            $this->logMessage('process_payment_forms_integration Subscription Values: ' . $subscriptionValues);
            
        }
        
        $this->logMessage('process_payment_forms_integration : after recurring items');
        
        // Form price must meet minimum
        if ( !($orderTotal > 0) ) { 
            wc_add_notice( 'Minimum initial price not met.  Initial Price: ' . $orderTotal, 'error' );
            return null;
        }
          
        $wTotal = '' . number_format($orderTotal, 2, '.', '');
        
        $fullTotal = $orderTotal + $recurringTotal;
        $wFullTotal = number_format($fullTotal, 2, '.', '');
  
        // Create the hash
        $myHash = $this->get_digest($wTotal, $initialPeriodInDays, $wRecurringTotal, $recurringPeriodInDays, $numRebills, $currencyCode, $salt);
       
        $ccbill_addr = $this->liveurl . '?';
        
        $fd_email = isset($_REQUEST['billing_email']) ? sanitize_email($_REQUEST['billing_email']) : '';
        
        // If this is classic checkout (vs blocks), get the field values using the classic method
        if ($fd_email != '')
        {
          $fd_customer_fname = isset($_REQUEST['billing_first_name']) ? sanitize_text_field($_REQUEST['billing_first_name']) : '';
          $fd_customer_lname = isset($_REQUEST['billing_last_name']) ? sanitize_text_field($_REQUEST['billing_last_name']) : '';
          $fd_zipcode = isset($_REQUEST['billing_postcode']) ? sanitize_text_field($_REQUEST['billing_postcode']) : '';
          $fd_country = isset($_REQUEST['billing_country']) ? sanitize_text_field($_REQUEST['billing_country']) : '';
          $fd_city = isset($_REQUEST['billing_city']) ? sanitize_text_field($_REQUEST['billing_city']) : '';
          $fd_state = isset($_REQUEST['billing_state']) ? sanitize_text_field($_REQUEST['billing_state']) : '';
          $fd_address1 = isset($_REQUEST['billing_address_1']) ? sanitize_text_field($_REQUEST['billing_address_1']) : '';
        }
        
        // Otherwise, get field values using the blocks method
        if ($fd_email == '')
        {
          $fd_email = $order->get_billing_email();
          
          $fd_customer_fname = $order->get_billing_first_name();
          $fd_customer_lname = $order->get_billing_last_name();
          $fd_address1       = $order->get_billing_address_1();
          $fd_state          = $order->get_billing_state();
          $fd_city           = $order->get_billing_city();
          $fd_zipcode        = $order->get_billing_postcode();
          $fd_country        = $order->get_billing_country();
          
        }
        
        // Default country to US if not set
        if ($fd_country == '')
          $fd_country = 'US';
          
        $subAccountToCharge = $recurringTotal > 0 ? $this->sub_account_no_recurring : $this->sub_account_no;
  
        $ccbill_args  = 'clientSubacc='   .   $subAccountToCharge
                      . '&' . $this->priceVarName . '='      . $wTotal
                      . '&' . $this->periodVarName . '='     . $initialPeriodInDays;
                      
        if ( ! $this->is_flexform) {
            $ccbill_args .= '&formName=' . $this->form_name
                          . '&clientAccnum=' . $this->account_no;
        }
                      
        if ($recurringTotal)
        {
            $ccbill_args .= '&recurringPrice=' . $wRecurringTotal
                          . '&recurringPeriod=' . $recurringPeriodInDays
                          . '&numRebills=' . $numRebills;
                          /*
                          . '&initialUnit=' . $initialUnit
                          . '&initialNumber=' . $initialNumber
                          . '&recurringUnit=' . $recurringUnit
                          . '&recurringNumber=' . $recurringNumber
                          . '&trialExpirationDate=' . $trialExpirationDate;
                          */
        }
  
        $ccbill_args .= '&currencyCode='   . $currencyCode
                      . '&customer_fname=' . $fd_customer_fname
                      . '&customer_lname=' . $fd_customer_lname
                      . '&email='          . $fd_email
                      . '&zipcode='        . $fd_zipcode
                      . '&country='        . $fd_country
                      . '&city='           . $fd_city
                      . '&state='          . $fd_state
                      . '&address1='       . $fd_address1
                      . '&wc_orderid='     . $order_id
                      //. '&referingDestURL='. $this->base_url . '/' . 'finish'
                      . '&orderPay=' . $orderPay
                      . '&formDigest='     . $myHash;
  
        return array(
          'result' 	     => 'success',
          'redirect'     => $this->liveurl . '?' . $ccbill_args
        );
        
    }// end process_payment_forms_integration
    
    
    function process_payment_advanced_integration( $order_id ) {
      
        global $woocommerce;
        
        global $wp;
        
        $orderPay = false;
        
        if ( isset($wp->query_vars['order-pay']) && absint($wp->query_vars['order-pay']) > 0 ) {
          $orderPay = 1;
          $order_id = absint($wp->query_vars['order-pay']); // The order ID
        }
        
        $this->logMessage( 'process_payment_advanced_integration POST data: ' . json_encode( $_POST ) );
        
        //$order = new WC_Order( $order_id );
        $order = wc_get_order( $order_id );
        
        $paymentToken = $this->getPostValue('ccbill_token') ?? '';
        
        $this->logMessage( 'process_payment_advanced_integration Payment Token: ' . $paymentToken );
                
        if ( empty( $paymentToken ) ) {
            wc_add_notice( __( 'Payment error: no payment token provided.', 'woocommerce-payment-gateway-ccbill' ), 'error' );
            return;
        }
        
        $orderTotal = $order->get_total();
        
        if ( !($orderTotal > 0) )
          return null;
          
        $wooCurrency = $order->get_currency();
        $ccbillCurrencyCode = $this->get_ccbill_currency_code( $wooCurrency );
        
        $periodInDays = 2;
        $isRecurringCharge = false;
        $subscription = null;
        
        if (function_exists('wcs_get_subscription')) {
          $subscription = wcs_get_subscription( $order_id );
        }
        
        // Save the payment token to each created subscription
        $subscriptions = [];
        
        if (function_exists('wcs_get_subscriptions_for_order')) { 
          $subscriptions = wcs_get_subscriptions_for_order( $order, array( 'order_type' => 'parent' ) );
        }
        
        
                
        $this->logMessage( 'process_payment_advanced_integration | subscriptions for order: ' . json_encode($subscriptions) );
        
        // Save the payment token to each subscription
        foreach ( $subscriptions as $tSubscription ) {
          $this->logMessage( 'process_payment_advanced_integration | adding meta data to subscription ' . $tSubscription->id . ': ' . json_encode($tSubscription) );
            //update_post_meta($tSubscription->id, '_payment_token', $paymentToken);
            $tSubscription->update_meta_data( '_ccbill_payment_token', $paymentToken );
            $tSubscription->save();
        }
        
        if ( $subscription ) {
            $nextPaymentDate = $subscription->get_date( 'next_payment' );
            $periodInDays = $this->get_days_from_present( $nextPaymentDate );
        }
        
        $charge = $this->api_charge( $orderTotal, $periodInDays, $isRecurringCharge, $ccbillCurrencyCode, $paymentToken, $order, $subscription );
        
        
        $this->logMessage( 'api_charge complete.  charge: ' . json_encode($charge) );
        
        if ( is_wp_error( $charge ) || !$charge['subscriptionId']) {
          
            if ( is_wp_error( $charge ) ) {
                $this->logMessage( 'payment declined.  charge is_wp_error' );
            }
            else {
                if ($charge['declineText'])
                  wc_add_notice( $charge['declineText'], 'error' );
                else if ($charge['generalMessage'])
                  wc_add_notice( $charge['generalMessage'], 'error' );
                $this->logMessage( 'payment declined.  is_wp_error: ' . is_wp_error( $charge ) . '; subscriptionId: ' . $charge['subscriptionId'] );
            }          
            
            return;
        }
        
        // On success, complete order and save token on subscription
        $order->payment_complete( $charge['subscriptionId'] );
        
        if ($this->markVirtualOrdersCompleteWhenPaid && $this->order_contains_only_virtual_products( $order_id ))
          $order->update_status( 'completed' );
        
        //$order->add_order_note( 'CCBill Transaction ID' . $charge['transactionId'], true );
        WC()->cart->empty_cart();
        
        return [
            'result'   => 'success',
            'redirect' => $this->get_return_url( $order ),
        ];
        
    }// end process_payment_advanced_integration
    
    /**
     * Handle a scheduled subscription renewal payment.
     *
     * @param float               $amount amount in order currency
     * @param WC_Order_Subscription $renewal_order   the renewal order object
     */
     // TODO: Test
     public function scheduled_subscription_payment( $amount, $renewal_order ) {
       
       if ( is_numeric( $renewal_order ) ) {
          $this->logMessage( 'scheduled_subscription_payment.  amount: ' . $amount . '; renewal order was specified as numeric: ' . $renewal_order );
           $renewal_order = wc_get_order( $renewal_order );
       }
       if ( ! $renewal_order instanceof WC_Order ) {
           $this->logMessage( 'scheduled_subscription_payment.  amount: ' . $amount . '; renewal order: is not an instance of WC_Order.  Exiting with error.' );
           return;
       }
       
         $this->logMessage( 'scheduled_subscription_payment.  rewnewal_order is apparently an instance of WC_Order even though it won\'t serialize correctly here.  amount: ' . $amount . '; renewal order ID: ' . $renewal_order->get_id() . '; renewal order: ' . json_encode($renewal_order) );
         
         $subscriptions = wcs_get_subscriptions_for_renewal_order( $renewal_order );
         
         $this->logMessage( 'scheduled_subscription_payment.  subscriptions for renewal order: ' . json_encode($subscriptions) );
         
         $subscription = $subscriptions ? array_shift( $subscriptions ) : null;
         
         $renewalOrderId = $renewal_order->get_id();
         $customerId = $renewal_order->get_customer_id(); // may be 0 for guest
         $renewalOrderTotal = $renewal_order->get_total();
         $subscriptionId = $subscription->get_id();
         $this->logMessage( 'scheduled_subscription_payment.  rewnewal_order ID: ' . $renewalOrderId . '; customer_id: ' . $customerId . '; renewalOrderTotal: ' . $renewalOrderTotal . '; subscriptionId: ' . $subscriptionId );
         
         // Check to see if there is a charge in progress for this order
         $ccbillCharging = $renewal_order->get_meta( '_ccbill_charging' );
         $ccbillCharged = $renewal_order->get_meta( '_ccbill_charged' );
         $ccbillTransactionId = $renewal_order->get_meta( '_ccbill_transaction_id' );
         
          // If a charge is already in progress, stop here
          if ($ccbillCharging != null && $ccbillCharging > 0) {
            $this->logMessage("A duplicate CCBill charge of " . $renewalOrderTotal . " for order " . $renewalOrderId . " is  in progress.  Exiting.");     
            return;
          }  
          
           // If a charge is already in progress, stop here
           if ($ccbillCharged != null && $ccbillCharged > 0) {
             $this->logMessage("Order " . $renewalOrderId . " has already been charged 1.  Exiting.");     
             return;
           }    
         
          // If a charge is already in complete, stop here
          if ($ccbillTransactionId != null) {
            $this->logMessage("Order " . $renewalOrderId . " has already been charged 2.  Exiting.");     
            return;
          }       
         
         try {
           
            $this->logMessage("Updating renewal order " . $renewalOrderId . " meta to indicate a charge is in progress.");   
              
            // Set the charging flag to indicate we are attempting to charge this subscription
            $renewal_order->update_meta_data( '_ccbill_charging', 1 );
            $renewal_order->update_meta_data( '_ccbill_syncing', 1 );
            $renewal_order->save();
           
            $this->logMessage( 'scheduled_subscription_payment.  1 subscription: ' . json_encode($subscription)  );
             
            $tokenId = $subscription->get_meta( '_ccbill_payment_token' );
               
            $this->logMessage( 'scheduled_subscription_payment.  tokenId from subscription: ' . $tokenId  );
            
            $paymentToken = $tokenId;
            // Get the CCBill currency code
            $wooCurrency = $renewal_order->get_currency();
            $ccbillCurrencyCode = $this->get_ccbill_currency_code( $wooCurrency );
            
            $nextPaymentDate = $subscription->get_date( 'next_payment' );
            
            $periodInDays = $this->get_days_from_present( $nextPaymentDate );
            
            $this->logMessage( 'charging subscription payment' );
            
            // A subscription payment is a recurring charge
            $isRecurringCharge = true;
            
            $charge = $this->api_charge( $amount, $periodInDays, $isRecurringCharge, $ccbillCurrencyCode, $paymentToken, $renewal_order, $subscription );
            
            $this->logMessage( 'charge: ' . json_encode($charge) );
            
            if ( is_wp_error( $charge ) || !$charge['subscriptionId']) {
                $renewal_order->update_status( 'failed',
                    sprintf( __( 'CCBill renewal failed: %s', 'woocommerce-payment-gateway-ccbill' ), $charge->get_error_message() ) );
                $this->logMessage( 'charge error: ' . $charge->get_error_message() );
                return;
            }
           
            $renewal_order->payment_complete( $charge['subscriptionId'] );
            $renewal_order->add_order_note( __( 'Automatic subscription renewal payment via CCBill.', 'woocommerce-payment-gateway-ccbill' ) );
            $renewal_order->update_meta_data( '_ccbill_charged', 1 );
            $renewal_order->save();
            
            if ($this->markVirtualOrdersCompleteWhenPaid && $this->order_contains_only_virtual_products( $renewalOrderId ))
             $renewal_order->update_status( 'completed' );             
           
         } catch (Exception $ex) {
           
         } finally {
           // Delete the meta tags when we're done
           $renewal_order->delete_meta_data( '_ccbill_charging', 1 );
           $renewal_order->delete_meta_data( '_ccbill_syncing', 1 );
           $renewal_order->save();
           
           $this->logMessage("Meta charging information removed from rewnewal order " . $renewalOrderId . ".");  
         }
         
     }
     
     function logMessage ($message) {
       if ( 'yes' == $this->debug && isset($this->log) )
          error_log($message);
     }
     
    /**
     * Call CCBill RESTful Transaction API to charge a token.
     *
     * @return array|WP_Error on success, WP_Error on failure
     */
    public function api_charge( $amount, $periodInDays, $isRecurringCharge, $ccbillCurrencyCode, $paymentToken, $order, $subscription ) {
      
        // Get OAuth access token
        $authToken = $this->get_oauth_token_backend();
        
        // Return an error if the auth token was not generated correctly
        if ( is_wp_error( $authToken ) ) {
            echo 'An error occurred while generating an auth token: ' . $authToken;
            return [
                'error' => 1,
                'errorMessage' => 'An error occurred while generating an auth token: ' . $authToken
            ];
        }
        
        // Use the recurring account for rebills and the non-recurring account for initial charges
        $clientSubacc = $isRecurringCharge ? $this->sub_account_charge_recurring : $this->sub_account_charge_nonrecurring;
          
        // Format the amount with two decimal places ex: 10.00
        $formattedAmount = '' . number_format($amount, 2, '.', '');
        
        $this->logMessage( 'ccbill', 'Debug Point: 1' );
        
        $orderId = $order ? $order->get_id() : null;
        $subscriptionId = $subscription ? $subscription->get_id() : null;
                  
        // Compose the URL
        $url = $this->transaction_url . "/payment-tokens/" . $paymentToken;
        
        // Compose the request
        $this->logMessage( 'Debug Point: 2 composing request to url: ' . $url );
        
        $passThroughValues = array();
        $passThroughValues[] = [ "name" => "wcOrderId", "value" => $orderId ];
        $passThroughValues[] = [ "name" => "wcSubscriptionId", "value" => $subscriptionId ];
        
        $headers = [
            'Cache-Control' => 'no-cache',
            'Authorization' => 'Bearer ' . $authToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/vnd.mcn.transaction-service.api.v.2+json',
        ];
        
        $postData = [
            'clientAccnum'    => $this->account_no_advanced,
            'clientSubacc'    => $clientSubacc,
            'initialPrice'    => $formattedAmount,
            'initialPeriod'   => $periodInDays,
            'currencyCode'    => $ccbillCurrencyCode,
            'createNewPaymentToken' => false,
        ];
        
        $postData = [
            'timeout' => 30,
            'headers' => [
                'Cache-Control' => 'no-cache',
                'Authorization' => 'Bearer ' . $authToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/vnd.mcn.transaction-service.api.v.2+json',
            ],
            'body' => json_encode([
                'clientAccnum'    => $this->account_no_advanced,
                'clientSubacc'    => $clientSubacc,
                'initialPrice'    => $formattedAmount,
                'initialPeriod'   => $periodInDays,
                /*
                'recurringPrice'  => $formattedRecurringPrice,
                'recurringPeriod' => $recurringPeriodInDays,
                'rebills'         => 99,
                'lifeTimeSubscription'  => false,
                */
                'currencyCode'    => $ccbillCurrencyCode,
                'createNewPaymentToken' => false,
            ]),
        ];
        
        $this->logMessage( 'CCBill Post Data: ' . json_encode($postData) );

        $response = wp_remote_post( $url, $postData );
        
        $this->logMessage( 'CCBill Response Data: ' . json_encode($response) );
        
        if ( is_wp_error( $response ) ) {
            $this->logMessage( 'Error occurred while retrieving payment token post response: ' . json_encode($response) );
            return $response;
        }
        
        // Decode the successful response
        $responseData = json_decode( wp_remote_retrieve_body( $response ), true );
        
        $this->logMessage( 'Debug Point: 3 response data retrieved: ' . json_encode( $responseData ) );
        
        if ( empty( $responseData['paymentUniqueId'] ) ) {
          
            $errorCode = '';
            $errorDesc = '';
            
            if ( array_key_exists('errorCode', $responseData) && !empty($repsonseData['errorCode']) )
              $errorCode = $repsonseData['errorCode'];
          
            if ( array_key_exists('declineText', $responseData) && !empty($repsonseData['declineText']) )
              $errorDesc = $repsonseData['declineText'];
              
            if ( empty($errorDesc) && array_key_exists('generalMessage', $responseData) && !empty($repsonseData['generalMessage']) )
              $errorDesc = $repsonseData['generalMessage'];
              
            return new WP_Error( 'ccbill_error',
                sprintf( __( 'CCBill error: %s', 'woocommerce-payment-gateway-ccbill' ), $errorCode . ' – ' . $errorDesc ) );
        }
        
        // on first sale, store token for future renewals
        if ( ! did_action( 'woocommerce_scheduled_subscription_payment_' . $orderId ) ) {
            // subscription object isn’t created until after process_payment, so hook later
            add_action( 'woocommerce_subscription_status_active', function( $subscription ) use ( $paymentToken ) {
                $subscription->update_meta_data( '_ccbill_payment_token', $paymentToken );
                $subscription->save();
            } );
        }
        
        // Check to see if subscription id is present,
        // indicating a successful transaction.
        // Classic returns subscription_id,
        // FlexForms return subscriptionId
        $ccbillTransactionId = isset($responseData['transactionId']) ? sanitize_text_field($responseData['transactionId']) : '';
        $ccbillSubscriptionId = isset($responseData['subscriptionId']) ? sanitize_text_field($responseData['subscriptionId']) : '';
        $success = !( empty ( $ccbillTransactionId ) || empty ( $ccbillSubscriptionId ) );
        
        if ( $success ){
          if (!empty($ccbillTransactionId))
              $order->update_meta_data( '_ccbill_transaction_id', $ccbillTransactionId );
          if (!empty($ccbillSubscriptionId))
              $order->update_meta_data( '_ccbill_subscription_id', $ccbillSubscriptionId );    
          $order->save();
        }
        
        $this->logMessage( 'api_charge was successful, returning transaction ID: ' . $ccbillTransactionId . '; returning subscription ID: ' . $ccbillSubscriptionId );
        
        return [
            'subscriptionId' => $ccbillSubscriptionId,
            'transactionId' => $ccbillTransactionId,
            'declineCode' => sanitize_text_field($responseData['declineCode']),
            'declineText' => sanitize_text_field($responseData['declineText']),
            'denialId' => sanitize_text_field($responseData['denialId']),
            'approved' => sanitize_text_field($responseData['approved']),
            'paymentUniqueId' => sanitize_text_field($responseData['paymentUniqueId']),
            'sessionId' => sanitize_text_field($responseData['sessionId']),
            'newPaymentTokenId' => sanitize_text_field($responseData['newPaymentTokenId']),
        ];
    }
    
    function get_oauth_token_frontend() {
      return $this->get_oauth_token($this->frontend_username, $this->frontend_password);
    }
    
    function get_oauth_token_backend() {
      return $this->get_oauth_token($this->backend_username, $this->backend_password);
    }
    
    function get_oauth_token($username, $password) {
      
      $url = $this->oauth_token_url;
      $encoded_credentials = base64_encode( $username . ':' . $password );
      
      $response = wp_remote_post( $url, [
          'timeout' => 30,
          'headers' => [
              'Authorization' => 'Basic ' . $encoded_credentials,
              'Content-Type'  => 'application/x-www-form-urlencoded',
          ],
          'body'    => [ 'grant_type' => 'client_credentials' ],
      ] );
      
      if ( is_wp_error( $response ) ) { // die('9 error');
          return $response;
      }
      $json = json_decode( wp_remote_retrieve_body( $response ), true );
      if ( empty( $json['access_token'] ) ) {
          return new WP_Error( 'oauth_error', __( 'Failed to obtain CCBill access token.', 'woocommerce-payment-gateway-ccbill' ) );
      }
      
      return $json['access_token'];
      
    }
    
    function update_failing_payment_method( $original_order, $new_renewal_order ) {
      
        $this->logMessage( 'update_failing_payment_method Original Order: ' . json_encode($original_order) . '; new renewal order: ' . $new_renewal_order );
    }

    /**
     * Check for CCBill IPN Response
     *
     * @access public
     * @return void
     */
    function check_ccbill_response() {
      
      $this->logMessage( 'ccbill | check_ccbill_response | IPN Response' );

      @ob_clean();
      
      $r = null;
      
      $responseAction = '';
      
      // eventType should be sent in the request
      if ( isset($_REQUEST['EventType']) )
        $responseAction = sanitize_text_field($_REQUEST['EventType']);
      else if ( isset($_REQUEST['eventType']) )
        $responseAction = sanitize_text_field($_REQUEST['eventType']);
      else if ( isset($_REQUEST['Action']) )
        $responseAction = sanitize_text_field($_REQUEST['Action']);

      if ( strpos($responseAction, 'Approval_Post') !== false )
        $responseAction = 'approval_post';
      else if ( strpos($responseAction, 'Rebill_Post') !== false )
        $responseAction = 'rebill_post';
      else if ( strpos($responseAction, 'Cancel_Post') !== false )
        $responseAction = 'cancel_post';

      // Webhooks screws up any query string arguments added to the first url
      // If response time has not been set by parameter value, assume it's an approval post
      if ( strlen($responseAction) < 1 &&
         ( ( isset($_POST['subscription_id'] ) && strlen( $_POST['subscription_id']) > 0 ) ||
          ( isset( $_POST['subscriptionId'] ) && strlen( $_POST['subscriptionId'] ) > 0))) {
            
            $responseAction = 'approval_post';
      }
        

      global $woocommerce;         
      global $wp;
      
      // Note the integration method if logging, even though we don't care
      $this->logMessage( 'ccbill | IPN Response | integration method: ' . $this->integration_method );
      $this->logMessage( 'ccbill | IPN Response | $responseAction = ' . $responseAction );
      
      $prefix = '';// isset($_POST['X-wc_orderid']) ? 'X-' : '';
      
      $order_id = -1;
      $initialPrice = -1;
      
      $isOrderPay = false;
      
      if( isset($_REQUEST['orderPay']) && sanitize_text_field($_REQUEST['orderPay']) == '1' ) {
        $isOrderPay = true;
      }
      
      // Invoice/order number returned as wc_orderid
      if( isset($_REQUEST[$prefix . 'wc_orderid']) )
      {
        $order_id = sanitize_text_field($_REQUEST[$prefix . 'wc_orderid']);
        $initialPrice = sanitize_text_field($_REQUEST[$prefix . 'initialPrice']);
      }
      
      $order = null;
      
      if ($order_id > 0)
        $order = wc_get_order( $order_id );
        
      $clearCart = false;
      
      
      // If this is not a payment for an order 
      // and the order total matches the cart total 
      // as well as the item counts, clear the cart
      if ($isOrderPay != '1' && ! is_null($order) && $initialPrice == $order->get_total() && ! is_null(WC()->cart) ){
        $cart = WC()->cart;
        
        if ( ! is_null($cart) ){
                    
          $cartItemCount = WC()->cart->get_cart_contents_count();
          $orderItemCount = $order->get_item_count();
          
          // If the cart and order total match, and the item count matches, clear the cart
          if ( $cartItemCount == $orderItemCount )
          {
            $clearCart = true;
          }
        }  
      }
      
      $orderReturnUrl = $this->get_return_url( $order );
      $this->logMessage( 'ccbill | IPN Response | $responseAction 2 = ' . $responseAction . '; logging input values: ');
      
      // Write all post values to the log for debugging
      $this->log_input_values();
        
      switch($responseAction){
        case 'CheckoutSuccess': //print('Checkout Success');
                                // clear the cart if the total matches the order total
                                if ( $clearCart ) { WC()->cart->empty_cart(); }
                                wp_die('<script>document.location = "' . $orderReturnUrl . '";</script><p>Thank you for your order.  Your payment has been approved.</p><p>You will now be redirected to the order page.  If you are not redirected automatically, please <a href="' . $orderReturnUrl . '">click here</a></p>', 'Checkout Success', array( 'response' => 200 ) );
          break;
        case 'CheckoutFailure': //wp_die('Checkout Failure');
                                wp_die('<p>Unfortunately, your payment was declined.</p><p><a href="' . esc_url($cart_url = $woocommerce->cart->get_cart_url()) . '">Return to Cart</a></p>', 'Checkout Failure', array( 'response' => 200 ) );
          break;
        case 'Approval_Post':
        case 'approval_post':   //print('Approval Post');
        case 'NewSaleSuccess':  $this->process_ccbill_approval_post();
          break;
        case 'Denial_Post':
        case 'denial_post':
        case 'NewSaleFailure': wp_die('Failure', 'Failure', array( 'response' => 200 ) );
          break;
        case 'Rebill_Post': 
        case 'rebill_post': 
        case 'RenewalSuccess':  $this->process_ccbill_rebill_post();
          break;
        case 'Cancel_Post': 
        case 'cancel_post': 
        case 'Cancellation':
        case 'Expiration':  $this->process_ccbill_cancellation_post();
          break;
        default: wp_die( "CCBill IPN Request Failure.  ResponseAction: " . $responseAction, "CCBill IPN", array( 'response' => 200 ) );
          break;
      }// end switch

      wp_die('Failure', 'Failure', array( 'response' => 200 ) );

    }
    
    function get_post_values_table() {
      
      $r = '<table>';
      
      foreach ($_POST as $key => $value) {
        $r .= '<tr>';
        $r .= '<td>';
        $r .= sanitize_text_field($key);
        $r .= '</td>';
        $r .= '<td>';
        $r .= sanitize_text_field($value);
        $r .= '</td>';
        $r .= '</tr>';
      
      }// end foreach
      
      $r .= '</table>';
      
      return $r;      
    }
    
    function log_input_values() {
      
      $this->logMessage( 'GET Values:' );
      
      $r = '';
      
      foreach ( $_GET as $key => $value ) {
        $r .= sanitize_text_field($key)
           . ' = '
           . sanitize_text_field($value)
           . ';  ';
      }
      
      $this->logMessage( $r );
      
      $this->logMessage( 'POST Values:' );
      
      $r = '';
      
      foreach ( $_POST as $key => $value ) {
        $r .= sanitize_text_field($key)
           . ' = '
           . sanitize_text_field($value)
           . ';  ';
      }
      
      $this->logMessage( $r );
    }

    // Verify CCBill variables
    function process_ccbill_approval_post() {

      $this->logMessage( 'ccbill | process_ccbill_approval_post hit' );

      $orderNumber = -1;
      $tCurrencyCode = -1;
      
      $recurringTotal = 0;
      $initialPeriodInDays = 0;
      $recurringPeriodInDays = 0;
      $wRecurringTotal = 0;
      $numRebills = 99;
      $priceDescription = '';

      $prefix = isset($_POST['X-wc_orderid']) ? 'X-' : '';

      // $this->logMessage( 'ccbill check response prefix | ' . $prefix);

      // Invoice/order number returned as wc_orderid
      if(isset($_POST[$prefix . 'wc_orderid']))
        $orderNumber = $this->getPostValue($prefix . 'wc_orderid');
      else
        wp_die('Order not found', 'Order Not Found', array( 'response' => 200 ) );


      // $this->logMessage( 'ccbill check response order no | ' . $orderNumber);

      // Check to see if subscription id is present,
      // indicating a successful transaction.
      // Classic returns subscription_id,
      // FlexForms return subscriptionId
      $txId = '';
      $success = false;
      
      if ( isset($_POST['subscription_id']) )
        $this->logMessage( 'ccbill | process_ccbill_approval_post subscription_id is set: ' . $this->getPostValue('subscription_id') );
      if ( isset($_POST['transactionId']) )
        $this->logMessage( 'ccbill | process_ccbill_approval_post transactionId is set: ' . $this->getPostValue('transactionId') );
      if ( isset($_POST['subscriptionId']) )
        $this->logMessage( 'ccbill | process_ccbill_approval_post subscriptionId is set: ' . $this->getPostValue('subscriptionId') );

      if(strlen($prefix) == 0 && isset($_POST['subscription_id'])){
        $txId = $this->getPostValue('subscription_id');
        $success = true;
      }
      else if(strlen($prefix) > 0 && isset($_POST['transactionId'])){
        $txId = $this->getPostValue('transactionId');
        $success = true;
      }
      else if(strlen($prefix) > 0 && isset($_POST['subscriptionId'])){
        $txId = $this->getPostValue('subscriptionId');
        $success = true;
      }
      
      $this->logMessage( 'ccbill | process_ccbill_approval_post $txId = ' . $txId . '; $success = ' . $success );

      $order = null;
      
      $branchNo = -1;

      // Attempt to retrieve the order and verify the hash
      if($success == true)
      {
        $order = new WC_Order( $orderNumber );

        $tCartTotal = -1;
        $tPeriod = -1;
        $formDigest = $this->getPostValue($prefix . 'formDigest');

        if(isset($_POST[$prefix . 'formPrice'])) {
          $tCartTotal = $this->getPostValue($prefix . 'formPrice');
          $tPeriod    = $this->getPostValue($prefix . 'formPeriod');
          $branchNo = 1;
        }
        else if(isset($_POST['billedInitialPrice'])) {
          $tCartTotal = $this->getPostValue('billedInitialPrice');
          $tPeriod    = $this->getPostValue('initialPeriod');
          $tCartTotal            = $this->getPostValue('subscriptionInitialPrice');
          $tPeriod               = $this->getPostValue('initialPeriod');
          $recurringTotal        = $this->getPostValue('subscriptionRecurringPrice');
          $recurringPeriodInDays = $this->getPostValue('recurringPeriod');
          $numRebills            = $this->getPostValue('rebills');
          
          if (isset($_POST['recurringPriceDescription']))
            $priceDescription      = $this->getPostValue('recurringPriceDescription');
          else if ( isset($_POST['priceDescription']) )
            $priceDescription      = $this->getPostValue('priceDescription');
            
          $tCurrencyCode         = $this->getPostValue('subscriptionCurrencyCode');
          $branchNo = 2;
        }
        else if(isset($_POST['initialPrice'])) {
          $tCartTotal            = $this->getPostValue('initialPrice');
          $tPeriod               = $this->getPostValue('initialPeriod');
          $branchNo = 3;
        }
        // $this->logMessage( 'Branch No = ' . $branchNo );
        
        $this->logMessage( 'ccbill | process_ccbill_approval_post 2' );

        if ($tCurrencyCode < 0)
          $tCurrencyCode = $this->getPostValue('billedCurrencyCode') ?? '';

        $wCartTotal = '' . number_format($tCartTotal, 2, '.', '');
        
        if ($recurringTotal)
          $wRecurringTotal = '' . number_format($recurringTotal, 2, '.', '');
          
        $this->logMessage( 'ccbill | process_ccbill_approval_post computing hash: $wCartTotal = ' .  $wCartTotal . '; $tPeriod = ' . $tPeriod . '; $wRecurringTotal = ' . $recurringPeriodInDays . '; $numRebills = ' . $numRebills . '; $tCurrencyCode = ' . $tCurrencyCode . '; salt = ' . $this->salt);

        $myHash = $this->get_digest($wCartTotal, $tPeriod, $wRecurringTotal, $recurringPeriodInDays, $numRebills, $tCurrencyCode, $this->salt);
        
        $this->logMessage( '$myHash: ' . $myHash );
        $this->logMessage( '$formDigest: ' . $formDigest );

        // Compare form digest if we have one.
        // Otherwise, compare ingredients
        if(strlen($formDigest) > 0) {

          if ($formDigest != $myHash)
            $success = false;

        }
        else {

          if($wCartTotal != $tCartTotal) {

             $success = false;

          }// end if

        }// end if/else
        
      }// end if order number was found in arguments

      if( $success == true )
      {        
        $this->logMessage( 'ccbill | process_ccbill_approval_post | success is true' );
        
        $orderNoteText = 'CCBill payment completed.';
        $customerNoteText = null;
        
        if ($recurringTotal && $recurringTotal != '0.00') {
          $orderNoteText .= '  A subscription was created: ' . $priceDescription
                          . ' The subscription ID is the transaction ID.';          
        }
        
        $customerNoteText = 'CCBill Subscription ID: ' . $txId;
          
        $orderNoteText .= '  CCBill Transaction ID: ' . $txId;
        
        $order = new WC_Order( $orderNumber );
        
        if ( $this->debug == true )
          $order->add_order_note( __( $orderNoteText, 'woocommerce-payment-gateway-ccbill' ) );    
        
        if ($customerNoteText)
        {
           $order->add_order_note( __( $customerNoteText, 'woocommerce-payment-gateway-ccbill' ), 1 );
        }
        
        $this->logMessage( 'ccbill | saving CCBill subscription ID ' . $txId . ' to order: ' . $orderNumber );
        
        $order->update_meta_data( '_ccbill_transaction_id', $txId );
        $order->update_meta_data( '_ccbill_subscription_id', $txId );
        $order->save();
        
        // Add the subscription ID to the subscription as well
        
        $subscriptions = [];
        
        if (function_exists('wcs_get_subscriptions_for_order')) {
          $subscriptions = wcs_get_subscriptions_for_order( $orderNumber );
        }
        
        if ( count( array_values( $subscriptions ) ) > 0  ) {
            foreach ( array_values( $subscriptions ) as $subscription ) {
              
                $this->logMessage( 'saving the CCBill subscription ID ' . $txId  . ' with the woo subscription ' . $subscription->get_id() );
                
                $subscription->update_meta_data( '_ccbill_subscription_id', $txId );
                $subscription->save();
            }
        }
        
        $this->logMessage( 'ccbill | process_ccbill_approval_post | marking payment as complete' );
        
        $order->payment_complete();
        
        // Mark the order as complete if it contains only virtual products
        if ($this->markVirtualOrdersCompleteWhenPaid && $this->order_contains_only_virtual_products( $orderNumber ))
           $order->update_status( 'completed' );
        
        wp_die('Success', 'Success', array( 'response' => 200 ) );
      }
      else{
        wp_die('Failure', 'Failure', array( 'response' => 200 ) );
      }// end if/else

    }// end process_ccbill_approval_post
    
    function getPostValue($keyName) {
      if ( isset( $_POST[ $keyName ] ) ) {
        return sanitize_text_field( $_POST[ $keyName ] );
      }      
      return null;
    }
    
    function getCleanStringValue( $inputValue ) {
      if ( !isset( $inputValue ) )
          return '';      
      return sanitize_text_field( trim( (string) $inputValue ) );
    }
    
    function process_ccbill_rebill_post() {
      
      $this->logMessage( 'ccbill | process_ccbill_rebill_post | Rebill post hit.  Input values: ' );
      
      $this->log_input_values();
      
      $orderNumber = -1;    
      $ccbillTransactionId = $this->getPostValue('transactionId');
      $ccbillSubscriptionId = $this->getPostValue('subscriptionId');   
      $ccbillTransactionTimestamp = $this->getPostValue('timestamp');  
      $billedAmount = $this->getPostValue('billedAmount');     
      $billedCurrency = $this->getPostValue('billedCurrency');  
      $billedCurrencyCode = $this->getPostValue('billedCurrencyCode');     
      $accountingAmount = $this->getPostValue('accountingAmount');     
      $renewalDate = $this->getPostValue('renewalDate');      
      $nextRenewalDate = $this->getPostValue('nextRenewalDate');    
      $cardType = $this->getPostValue('cardType');     
      $cardSubType = $this->getPostValue('cardSubType');    
      $paymentType = $this->getPostValue('paymentType');    
      $last4 = $this->getPostValue('last4');    
      $expDate = $this->getPostValue('expDate');     
      $wooCurrency = strtoupper( $billedCurrency ?? get_woocommerce_currency() );
      $success = !( empty($ccbillTransactionId) || empty($ccbillSubscriptionId) );
      
      $this->logMessage( 'ccbill | process_ccbill_rebill_post | ccbillTransactionId = ' . $ccbillTransactionId . '; ccbillSubscriptionId = ' . $ccbillSubscriptionId . '; wooCurrency = ' . $wooCurrency . '; $success = ' . $success );
      
      if ( $success != true )
      {
        $this->logMessage( 'ccbill | process_ccbill_rebill_post | Success is false.  Exiting.');
        wp_die('Failure', 'Failure', array( 'response' => 200 ) );
      }
      
      // Get any existing orders with the same transaction ID, indicating the renewal has already been processed
      $existingOrders = wc_get_orders([
          'limit'        => 1,
          'type'         => 'shop_order',
          'status'       => array_keys( wc_get_order_statuses() ),
          'meta_key'     => '_ccbill_transaction_id',
          'meta_value'   => $ccbillTransactionId,
          'return'       => 'ids',
      ]);
      
      // If an existing renewal order is found, return an error
      if ( ! empty( $existingOrders ) ) {
          $this->logMessage( 'ccbill | process_ccbill_rebill_post | existing order found for transaction ID ' . $ccbillTransactionId . ': ' . $existingOrders[0] . '; This renewal has already been processed.  Exiting.' );
          status_header( 200 );
          echo 'Already processed';
          return;
      }
      
      $this->logMessage( 'ccbill | process_ccbill_rebill_post | Attempting to locate a subscription by direct meta data for ' . $ccbillSubscriptionId );
      
      $subscriptions = wcs_get_subscriptions([
          'subscriptions_per_page' => 1,
          'subscription_status'    => 'any',
          'meta_query'             => [[
              'key'   => '_ccbill_subscription_id',
              'value' => $ccbillSubscriptionId,
          ]],
          'return'                 => 'objects',
      ]);
      
      if ( empty( $subscriptions )) {
        $this->logMessage( 'ccbill | process_ccbill_rebill_post | no subscriptions were found by direct meta data' );
      }
      else {
        $this->logMessage( 'ccbill | process_ccbill_rebill_post | ' . count( $subscriptions ) . ' subscriptions were found by direct meta data' );
      }      
        
      if ( empty( $subscriptions ) )
      {
          $this->logMessage( 'ccbill | process_ccbill_rebill_post | subscription not found by direct meta data.  Attempting to locate subscription from its parent order.' );
          
          // An order where the transaction ID matches the subscription ID is the first order for the subscription
          $subscriptionOrders = wc_get_orders([
              'limit'        => 1,
              'type'         => 'shop_order',
              'status'       => array_keys( wc_get_order_statuses() ),
              'meta_key'     => '_ccbill_subscription_id',
              'meta_value'   => $ccbillTransactionId,
              'return'       => 'ids',
          ]);
          
          if ( empty( $subscriptionOrders ) )
          {
            $this->logMessage( 'ccbill | process_ccbill_rebill_post | no matching orders were found.' );
          } 
          else
          {
            $this->logMessage( 'ccbill | process_ccbill_rebill_post | ' . count( $subscriptionOrders ) . ' matching orders were found' );
            
            $subscriptions = wcs_get_subscriptions_for_order( $subscriptionOrders[0] );
            
            $this->logMessage( 'ccbill | process_ccbill_rebill_post | ' . count( $subscriptions ) . ' found for order ' . $subscriptionOrders[0] );
          }  
          
      }
      
      $subscription = $subscriptions ? reset( $subscriptions ) : null;
        
      if ( ! $subscription instanceof WC_Subscription ) {
          status_header( 404 );
          echo 'subscription not found';
          return;
      }
      
      // Verify subscription exists and is active
      $remoteSubscriptionIsActive = $this->ccbillSubscriptionIsActive($subscription);
      
      if (!$remoteSubscriptionIsActive) {
        status_header( 404 );
        echo 'remote subscription is not active';
        return;
      }
      
      // Verify currency and amount
      if ( $wooCurrency !== $subscription->get_currency() ||  $billedAmount !== $subscription->get_total()) {
         $this->logMessage( 'ccbill | process_ccbill_rebill_post | A subscription was found, but the currency or total do not match expected values.  wooCurrency: ' . $wooCurrency . '; subscription currency: ' .  $subscription->get_currency() . '; billedAmount: ' . $billedAmount . '; subscription total: ' . $subscription->get_total() . ';' );
      }
      
      $this->logMessage( 'ccbill | process_ccbill_rebill_post | Looking for existing renewal orders...' );
      
      // Find a pending renewal order for this subscription if one exists
      $renewalOrder = null;      
      $renewalOrders = $subscription->get_related_orders( 'all', 'renewal' );
      
      foreach ( $renewalOrders as $renewalOrderId ) {
          $order = wc_get_order( $renewalOrderId );
          
          if ( $order && in_array( $order->get_status(), [ 'pending', 'failed', 'on-hold' ], true ) && $order->get_total() == $amount ) {
              $renewalOrder = $order;              
              
              $this->logMessage( 'ccbill | process_ccbill_rebill_post | Existing renewal order found: ' . $renewalOrderId );
              
              break;
          }
      }
      
      // If no renewal order exists, create one
      if ( ! $renewalOrder ) {
        
         $this->logMessage( 'ccbill | process_ccbill_rebill_post | An existing renewal order was not found.  Createing a new one...' );
        
          $renewalOrder = wcs_create_renewal_order( $subscription );
          if ( is_wp_error( $renewalOrder ) ) {
            $this->logMessage( 'ccbill | process_ccbill_rebill_post | An error occurred while creating a renewal order.  Exiting.' );
              // fail so we can retry later
              status_header( 500 );
              echo 'failed to create renewal order: ' . $renewalOrder->get_error_message();
              return;
          }
          // Ensure the amount matches what the PSP charged (handles proration/discounts)
          $renewalOrder->set_currency( $wooCurrency );
          $renewalOrder->set_total( $billedAmount );
          $renewalOrder->save();
          
          $this->logMessage( 'ccbill | process_ccbill_rebill_post | Renewal order created: ' . $renewalOrder->get_id() );
      }
      
      // Record the payment on the renewal order
      $this->logMessage( 'ccbill | process_ccbill_rebill_post | Recording payment...' );
      $renewalOrder->update_meta_data( '_ccbill_subscription_id', $ccbillSubscriptionId );
      $renewalOrder->update_meta_data( '_ccbill_transaction_id', $ccbillTransactionId );
      $renewalOrder->set_transaction_id( $ccbillTransactionId );
      $renewalOrder->add_order_note( sprintf(
            'Renewal paid via CCBill. Amount: ' . $billedAmount,
            'YourGateway',
            $ccbillTransactionId,
            $billedAmount,
            $wooCurrency
      ) );    
      $renewalOrder->payment_complete( $ccbillTransactionId ); // <-- moves sub forward & sets dates
      $renewalOrder->save();
      
      if ($this->markVirtualOrdersCompleteWhenPaid && $this->order_contains_only_virtual_products( $renewalOrder->get_id() ))
        $renewalOrder->update_status( 'completed' );
      
      $this->logMessage( 'ccbill | process_ccbill_rebill_post | A payment of ' . $billedAmount . ' was recorded.' );
      
      // Sync the next billing date
      if ( ! empty( $nextRenewalDate ) ) {
          $this->logMessage( 'ccbill | process_ccbill_rebill_post | Syncing the next billing date...' );
          
          // Get the time of the last transaction, since it will be used for the next
          $timestampTime = explode(' ', $ccbillTransactionTimestamp)[1];
          
          // Add the time to the next renewal date
          $nextDateTimesString = $nextRenewalDate . ' ' . $timestampTime;
          
          // Create a timezone for AZ (GMT -7)
          $azTimeZone = new DateTimeZone(sprintf("%+03d:00", -7));
          
          // Create DateTime object in the AZ timezone
          $nextDateTime = new DateTime($nextDateTimesString, $azTimeZone);
          
          // Convert the next date/time to UTC
          $nextDateTime->setTimezone(new DateTimeZone("UTC"));
          
          // Get the formatted UTC value
          $formattedNextDateTime = $nextDateTime->format("Y-m-d H:i:s");
          
          $this->logMessage( 'ccbill | process_ccbill_rebill_post | ccbillTransactionTimestamp = ' . $timestampTime . '; nextDateTimesString = ' . $nextDateTimesString . '; formattedNextDateTime = ' . $formattedNextDateTime );
          
          // Append the current hours/minutes/seconds to the renewal date
          $nextTime = strtotime( $formattedNextDateTime );
          // $nextTime = strtotime( $nextRenewalDate . ' ' . gmdate("H:i:s") );
          if ( $nextTime ) {
              $phpDate = gmdate( 'Y-m-d H:i:s', $nextTime );
              $subscription->update_dates( [ 'next_payment' => $phpDate ] );
              $subscription->add_order_note( 'Next payment date synced from CCBill: ' . $phpDate );
              $subscription->save();
              $this->logMessage( 'ccbill | process_ccbill_rebill_post | Next billing date synced. nextRenewalDate: ' . $nextRenewalDate . '; phpDate: ' . $phpDate );
          }
      }
      
      $this->logMessage( 'ccbill | process_ccbill_rebill_post | Completed successfully.  Exiting.' );
      
      wp_die('Success', 'Success', array( 'response' => 200 ) );
      
    }
    
    function process_ccbill_cancellation_post() {
      $this->logMessage( 'ccbill | process_ccbill_cancellation_post | Cancellation post hit' );
      
      $this->log_input_values();
      
      $orderNumber = -1;    
      $transactionId = $this->getPostValue('transactionId'); // Chargeback, Return, Refund, Void
      $subscriptionId = $this->getPostValue('subscriptionId'); // All
      $timestamp = $this->getPostValue('timestamp'); // All
      $clientAccnum = $this->getPostValue('clientAccnum'); // All
      $clientSubacc = $this->getPostValue('clientSubacc'); // All
      $reason = $this->getPostValue('reason'); // Cancellation, Chargeback, Return, Refund, Void
      $source = $this->getPostValue('source'); // Cancellation
      $amount = $this->getPostValue('amount'); // Chargeback, Return, Refund, Void
      $currency = $this->getPostValue('currency'); // Chargeback, Return, Refund, Void
      $currencyCode = $this->getPostValue('currencyCode'); // Chargeback, Return, Refund, Void
      $wooCurrency = strtoupper( $billedCurrency ?? get_woocommerce_currency() );
      $success = !empty($subscriptionId);
      
      $this->logMessage( 'ccbill | process_ccbill_cancellation_post | ccbillTransactionId = ' . $transactionId . '; ccbillSubscriptionId = ' . $subscriptionId . '; wooCurrency = ' . $wooCurrency . '; $success = ' . $success );
      
      if ( $success != true )
      {
        $this->logMessage( 'ccbill | process_ccbill_cancellation_post | Success is false.  Exiting.');
        wp_die('Failure', 'Failure', array( 'response' => 200 ) );
      }
      
      // Get any existing orders with the same transaction ID, indicating the renewal has already been processed
      $subscriptions = wcs_get_subscriptions([
          'subscriptions_per_page' => 1,
          'subscription_status'    => 'any',
          'meta_query'             => [[
              'key'   => '_ccbill_subscription_id',
              'value' => $subscriptionId,
          ]],
          'return'                 => 'objects',
      ]);
      
      if ( empty( $subscriptions )) {
        $this->logMessage( 'ccbill | process_ccbill_cancellation_post | no subscriptions were found by direct meta data.  Exiting.' );
        status_header( 404 ); echo 'subscription not found'; return;
      }
      
      $this->logMessage( 'ccbill | process_ccbill_cancellation_post | ' . count( $subscriptions ) . ' subscriptions were found by direct meta data.' );
        
      $this->logMessage( 'ccbill | process_ccbill_cancellation_post | JSON subscriptions: ' . json_encode( $subscriptions ) . ' subscriptions.' );
      
      $subscription = $subscriptions ? reset( $subscriptions ) : null;
          
      if ( ! $subscription instanceof WC_Subscription ) {
          $this->logMessage( 'ccbill | process_ccbill_cancellation_post | subscription not found.  Exiting.' );
            status_header( 404 );
            echo 'subscription not found';
            return;
       }
       
       // Set Sync property so other processes know we're updating the subscription
       $this->logMessage( 'ccbill | process_ccbill_cancellation_post | setting sync flag in subscription meta data');
       $subscription->update_meta_data( '_ccbill_syncing', 1 );
       $subscription->save();
       
      if ( count( $subscriptions ) > 0 ) {
          $this->logMessage( 'ccbill | process_ccbill_cancellation_post | first subscription ID: ' . $subscription->get_id() );
      }
      
      if ( ! $subscription instanceof WC_Subscription ) {
          $this->logMessage( 'ccbill | process_ccbill_cancellation_post | An error occurred while retrieving the subscription.  Subscription not found.' );
          $this->logMessage( 'ccbill | process_ccbill_cancellation_post | removing sync flag from subscription meta data');
          $subscription->delete_meta_data( '_ccbill_syncing', 1 );
          $subscription->save();
           
          status_header( 404 ); echo 'subscription not found'; return;
      }
      
      // If already cancelled, return success
      $status = $subscription->get_status(); // 'active', 'on-hold', 'cancelled', 'pending-cancellation', etc.
      if ( in_array( $status, [ 'cancelled', 'pending-cancellation', 'expired' ], true ) ) {
          if ( $transactionId ) {
              $subscription->update_meta_data( '_ccbill_cancel_transaction', $transactionId );
              $subscription->save();
          }
          $this->logMessage( 'ccbill | process_ccbill_cancellation_post | removing sync flag from subscription meta data');
          $subscription->delete_meta_data( '_ccbill_syncing', 1 );
          $subscription->save();
          status_header( 200 ); echo 'OK (already cancelled)'; return;
      }
      
      if ( $subscription->can_be_updated_to( 'cancelled' ) ) {
          $subscription->update_status(
              'cancelled',
              __( 'Cancellation received from CCBill: subscription cancelled at CCBill.', 'woocommerce-payment-gateway-ccbill' ),
              true
          );
          $this->logMessage( 'ccbill | process_ccbill_cancellation_post | removing sync flag from subscription meta data');
          $subscription->delete_meta_data( '_ccbill_syncing', 1 );
          $subscription->save();
          return true; // Cancellation successful
      } else if ( $subscription->can_be_updated_to( 'expired' ) ) {
          $subscription->update_status(
              'expired',
              __( 'Cancellation received from CCBill: subscription cancelled at CCBill.', 'woocommerce-payment-gateway-ccbill' ),
              true
          );
          $this->logMessage( 'ccbill | process_ccbill_cancellation_post | removing sync flag from subscription meta data');
          $subscription->delete_meta_data( '_ccbill_syncing', 1 );
          $subscription->save();
          return true; // Expiration successful
      } else {
          // Subscription cannot be updated to 'cancelled' (e.g., already canceled, expired)
          error_log( "Subscription {$subscription_id} cannot be canceled or expired." );
          $this->logMessage( 'ccbill | process_ccbill_cancellation_post | removing sync flag from subscription meta data');
          $subscription->delete_meta_data( '_ccbill_syncing', 1 );
          $subscription->save();
          return false;
      }
      /*
      // Optional: explicitly set end date to now (GMT)
      $subscription->set_date( 'end', gmdate( 'Y-m-d H:i:s' ) );
      
      // Clean up renewal orders that are still pending
      $renewalIds = $subscription->get_related_orders( 'ids', 'renewal' );
      foreach ( $renewalIds as $orderId ) {
          $order = wc_get_order( $orderId );
          if ( $order && in_array( $o->get_status(), [ 'pending', 'on-hold', 'failed' ], true ) ) {
              $order->update_status( 'cancelled', __( 'Cancelled due to PSP cancellation.', 'your-textdomain' ) );
          }
      }
      
      status_header( 200 ); echo 'ok';
      */
    }
    
    function order_contains_only_virtual_products( $order_id ) {
      
        // Get the order
        $order = wc_get_order( $order_id );
    
        // Return if order is false
        if ( ! $order ) 
            return false;
    
        // If any product in the order is not virtual, return false
        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
    
            if ( ! $product->is_virtual() ) {
                return false;
            }
        }
    
        // If we get here, the order contains only virtual products
        return true;
    }

    /**
     * get_ccbill_order function.
     *
     * @param  string $custom
     * @param  string $invoice
     * @return WC_Order object
     */
    private function get_ccbill_order( $custom, $invoice = '' ) {
      $custom = maybe_unserialize( $custom );

      // Backwards comp for IPN requests
      if ( is_numeric( $custom ) ) {
        $order_id  = (int) $custom;
        $order_key = $invoice;
      } elseif( is_string( $custom ) ) {
        $order_id  = (int) str_replace( $this->invoice_prefix, '', $custom );
        $order_key = $custom;
      } else {
        list( $order_id, $order_key ) = $custom;
      }

      $order = new WC_Order( $order_id );

      if ( ! isset( $order->id ) ) {
        // We have an invalid $order_id, probably because invoice_prefix has changed
        $order_id 	= wc_get_order_id_by_order_key( $order_key );
        $order 		= new WC_Order( $order_id );
      }

      // Validate key
      if ( $order->order_key !== $order_key ) {
        $this->logMessage( 'Error: Order Key does not match invoice.' );
        
        exit;
      }

      return $order;
    }

    /**
     * Get the state to send to CCBill
     * @param  string $cc
     * @param  string $state
     * @return string
     */
    public function get_ccbill_state( $cc, $state ) {
      if ( 'US' === $cc ) {
        return $state;
      }

      $states = WC()->countries->get_states( $cc );

      if ( isset( $states[ $state ] ) ) {
        return $states[ $state ];
      }

      return $state;
    }
  }// end class


  function add_ccbill_gateway_class( $methods ) {
    $methods[] = 'WC_Gateway_CCBill';
    return $methods;
  }

  add_filter( 'woocommerce_payment_gateways', 'add_ccbill_gateway_class' );

}// end init function


// Hook in Blocks integration. This action is called in a callback on plugins loaded

add_action( 'woocommerce_blocks_loaded', 'wc_gateway_ccbill_register_order_approval_payment_method_type' );

function wc_gateway_ccbill_register_order_approval_payment_method_type() {
  
  // Check if the required class exists
  if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
      return;
  }
  
  // Get the settings since we are not in object context
  $tSettings = get_option( 'woocommerce_wc_gateway_ccbill_settings', [] );
  
  
  // We get an error if we try to read the object directly, so concert to JSON and back to strip the warning action
  $jSettings = json_encode($tSettings);
  
  $tSettings = json_decode($jSettings);  
  
  $isAdvanced = isset($tSettings->integration_method) && $tSettings->integration_method == 'advanced';
  
  // Include the custom Blocks Checkout class
  if ($isAdvanced)
      require_once plugin_dir_path(__FILE__) . 'includes/Blocks/CCBillAdvancedBlocks.php';
  else
      require_once plugin_dir_path(__FILE__) . 'includes/Blocks/CCBillFlexBlocks.php';
  
  // Hook the registration function to the 'woocommerce_blocks_payment_method_type_registration' action
  add_action(
      'woocommerce_blocks_payment_method_type_registration',
      function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
          // Register an instance of My_Custom_Gateway_Blocks
          $payment_method_registry->register( new WC_Gateway_CCBill_Blocks );
      }
  );
  
}

function wc_gateway_ccbill_register_order_approval_payment_method_type_advanced() {
  
  // Check if the required class exists
  if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
      return;
  }
  
  // Include the custom Blocks Checkout class
  require_once plugin_dir_path(__FILE__) . 'includes/Blocks/CCBillAdvancedBlocks.php';
  
  // Hook the registration function to the 'woocommerce_blocks_payment_method_type_registration' action
  add_action(
      'woocommerce_blocks_payment_method_type_registration',
      function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
          // Register an instance of My_Custom_Gateway_Blocks
          $payment_method_registry->register( new WC_Gateway_CCBill_Blocks );
      }
  );
  
}

/**
 * Custom function to declare compatibility with cart_checkout_blocks feature 
*/
function declare_cart_checkout_blocks_compatibility() {
    // Check if the required class exists
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        // Declare compatibility for 'cart_checkout_blocks'
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}
// Hook the custom function to the 'before_woocommerce_init' action
add_action('before_woocommerce_init', 'declare_cart_checkout_blocks_compatibility');
