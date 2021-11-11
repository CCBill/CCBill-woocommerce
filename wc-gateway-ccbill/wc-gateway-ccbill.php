<?php

/**
 * Plugin Name: WooCommerce Payment Gateway - CCBill
 * Plugin URI: https://www.ccbill.com/cs/wiki/tiki-index.php?page=CCBill+Modules+for+Third+Party+Products#WooCommerce
 * Description: Accept CCBill payments on your WooCommerce website.
 * Version: 1.4.0
 * Author: CCBill
 * Author URI: http://www.ccbill.com/
 * License: Closed
 *
 * @package WordPress
 * @author CCBill
 * @since 1.0.0
 */

add_action( 'plugins_loaded', 'wc_gateway_ccbill_init', 0 );

function wc_gateway_ccbill_init(){

  if(! class_exists('WC_Payment_Gateway')){
    return;
  }// end if

  class WC_Gateway_CCBill extends WC_Payment_Gateway {

  	var $notify_url;

  	/**
  	 * Constructor for the gateway.
  	 *
  	 * @access public
  	 * @return void
  	 */
  	public function __construct() {

  		$this->id                = 'ccbill';
  		$this->icon              = '';//apply_filters( 'woocommerce_ccbill_icon', WC()->plugin_url() . '/assets/images/icons/ccbill.png' );
  		$this->has_fields        = false;
  		$this->order_button_text = __( 'Proceed to Checkout', 'woocommerce' );
  		$this->liveurl           = 'https://bill.ccbill.com/jpost/signup.cgi';
  		$this->testurl           = 'https://bill.ccbill.com/jpost/signup.cgi';
  		$this->baseurl_flex      = 'https://api.ccbill.com/wap-frontflex/flexforms/';
  		$this->method_title      = __( 'CCBill', 'woocommerce' );
  		$this->notify_url        = WC()->api_request_url( 'WC_Gateway_CCBill' );
  		$this->priceVarName      = 'formPrice';
  		$this->periodVarName     = 'formPeriod';

  		// Load the settings.
  		$this->init_form_fields();
  		$this->init_settings();

  		// Define user set variables
  		$this->title            = $this->get_option( 'title' );
  		$this->description      = $this->get_option( 'description' );
  		$this->account_no       = $this->get_option( 'account_no' );
  		$this->sub_account_no   = $this->get_option( 'sub_account_no' );
  		$this->currency_code    = $this->get_option( 'currency_code' );
  		$this->form_name        = $this->get_option( 'form_name' );
  		$this->is_flexform      = $this->get_option( 'is_flexform' ) != 'no';
  		$this->salt             = $this->get_option( 'salt' );
  		$this->debug            = $this->get_option( 'debug' );

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

  		// Payment listener/API hook
  		 add_action( 'woocommerce_api_wc_gateway_ccbill', array( $this, 'check_ccbill_response' ) );


      if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
      } else {
        add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
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
  		if ( ! in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_ccbill_supported_currencies', array( 'AUD', 'BRL', 'CAD', 'MXN', 'NZD', 'HKD', 'SGD', 'USD', 'EUR', 'JPY', 'TRY', 'NOK', 'CZK', 'DKK', 'HUF', 'ILS', 'MYR', 'PHP', 'PLN', 'SEK', 'CHF', 'TWD', 'THB', 'GBP', 'RMB', 'RUB' ) ) ) ) {
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
  		<h3><?php _e( 'CCBill standard', 'woocommerce' ); ?></h3>
  		<p><?php _e( 'CCBill standard works by sending the user to CCBill to enter their payment information.', 'woocommerce' ); ?></p>

  		<?php if ( $this->is_valid_for_use() ) : ?>

  			<table class="form-table">
  			<?php
  				// Generate the HTML For the settings form.
  				$this->generate_settings_html();
  			?>

            <?php if ($this->is_flexform == true) : ?>
            <script type="text/javascript">
                document.getElementById('woocommerce_ccbill_is_flexform').parentElement.parentElement.parentElement.parentElement.style.display = 'none';
                var label = jQuery("label[for='woocommerce_ccbill_form_name']");
                label.html('FlexForm ID <span class="woocommerce-help-tip" data-tip="The ID of the CCBill FlexForm used to collect payment"></span>');
            </script>
            <?php endif; ?>

  			</table><!--/.form-table-->

  		<?php else : ?>
  			<div class="inline error"><p><strong><?php _e( 'Gateway Disabled', 'woocommerce' ); ?></strong>: <?php _e( 'CCBill does not support your store currency.', 'woocommerce' ); ?></p></div>
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
  				'title'   => __( 'Enable/Disable', 'woocommerce' ),
  				'type'    => 'checkbox',
  				'label'   => __( 'Enable CCBill standard', 'woocommerce' ),
  				'default' => 'yes'
  			),
  			'title' => array(
  				'title'       => __( 'Title', 'woocommerce' ),
  				'type'        => 'text',
  				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
  				'default'     => __( 'CCBill', 'woocommerce' ),
  				'desc_tip'    => true,
  			),
  			'description' => array(
  				'title'       => __( 'Description', 'woocommerce' ),
  				'type'        => 'textarea',
  				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
  				'default'     => __( 'Pay with your credit card via CCBill.', 'woocommerce' )
  			),
  			'account_no' => array(
  				'title'       => __( 'Client Account Number', 'woocommerce' ),
  				'type'        => 'text',
  				'description' => __( 'Please enter your six-digit CCBill client account number; this is needed in order to take payment via CCBill.', 'woocommerce' ),
  				'default'     => '',
  				'desc_tip'    => true,
  				'placeholder' => 'XXXXXX'
  			),
  			'sub_account_no' => array(
  				'title'       => __( 'Client SubAccount Number', 'woocommerce' ),
  				'type'        => 'text',
  				'description' => __( 'Please enter your four-digit CCBill client account number; this is needed in order to take payment via CCBill.', 'woocommerce' ),
  				'default'     => '',
  				'desc_tip'    => true,
  				'placeholder' => 'XXXX'
  			),
  			'form_name' => array(
  				'title'       => __( 'Form Name', 'woocommerce' ),
  				'type'        => 'text',
  				'description' => __( 'The name of the CCBill form used to collect payment', 'woocommerce' ),
  				'default'     => '',
  				'desc_tip'    => true,
  				'placeholder' => __( 'XXXcc', 'woocommerce' )
  			),
  			'is_flexform' => array(
  				'title'       => __( 'Flex Form', 'woocommerce' ),
  				'type'        => 'checkbox',
  				'label'       => __( 'Check this box if the form name provided is a CCBill FlexForm', 'woocommerce' ),
  				'default'     => 'yes',
  				'desc_tip'    => true,
  				'description' => __( 'Check this box if the form name provided is a CCBill FlexForm', 'woocommerce' ),
  			),
  			'currency_code' => array(
  				'title'       => __( 'Currency', 'woocommerce' ),
  				'type'        => 'select',
  				'description' => __( 'The currency in which payments will be made.', 'woocommerce' ),
  				'options'     => array( '840' => 'USD',
  				                        '978' => 'EUR',
  		                            '036' => 'AUD',
  		                            '124' => 'CAD',
  		                            '826' => 'GBP',
  		                            '392' => 'JPY'),
  				'desc_tip'    => true
  			),
  			'salt' => array(
  				'title'       => __( 'Salt', 'woocommerce' ),
  				'type'        => 'text',
  				'description' => __( 'The salt value is used by CCBill to verify the hash and can be obtained in one of two ways: (1) Contact client support and receive the salt value, OR (2) Create your own salt value (up to 32 alphanumeric characters) and provide it to client support.', 'woocommerce' ),
  				'default'     => '',
  				'desc_tip'    => true,
  				'placeholder' => __( '', 'woocommerce' )
  			),
  			'debug' => array(
  				'title'       => __( 'Debug Log', 'woocommerce' ),
  				'type'        => 'checkbox',
  				'label'       => __( 'Enable logging', 'woocommerce' ),
  				'default'     => 'no',
  				'description' => sprintf( __( 'Log CCBill events, such as IPN requests, inside <code>woocommerce/logs/ccbill-%s.txt</code>', 'woocommerce' ), sanitize_file_name( wp_hash( 'ccbill' ) ) ),
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

	  $order = new WC_Order( $order_id );

      // Do nothing if the cart total is not greater than zero
      if ( !($woocommerce->cart->total > 0) )
        return null;

  	  // Create hash
  	  //$stringToHash = [price] + [period] + [currencyCode] + [salt];

  	  $wCartTotal = '' . number_format($woocommerce->cart->total, 2, '.', '');

  	  $billingPeriodInDays = 2;
  	  $salt = $this->salt;

  	  $myHash = $this->get_digest($wCartTotal, $billingPeriodInDays, $this->currency_code, $salt);


      $ccbill_addr = $this->liveurl . '?';

      $ccbill_args = 'clientAccnum='    . $this->account_no
                   . '&clientSubacc='   . $this->sub_account_no
                   . '&formName='       . $this->form_name

                   . '&' . $this->priceVarName . '='      . $wCartTotal
                   . '&' . $this->periodVarName . '='     . $billingPeriodInDays

                   . '&currencyCode='   . $this->currency_code
                   . '&customer_fname=' . $_REQUEST['billing_first_name']
                   . '&customer_lname=' . $_REQUEST['billing_last_name']
                   . '&email='          . $_REQUEST['billing_email']
                   . '&zipcode='        . $_REQUEST['billing_postcode']
                   . '&country='        . $_REQUEST['billing_country']
                   . '&city='           . $_REQUEST['billing_city']
                   . '&state='          . $_REQUEST['billing_state']
                   . '&address1='       . $_REQUEST['billing_address_1']
                   . '&wc_orderid='     . $order_id
                   //. '&referingDestURL='. $this->base_url . '/' . 'finish'
                   . '&formDigest='     . $myHash;


			/*
			$postArgs = array(
			  'clientAccnum' => $this->account_no,
			  'clientSubacc' => $this->sub_account_no,
			  'formName'     => $this->form_name,
			  'formPrice'    => $wCartTotal,
			  'formPeriod'   => $billingPeriodInDays,
			  'currencyCode' => $this->currency_code,
			  'salt'         => $this->salt,
			  'formDigest'   => $myHash,
				'result' 	     => 'success',
        'customer_fname'  => $_REQUEST['billing_first_name'],
        'customer_lname'  => $_REQUEST['billing_last_name'],
        'email'           => $_REQUEST['billing_email'],
        'zipcode'         => $_REQUEST['billing_postcode'],
        'country'         => $_REQUEST['billing_country'],
        'wc_orderid'      => $order_id,
        'referingDestURL' => $this->base_url . '/' . 'finish',
				'redirect'        => $this->liveurl
		  );
		  */

			return array(
				'result' 	     => 'success',
				'redirect'     => $this->liveurl . '?' . $ccbill_args
			);

  /*
      $html = 'You are being directed to CCBill...';

      $html .= '<form name="ccbillForm" action="' . $this->liveurl . '" method="POST">';

      $html .= '<include type="hidden" name="clientAccnum" value="' . $this->account_no . '">';
      $html .= '<include type="hidden" name="clientSubacc" value="' . $this->sub_account_no . '">';
      $html .= '<include type="hidden" name="formName"     value="' . $this->form_name . '">';
      $html .= '<include type="hidden" name="' . $this->priceVarName . '"  value="' . $wCartTotal . '">';
      $html .= '<include type="hidden" name="' . $this->periodVarName . '" value="' . $billingPeriodInDays . '">';

      $html .= '<include type="hidden" name="currencyCode"   value="' . $this->currency_code . '">';
      $html .= '<include type="hidden" name="customer_fname" value="' . $_REQUEST['billing_first_name'] . '">';
      $html .= '<include type="hidden" name="customer_lname" value="' . $_REQUEST['billing_last_name'] . '">';
      $html .= '<include type="hidden" name="email"          value="' . $_REQUEST['billing_email'] . '">';
      $html .= '<include type="hidden" name="zipcode"        value="' . $_REQUEST['billing_postcode'] . '">';
      $html .= '<include type="hidden" name="country"        value="' . $_REQUEST['billing_country'] . '">';
      $html .= '<include type="hidden" name="city"           value="' . $_REQUEST['billing_city'] . '">';
      $html .= '<include type="hidden" name="state"          value="' . $_REQUEST['billing_state'] . '">';
      $html .= '<include type="hidden" name="address1"       value="' . $_REQUEST['billing_address_1'] . '">';
      $html .= '<include type="hidden" name="wc_orderid"     value="' . $order_id . '">';
      $html .= '<include type="hidden" name="formDigest"     value="' . $myHash . '">';

      $html .= '</form>';

      $html .= '<script type="text/javascript">document.ccbillForm.submit();</script>';

      echo($html);
  */
  	}

  	/**
  	 * Check for CCBill IPN Response
  	 *
  	 * @access public
  	 * @return void
  	 */
  	function check_ccbill_response() {

  		@ob_clean();

  		$responseAction = isset($_REQUEST['EventType']) ? $_REQUEST['EventType'] : '';

  		if(strlen($responseAction) < 1){
  		  $responseAction = isset($_REQUEST['Action']) ? $_REQUEST['Action'] : '';

  		  if(strpos($responseAction, 'Approval_Post') !== false)
  		    $responseAction = 'approval_post';

  		}// end if

  		// Webhooks screws up any query string arguments added to the first url
  		if(strlen($responseAction) < 1 &&
  		   ((isset($_POST['subscription_id']) && strlen($_POST['subscription_id']) > 0) ||
  		    (isset($_POST['subscriptionId']) && strlen($_POST['subscriptionId']) > 0)))
  		  $responseAction = 'approval_post';

  		global $woocommerce;

  		switch(strToLower($responseAction)){
  		  case 'checkoutsuccess': //print('Checkout Success');
  		                          wp_die('<p>Thank you for your order.  Your payment has been approved.</p><p><a href="' . get_permalink( get_option('woocommerce_myaccount_page_id') ) . '" title="' . _e('My Account','woothemes') . '">My Account</a></p><p><a href="?">Return Home</a></p>', array( 'response' => 200 ) );
  		    break;
  		  case 'checkoutfailure': //wp_die('Checkout Failure');
  		                          wp_die('<p>Unfortunately, your payment was declined.</p><p><a href="' . $cart_url = $woocommerce->cart->get_cart_url() . '">Return to Cart</a></p>', array( 'response' => 200 ) );
  		    break;
  		  case 'approval_post':   //print('Approval Post');
  		  case 'NewSaleSuccess':  $this->process_ccbill_approval_post();
  		    break;
  		  case 'denial_post':
  		  case 'NewSaleFailure':
  		                          wp_die('Failure', array( 'response' => 200 ) );
  		    break;
  		  default: wp_die( "CCBill IPN Request Failure", "CCBill IPN", array( 'response' => 200 ) );
  		    break;
  		}// end switch

      wp_die('Failure', array( 'response' => 200 ) );

  	}

  	// Verify CCBill variables
  	function process_ccbill_approval_post() {

  	  // error_log( 'ccbill | Approval post hit' );

  	  // For now, let's print all post variables
  	  $r = '<table>';

      foreach ($_POST as $key => $value) {
        $r .= '<tr>';
        $r .= '<td>';
        $r .= $key;
        $r .= '</td>';
        $r .= '<td>';
        $r .= $value;
        $r .= '</td>';
        $r .= '</tr>';

      }// end foreach

      $r .= '</table>';

      error_log( 'ccbill | ' . $r);

      // -----------------------------------------------------------

  	  // Get prefix
  	  $p = $_POST;

		  $orderNumber = -1;

		  $prefix = isset($p['X-wc_orderid']) ? 'X-' : '';

      // error_log( 'ccbill check response prefix | ' . $prefix);

		  // Invoice/order number returned as zc_orderid
		  if(isset($p[$prefix . 'wc_orderid']))
		    $orderNumber = $p[$prefix . 'wc_orderid'];
		  else
		    wp_die('Order not found', array( 'response' => 200 ) );


      // error_log( 'ccbill check response order no | ' . $orderNumber);

		  // Check to see if subscription id is present,
		  // indicating a successful transaction.
		  // Classic returns subscription_id,
		  // FlexForms return subscriptionId
		  $txId = '';
		  $success = false;

		  if(strlen($prefix) == 0 && isset($p['subscription_id'])){
  		  $txId = $p['subscription_id'];
  		  $success = true;
  		}
  		else if(strlen($prefix) > 0 && isset($p['subscriptionId'])){
  		  $txId = $p['subscriptionId'];
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

  		  if(isset($p[$prefix . 'formPrice'])) {
  		    $tCartTotal = $p[$prefix . 'formPrice'];
  		    $tPeriod    = $p[$prefix . 'formPeriod'];
  		    $formDigest = $p[$prefix . 'formDigest'];
  		  }
  		  else if(isset($p['billedInitialPrice'])) {
  		    $tCartTotal = $p['billedInitialPrice'];
  		    $tPeriod    = $p['initialPeriod'];
  		  }// end if/else
  		  else if(isset($p['initialPrice'])) {
  		    $tCartTotal = '' . number_format($p['initialPrice'], 2, '.', '');
  		    $tPeriod    = $p['initialPeriod'];
  		  }// end if/else

  		  $tCurrencyCode = isset($p['billedCurrencyCode']) ? $p['billedCurrencyCode'] : '';

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
        $order->add_order_note( __( 'PDT payment completed', 'woocommerce' ) );
        $order->payment_complete();
        wp_die('Success', array( 'response' => 200 ) );
  		}
  		else{
  		  wp_die('Failure', array( 'response' => 200 ) );
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
  						$order->update_status( 'on-hold', sprintf( __( 'Validation error: CCBill currencies do not match (code %s).', 'woocommerce' ), $posted['mc_currency'] ) );
  						exit;
  					}

  					// Validate amount
  					if ( $order->get_total() != $posted['mc_gross'] ) {
  						if ( 'yes' == $this->debug ) {
  							$this->log->add( 'ccbill', 'Payment error: Amounts do not match (gross ' . $posted['mc_gross'] . ')' );
  						}

  						// Put this order on-hold for manual checking
  						$order->update_status( 'on-hold', sprintf( __( 'Validation error: CCBill amounts do not match (gross %s).', 'woocommerce' ), $posted['mc_gross'] ) );
  						exit;
  					}

  					// Validate Email Address
  					if ( strcasecmp( trim( $posted['receiver_email'] ), trim( $this->receiver_email ) ) != 0 ) {
  						if ( 'yes' == $this->debug ) {
  							$this->log->add( 'ccbill', "IPN Response is for another one: {$posted['receiver_email']} our email is {$this->receiver_email}" );
  						}

  						// Put this order on-hold for manual checking
  						$order->update_status( 'on-hold', sprintf( __( 'Validation error: CCBill IPN response from a different email address (%s).', 'woocommerce' ), $posted['receiver_email'] ) );

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
  						$order->add_order_note( __( 'IPN payment completed', 'woocommerce' ) );
  						$order->payment_complete();
  					} else {
  						$order->update_status( 'on-hold', sprintf( __( 'Payment pending: %s', 'woocommerce' ), $posted['pending_reason'] ) );
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
  					$order->update_status( 'failed', sprintf( __( 'Payment %s via IPN.', 'woocommerce' ), strtolower( $posted['payment_status'] ) ) );
  				break;
  				case 'refunded' :

  					// Only handle full refunds, not partial
  					if ( $order->get_total() == ( $posted['mc_gross'] * -1 ) ) {

  						// Mark order as refunded
  						$order->update_status( 'refunded', sprintf( __( 'Payment %s via IPN.', 'woocommerce' ), strtolower( $posted['payment_status'] ) ) );

  						$mailer = WC()->mailer();

  						$message = $mailer->wrap_message(
  							__( 'Order refunded/reversed', 'woocommerce' ),
  							sprintf( __( 'Order %s has been marked as refunded - CCBill reason code: %s', 'woocommerce' ), $order->get_order_number(), $posted['reason_code'] )
  						);

  						$mailer->send( get_option( 'admin_email' ), sprintf( __( 'Payment for order %s refunded/reversed', 'woocommerce' ), $order->get_order_number() ), $message );

  					}

  				break;
  				case 'reversed' :

  					// Mark order as refunded
  					$order->update_status( 'on-hold', sprintf( __( 'Payment %s via IPN.', 'woocommerce' ), strtolower( $posted['payment_status'] ) ) );

  					$mailer = WC()->mailer();

  					$message = $mailer->wrap_message(
  						__( 'Order reversed', 'woocommerce' ),
  						sprintf(__( 'Order %s has been marked on-hold due to a reversal - CCBill reason code: %s', 'woocommerce' ), $order->get_order_number(), $posted['reason_code'] )
  					);

  					$mailer->send( get_option( 'admin_email' ), sprintf( __( 'Payment for order %s reversed', 'woocommerce' ), $order->get_order_number() ), $message );

  				break;
  				case 'canceled_reversal' :

  					$mailer = WC()->mailer();

  					$message = $mailer->wrap_message(
  						__( 'Reversal Cancelled', 'woocommerce' ),
  						sprintf( __( 'Order %s has had a reversal cancelled. Please check the status of payment and update the order status accordingly.', 'woocommerce' ), $order->get_order_number() )
  					);

  					$mailer->send( get_option( 'admin_email' ), sprintf( __( 'Reversal cancelled for order %s', 'woocommerce' ), $order->get_order_number() ), $message );

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
