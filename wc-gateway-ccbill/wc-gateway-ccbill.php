<?php

/**
 * Plugin Name: CCBill Payment Gateway for WooCommerce
 * Plugin URI: https://ccbill.com/doc/ccbill-woocommerce-module
 * Description: Accept CCBill payments on your WooCommerce website.
 * Version: 2.0.0
 * Author: CCBill
 * Author URI: http://www.ccbill.com/
 * License: GPLv2 or later
 *
 * @package WordPress
 * @author CCBill
 * @since 1.0.0
 */
 
 if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
 
/* Defined minimums and constants */

define( 'WC_CCBILL_MAIN_FILE', __FILE__ );
define( 'WC_CCBILL_PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

add_action( 'plugins_loaded', 'wc_gateway_ccbill_init', 0 );

function wc_gateway_ccbill_init(){

  if(! class_exists('WC_Payment_Gateway')){
    return;
  }// end if
  
  load_plugin_textdomain('wc-gateway-ccbill', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');

  class WC_Gateway_CCBill extends WC_Payment_Gateway {

    var $notify_url;
    
    var $liveurl;
    var $testurl;
    var $baseurl_flex;
    var $priceVarName;
    var $periodVarName;
    var $account_no;
    var $sub_account_no;
    var $currency_code;
    var $form_name;
    var $is_flexform;
    var $salt;
    var $debug;
    var $ccbill_currency_codes;
    var $paymentaction;
    var $identity_token;
    var $log;

    /**
     * Constructor for the gateway.
     *
     * @access public
     * @return void
     */
    public function __construct() {

      $this->id                = 'wc_gateway_ccbill';
      $this->icon = WC_CCBILL_PLUGIN_URL . '/assets/images/icons/ccbill-50.png';
      $this->has_fields        = false;
      
      /* translators: Proceed to Checkout button label */
      $this->order_button_text = __( 'Proceed to Checkout', 'woocommerce-payment-gateway-ccbill' );
      $this->liveurl           = 'https://bill.ccbill.com/jpost/signup.cgi';
      $this->testurl           = 'https://bill.ccbill.com/jpost/signup.cgi';
      $this->baseurl_flex      = 'https://api.ccbill.com/wap-frontflex/flexforms/';
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
      $this->currency_code      = $this->get_option( 'currency_code' );
      $this->form_name          = $this->get_option( 'form_name' );
      $this->is_flexform        = $this->get_option( 'is_flexform' ) != 'no';
      $this->salt               = $this->get_option( 'salt' );
      $this->debug              = $this->get_option( 'debug' );

      if($this->is_flexform){
        $this->liveurl = $this->baseurl_flex . $this->form_name;
        $this->priceVarName   = 'initialPrice';
        $this->periodVarName  = 'initialPeriod';
      }// end if

      $this->ccbill_currency_codes =  array(
                                        array("USD", 840),
                                        array("EUR", 978),
                                        array("AUD", 036),
                                        array("CAD", 124),
                                        array("GBP", 826),
                                        array("JPY", 392)
                                      );

      //$this->page_style 		  = $this->get_option( 'page_style' );
      //$this->invoice_prefix	  = $this->get_option( 'invoice_prefix', 'WC-' );
      $this->paymentaction    = $this->get_option( 'paymentaction', 'sale' );
      $this->identity_token   = $this->get_option( 'identity_token', '' );


      // Logs
      if ( 'yes' == $this->debug ) {
        $this->log = new WC_Logger();
      }

      // Actions
       add_action( 'valid-ccbill-standard-ipn-request', array( $this, 'successful_request' ) );
       /*
       str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'wc_gateway_ccbill', home_url( '/' ) ) );
       */
         

      // Payment listener/API hook
      //add_action( 'woocommerce_api_callback', array( $this, 'check_ccbill_response' ) );
      
        
      
       add_action( 'woocommerce_api_wc_gateway_ccbill', array( $this, 'check_ccbill_response' ) );


      if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
        //add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
      } else {
        //add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
        add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
      }// end if/else

      if ( ! $this->is_valid_for_use() ) {
        $this->enabled = false; 
      }
    }

    /**
     * Check if this gateway is enabled and available in the user's country
     *
     * @access public
     * @return bool
     */
    function is_valid_for_use() {
      if ( ! in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_wc_gateway_ccbill_supported_currencies', array( 'AUD', 'BRL', 'CAD', 'MXN', 'NZD', 'HKD', 'SGD', 'USD', 'EUR', 'JPY', 'TRY', 'NOK', 'CZK', 'DKK', 'HUF', 'ILS', 'MYR', 'PHP', 'PLN', 'SEK', 'CHF', 'TWD', 'THB', 'GBP', 'RMB', 'RUB' ) ) ) ) {
        return false;
      }

      return true;
    }

    /**
     * Admin Panel Options
     * - Options for bits like 'title' and availability on a country-by-country basis
     *
     * @since 1.0.0
     */
    public function admin_options() {

      ?>
      <h3><?php esc_html_e( 'CCBill standard', 'woocommerce-payment-gateway-ccbill' ); ?></h3>
      <p><?php esc_html_e( 'CCBill standard works by sending the user to CCBill to enter their payment information.', 'woocommerce-payment-gateway-ccbill' ); ?></p>

      <?php if ( $this->is_valid_for_use() ) : ?>

        <table class="form-table">
        <?php
          // Generate the HTML For the settings form.
          $this->generate_settings_html();
        ?>

            <?php if ($this->is_flexform == true) : ?>
            <script type="text/javascript">
                document.getElementById('woocommerce_wc_gateway_ccbill_is_flexform').parentElement.parentElement.parentElement.parentElement.style.display = 'none';
                var label = jQuery("label[for='woocommerce_wc_gateway_ccbill_form_name']");
                label.html('FlexForm ID <span class="woocommerce-help-tip" data-tip="The ID of the CCBill FlexForm used to collect payment"></span>');
            </script>
            <?php endif; ?>

        </table><!--/.form-table-->

      <?php else : ?>
        <div class="inline error"><p><strong><?php esc_html_e( 'Gateway Disabled', 'woocommerce-payment-gateway-ccbill' ); ?></strong>: <?php esc_html_e( 'CCBill does not support your store currency.', 'woocommerce-payment-gateway-ccbill' ); ?></p></div>
      <?php
        endif;
    }

    /**
     * Initialise Gateway Settings Form Fields
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
          'label'   => __( 'Enable CCBill standard', 'woocommerce-payment-gateway-ccbill' ),
          'default' => 'yes'
        ),
        'title' => array(
          /* translators: Plugin title that customers will see when checking out */
          'title'       => __( 'Title', 'woocommerce-payment-gateway-ccbill' ),
          'type'        => 'text',
          /* translators: Description of the plugin title */
          'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-payment-gateway-ccbill' ),
          /* translators: Plugin title default value */
          'default'     => __( 'CCBill', 'woocommerce-payment-gateway-ccbill' ),
          'desc_tip'    => true,
        ),
        'description' => array(
          /* translators: Plugin description that customers will see when checking out */
          'title'       => __( 'Description', 'woocommerce-payment-gateway-ccbill' ),
          'type'        => 'textarea',
          /* translators: Description of the plugin description */
          'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-payment-gateway-ccbill' ),
          /* translators: Plugin description default value */
          'default'     => __( 'Pay with your credit card via CCBill.', 'woocommerce-payment-gateway-ccbill' )
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
          'title'       => __( 'Client SubAccount Number', 'woocommerce-payment-gateway-ccbill' ),
          'type'        => 'text',
          /* translators: The description for the CCBill client subaccount number field */
          'description' => __( 'Please enter your four-digit CCBill client account number; this is needed in order to take payment via CCBill.', 'woocommerce-payment-gateway-ccbill' ),
          'default'     => '',
          'desc_tip'    => true,
          'placeholder' => 'XXXX'
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
        'is_flexform' => array(
          /* translators: The title for the CCBill flex form name field */
          'title'       => __( 'Flex Form', 'woocommerce-payment-gateway-ccbill' ),
          'type'        => 'checkbox',
          /* translators: The label for the CCBill flex form name field */
          'label'       => __( 'Check this box if the form name provided is a CCBill FlexForm', 'woocommerce-payment-gateway-ccbill' ),
          'default'     => 'yes',
          'desc_tip'    => true,
          /* translators: The description for the CCBill flex form name field */
          'description' => __( 'Check this box if the form name provided is a CCBill FlexForm', 'woocommerce-payment-gateway-ccbill' ),
        ),
        'currency_code' => array(
          /* translators: The title for the currency form name field */
          'title'       => __( 'Currency', 'woocommerce-payment-gateway-ccbill' ),
          'type'        => 'select',
          /* translators: The description for the currency form name field */
          'description' => __( 'The currency in which payments will be made.', 'woocommerce-payment-gateway-ccbill' ),
          'options'     => array( '840' => 'USD',
                                  '978' => 'EUR',
                                  '036' => 'AUD',
                                  '124' => 'CAD',
                                  '826' => 'GBP',
                                  '392' => 'JPY'),
          'desc_tip'    => true
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

    function get_digest($formattedCartTotal, $billingPeriodInDays, $currencyCode, $salt) {

      $stringToHash = '' . $formattedCartTotal
                         . $billingPeriodInDays
                         . $currencyCode
                         . $salt;

      return md5($stringToHash);

    }// end get_digest

    /**
     * Process the payment and return the result
     *
     * @access public
     * @param int $order_id
     * @return array
     */
    function process_payment( $order_id ) {

      global $woocommerce;
      
      global $wp;
      
      $orderPay = false;
      
      if ( isset($wp->query_vars['order-pay']) && absint($wp->query_vars['order-pay']) > 0 ) {
        $orderPay = 1;
        $order_id = absint($wp->query_vars['order-pay']); // The order ID
      }

      //$order = new WC_Order( $order_id );
      $order = wc_get_order( $order_id );
      
      $orderTotal = $order->get_total();
      
      if ( !($orderTotal> 0) )
        return null;
        
      $wTotal = '' . number_format($orderTotal, 2, '.', '');
/*
      // Do nothing if the cart total is not greater than zero
      if ( !($woocommerce->cart->total > 0) )
        return null;
*/
      // Create hash
      //$stringToHash = [price] + [period] + [currencyCode] + [salt];
/*
      $wCartTotal = '' . number_format($woocommerce->cart->total, 2, '.', '');
*/
      $billingPeriodInDays = 2;
      $salt = $this->salt;

      $myHash = $this->get_digest($wTotal, $billingPeriodInDays, $this->currency_code, $salt);


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

      $ccbill_args = 'clientAccnum='    . $this->account_no
                   . '&clientSubacc='   . $this->sub_account_no
                   . '&formName='       . $this->form_name

                   . '&' . $this->priceVarName . '='      . $wTotal
                   . '&' . $this->periodVarName . '='     . $billingPeriodInDays

                   . '&currencyCode='   . $this->currency_code
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

    }

    /**
     * Check for CCBill IPN Response
     *
     * @access public
     * @return void
     */
    function check_ccbill_response() {

      @ob_clean();

      $responseAction = isset($_REQUEST['EventType']) ? sanitize_text_field($_REQUEST['EventType']) : '';

      if(strlen($responseAction) < 1){
        $responseAction = isset($_REQUEST['Action']) ? sanitize_text_field($_REQUEST['Action']) : '';

        if(strpos($responseAction, 'Approval_Post') !== false)
          $responseAction = 'approval_post';

      }// end if

      // Webhooks screws up any query string arguments added to the first url
      if(strlen($responseAction) < 1 &&
         ((isset($_POST['subscription_id']) && strlen($_POST['subscription_id']) > 0) ||
          (isset($_POST['subscriptionId']) && strlen($_POST['subscriptionId']) > 0)))
        $responseAction = 'approval_post';

      global $woocommerce;
      
      $prefix = '';// isset($_POST['X-wc_orderid']) ? 'X-' : '';
      
      $order_id = -1;
      $initialPrice = -1;
      
      $isOrderPay = false;
      
      if(isset($_REQUEST['orderPay']) && sanitize_text_field($_REQUEST['orderPay']) == '1') {
        $isOrderPay = true;
      }
      
      // Invoice/order number returned as wc_orderid
      if(isset($_REQUEST[$prefix . 'wc_orderid']))
      {
        $order_id = sanitize_text_field($_REQUEST[$prefix . 'wc_orderid']);
        $initialPrice = sanitize_text_field($_REQUEST[$prefix . 'initialPrice']);
      }
      
      $order = null;
      
      if ($order_id > 0)
        $order = wc_get_order( $order_id );
        
      $clearCart = false;
      
      
      // If this is not a payment for an order and the order total matches the cart total match as well as item counts, clear the cart
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
        
      switch(strToLower($responseAction)){
        case 'checkoutsuccess': //print('Checkout Success');
                                // clear the cart if the total matches the order total
                                if ( $clearCart ) { WC()->cart->empty_cart(); }
                                wp_die('<p>Thank you for your order.  Your payment has been approved.</p><p><a href="' . esc_url(get_permalink( get_option('woocommerce_myaccount_page_id') ) ) . '" title="' . esc_html__('My Account','woocommerce-payment-gateway-ccbill') . '">My Account</a></p><p><a href="?">Return Home</a></p>', 'Checkout Success', array( 'response' => 200 ) );
          break;
        case 'checkoutfailure': //wp_die('Checkout Failure');
                                wp_die('<p>Unfortunately, your payment was declined.</p><p><a href="' . esc_url($cart_url = $woocommerce->cart->get_cart_url()) . '">Return to Cart</a></p>', 'Checkout Failure', array( 'response' => 200 ) );
          break;
        case 'approval_post':   //print('Approval Post');
        case 'NewSaleSuccess':  $this->process_ccbill_approval_post();
          break;
        case 'denial_post':
        case 'NewSaleFailure':
                                wp_die('Failure', 'Failure', array( 'response' => 200 ) );
          break;
        default: wp_die( "CCBill IPN Request Failure", "CCBill IPN", array( 'response' => 200 ) );
          break;
      }// end switch

      wp_die('Failure', 'Failure', array( 'response' => 200 ) );

    }

    // Verify CCBill variables
    function process_ccbill_approval_post() {

      // error_log( 'ccbill | Approval post hit' );

      // For now, let's print all post variables
      /*
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

      error_log( 'ccbill | ' . $r);
      */
      // -----------------------------------------------------------

      $orderNumber = -1;

      $prefix = isset($_POST['X-wc_orderid']) ? 'X-' : '';

      // error_log( 'ccbill check response prefix | ' . $prefix);

      // Invoice/order number returned as zc_orderid
      if(isset($_POST[$prefix . 'wc_orderid']))
        $orderNumber = sanitize_text_field($_POST[$prefix . 'wc_orderid']);
      else
        wp_die('Order not found', 'Order Not Found', array( 'response' => 200 ) );


      // error_log( 'ccbill check response order no | ' . $orderNumber);

      // Check to see if subscription id is present,
      // indicating a successful transaction.
      // Classic returns subscription_id,
      // FlexForms return subscriptionId
      $txId = '';
      $success = false;

      if(strlen($prefix) == 0 && isset($_POST['subscription_id'])){
        $txId = sanitize_text_field($_POST['subscription_id']);
        $success = true;
      }
      else if(strlen($prefix) > 0 && isset($_POST['subscriptionId'])){
        $txId = sanitize_text_field($_POST['subscriptionId']);
        $success = true;
      }

      $order = null;

      // Attempt to retrieve the order and verify the hash
      if($success == true)
      {
        $order = new WC_Order( $orderNumber );

        $tCartTotal = -1;
        $tPeriod = -1;

        $formDigest = '';

        if(isset($_POST[$prefix . 'formPrice'])) {
          $tCartTotal = sanitize_text_field($_POST[$prefix . 'formPrice']);
          $tPeriod    = sanitize_text_field($_POST[$prefix . 'formPeriod']);
          $formDigest = sanitize_text_field($_POST[$prefix . 'formDigest']);
        }
        else if(isset($_POST['billedInitialPrice'])) {
          $tCartTotal = sanitize_text_field($_POST['billedInitialPrice']);
          $tPeriod    = sanitize_text_field($_POST['initialPeriod']);
        }// end if/else
        else if(isset($_POST['initialPrice'])) {
          $tCartTotal = '' . number_format(sanitize_text_field($_POST['initialPrice']), 2, '.', '');
          $tPeriod    = sanitize_text_field($_POST['initialPeriod']);
        }// end if/else

        $tCurrencyCode = isset($_POST['billedCurrencyCode']) ? sanitize_text_field($_POST['billedCurrencyCode']) : '';

        $wCartTotal = '' . number_format($tCartTotal, 2, '.', '');

        $myHash = $this->get_digest($wCartTotal, $tPeriod, $tCurrencyCode, $this->salt);

        // Compare form digest if we have one.
        // Otherwise, compare ingredients
        if(strlen($formDigest) > 0) {

          if($formDigest != $myHash)
            $success = false;

        }
        else {

          if($wCartTotal != $tCartTotal) {

             $success = false;

          }// end if

        }// end if/else



      }// end if order number was found in arguments

      if($success == true)
      {
        $order = new WC_Order( $orderNumber );
        $order->add_order_note( __( 'PDT payment completed', 'woocommerce-payment-gateway-ccbill' ) );
        $order->payment_complete();
        wp_die('Success', 'Success', array( 'response' => 200 ) );
      }
      else{
        wp_die('Failure', 'Failure', array( 'response' => 200 ) );
      }// end if/else

    }// end process_ccbill_approval_post

    /**
     * Successful Payment!
     *
     * @access public
     * @param array $posted
     * @return void
     */
    function successful_request( $posted ) {

      $posted = stripslashes_deep( $posted );

      // Custom holds post ID
      if ( ! empty( $posted['invoice'] ) && ! empty( $posted['custom'] ) ) {

        $order = $this->get_ccbill_order( $posted['custom'], $posted['invoice'] );

        if ( 'yes' == $this->debug ) {
          $this->log->add( 'ccbill', 'Found order #' . $order->id );
        }

        // Lowercase returned variables
        $posted['payment_status'] 	= strtolower( $posted['payment_status'] );
        $posted['txn_type'] 		= strtolower( $posted['txn_type'] );

        // Sandbox fix
        if ( 1 == $posted['test_ipn'] && 'pending' == $posted['payment_status'] ) {
          $posted['payment_status'] = 'completed';
        }

        if ( 'yes' == $this->debug ) {
          $this->log->add( 'ccbill', 'Payment status: ' . $posted['payment_status'] );
        }

        // We are here so lets check status and do actions
        switch ( $posted['payment_status'] ) {
          case 'completed' :
          case 'pending' :

            // Check order not already completed
            if ( $order->status == 'completed' ) {
              if ( 'yes' == $this->debug ) {
                $this->log->add( 'ccbill', 'Aborting, Order #' . $order->id . ' is already complete.' );
              }
              exit;
            }

            // Check valid txn_type
            $accepted_types = array( 'cart', 'instant', 'express_checkout', 'web_accept', 'masspay', 'send_money' );

            if ( ! in_array( $posted['txn_type'], $accepted_types ) ) {
              if ( 'yes' == $this->debug ) {
                $this->log->add( 'ccbill', 'Aborting, Invalid type:' . $posted['txn_type'] );
              }
              exit;
            }

            // Validate currency
            if ( $order->get_order_currency() != $posted['mc_currency'] ) {
              if ( 'yes' == $this->debug ) {
                $this->log->add( 'ccbill', 'Payment error: Currencies do not match (sent "' . $order->get_order_currency() . '" | returned "' . $posted['mc_currency'] . '")' );
              }

              // Put this order on-hold for manual checking
              /* translators: The error message indicating the order currency does not match the posted currency and the order will therefore be placed on hold for manual checking */
              $order->update_status( 'on-hold', sprintf( __( 'Validation error: CCBill currencies do not match (code %s).', 'woocommerce-payment-gateway-ccbill' ), $posted['mc_currency'] ) );
              exit;
            }

            // Validate amount
            if ( $order->get_total() != $posted['mc_gross'] ) {
              if ( 'yes' == $this->debug ) {
                $this->log->add( 'ccbill', 'Payment error: Amounts do not match (gross ' . $posted['mc_gross'] . ')' );
              }

              // Put this order on-hold for manual checking
              /* translators: The error message indicating the order amount does not match the posted amount and the order will therefore be placed on hold for manual checking */
              $order->update_status( 'on-hold', sprintf( __( 'Validation error: CCBill amounts do not match (gross %s).', 'woocommerce-payment-gateway-ccbill' ), $posted['mc_gross'] ) );
              exit;
            }

            // Validate Email Address
            if ( strcasecmp( trim( sanitize_email($posted['receiver_email']) ), trim( $this->receiver_email ) ) != 0 ) {
              if ( 'yes' == $this->debug ) {
                $this->log->add( 'ccbill', "IPN Response is for another one: {$posted['receiver_email']} our email is {$this->receiver_email}" );
              }

              // Put this order on-hold for manual checking
              /* translators: The error message indicating the order email address does not match the posted email address and the order will therefore be placed on hold for manual checking */
              $order->update_status( 'on-hold', sprintf( __( 'Validation error: CCBill IPN response from a different email address (%s).', 'woocommerce-payment-gateway-ccbill' ), $posted['receiver_email'] ) );

              exit;
            }

             // Store PP Details
            if ( ! empty( $posted['payer_email'] ) ) {
              update_post_meta( $order->id, 'Payer CCBill address', wc_clean( $posted['payer_email'] ) );
            }
            if ( ! empty( $posted['txn_id'] ) ) {
              update_post_meta( $order->id, 'Transaction ID', wc_clean( $posted['txn_id'] ) );
            }
            if ( ! empty( $posted['first_name'] ) ) {
              update_post_meta( $order->id, 'Payer first name', wc_clean( $posted['first_name'] ) );
            }
            if ( ! empty( $posted['last_name'] ) ) {
              update_post_meta( $order->id, 'Payer last name', wc_clean( $posted['last_name'] ) );
            }
            if ( ! empty( $posted['payment_type'] ) ) {
              update_post_meta( $order->id, 'Payment type', wc_clean( $posted['payment_type'] ) );
            }

            if ( $posted['payment_status'] == 'completed' ) {
              /* translators: The order note indicating payment has been completed successfully */
              $order->add_order_note( __( 'IPN payment completed', 'woocommerce-payment-gateway-ccbill' ) );
              $order->payment_complete();
            } else {
              /* translators: The order note indicating payment is still pending */
              $order->update_status( 'on-hold', sprintf( __( 'Payment pending: %s', 'woocommerce-payment-gateway-ccbill' ), wc_clean($posted['pending_reason']) ) );
            }

            if ( 'yes' == $this->debug ) {
              $this->log->add( 'ccbill', 'Payment complete.' );
            }

          break;
          case 'denied' :
          case 'expired' :
          case 'failed' :
          case 'voided' :
            // Order failed
            /* translators: The order status title indicating payment has failed */
            $order->update_status( 'failed', sprintf( __( 'Payment %s via IPN.', 'woocommerce-payment-gateway-ccbill' ), strtolower( wc_clean($posted['payment_status']) ) ) );
          break;
          case 'refunded' :

            // Only handle full refunds, not partial
            if ( $order->get_total() == ( $posted['mc_gross'] * -1 ) ) {

              // Mark order as refunded
              /* translators: The order status title indicating payment has been refunded */
              $order->update_status( 'refunded', sprintf( __( 'Payment %s via IPN.', 'woocommerce-payment-gateway-ccbill' ), strtolower( $posted['payment_status'] ) ) );

              $mailer = WC()->mailer();
              
              $message = $mailer->wrap_message(
                /* translators: The order status title indicating payment has refunded/reversed */
                __( 'Order refunded/reversed', 'woocommerce-payment-gateway-ccbill' ),
                /* translators: The order status description indicating payment has refunded/reversed along with the reason code */
                sprintf( __( 'Order %1$s has been marked as refunded - CCBill reason code: %2$s', 'woocommerce-payment-gateway-ccbill' ), $order->get_order_number(), $posted['reason_code'] )
              );
              
              /* translators: The email subject indicating payment has refunded/reversed */
              $mailer->send( get_option( 'admin_email' ), sprintf( __( 'Payment for order %s refunded/reversed', 'woocommerce-payment-gateway-ccbill' ), $order->get_order_number() ), $message );

            }

          break;
          case 'reversed' :

            // Mark order as refunded
            /* translators: The order status description indicating payment has refunded/reversed along with the reason code */
            $order->update_status( 'on-hold', sprintf( __( 'Payment %s via IPN.', 'woocommerce-payment-gateway-ccbill' ), strtolower( $posted['payment_status'] ) ) );

            $mailer = WC()->mailer();

            $message = $mailer->wrap_message(
              /* translators: The order status indicating the order has been marked as on-hold because the credit card charge has been reversed */
              __( 'Order reversed', 'woocommerce-payment-gateway-ccbill' ),
              /* translators: The order status indicating the order has been marked as on-hold because the credit card charge has been reversed, along with the CCBill reason code */
              sprintf(__( 'Order %1$s has been marked on-hold due to a reversal - CCBill reason code: %2$s', 'woocommerce-payment-gateway-ccbill' ), $order->get_order_number(), $posted['reason_code'] )
            );
            
            /* translators: The email subject indicating the order has been marked as on-hold because the credit card charge has been reversed, along with the CCBill reason code */
            $mailer->send( get_option( 'admin_email' ), sprintf( __( 'Payment for order %s reversed', 'woocommerce-payment-gateway-ccbill' ), $order->get_order_number() ), $message );

          break;
          case 'canceled_reversal' :

            $mailer = WC()->mailer();

            $message = $mailer->wrap_message(
              /* translators: The order status indicating a charge reversal has been canceled */
              __( 'Reversal Cancelled', 'woocommerce-payment-gateway-ccbill' ),
              /* translators: The order description indicating a charge reversal has been canceled */
              sprintf( __( 'Order %s has had a reversal cancelled. Please check the status of payment and update the order status accordingly.', 'woocommerce-payment-gateway-ccbill' ), $order->get_order_number() )
            );

            /* translators: The email subject indicating a charge reversal has been canceled */
            $mailer->send( get_option( 'admin_email' ), sprintf( __( 'Reversal cancelled for order %s', 'woocommerce-payment-gateway-ccbill' ), $order->get_order_number() ), $message );

          break;
          default :
            // No action
          break;
        }

        exit;
      }

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
        if ( 'yes' == $this->debug ) {
          $this->log->add( 'ccbill', 'Error: Order Key does not match invoice.' );
        }
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


// Hook in Blocks integration. This action is called in a callback on plugins loaded, so current Stripe plugin class
// implementation is too late.
add_action( 'woocommerce_blocks_loaded', 'wc_gateway_ccbill_register_order_approval_payment_method_type' );

function wc_gateway_ccbill_register_order_approval_payment_method_type() {
  
  // Check if the required class exists
  if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
      return;
  }
  
  // Include the custom Blocks Checkout class
  require_once plugin_dir_path(__FILE__) . 'ccbill-class-block.php';
  
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
