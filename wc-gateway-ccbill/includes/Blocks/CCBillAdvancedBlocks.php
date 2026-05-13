<?php


use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;

final class WC_Gateway_CCBill_Blocks extends AbstractPaymentMethodType {
    private $gateway;
    protected $name = 'wc-gateway-ccbill';// your payment gateway name
    
    // Add the gateway to the available payment gateways
    function add_ccbill_gateway( $gateways ) {
        $gateways[] = 'WC_Gateway_CCBill';
        return $gateways;
    }
    
    public function add_custom_checkout_field( $fields ) {
        /*
        $fields['billing']['billing_custom_field'] = array(
            'type'        => 'text',
            'label'       => __( 'Custom Field' ),
            'placeholder' => __( 'Enter custom data' ),
            'required'    => false,
        );
        */
        return $fields;        
    }
    
    public function initialize() {
        $this->settings = get_option( 'woocommerce_wc_gateway_ccbill_settings', [] );
        $this->gateway = new WC_Gateway_CCBill();
        add_action(
            'woocommerce_rest_checkout_process_payment_with_context',
            [ $this, 'process_store_api_payment' ],
            10, 2 // ($context, $payment_result)
        );
        wp_enqueue_script( 'ccbill_functions_js', $this->gateway->plugin_base_url . 'includes/js/ccbillFunctions.js' );
    }
    public function is_active() {
        return $this->gateway->is_available() && $this->get_setting( 'integration_method' ) == 'advanced';
    }
    public function get_payment_method_script_handles() {
        
        wp_register_script(
            'wc_gateway_ccbill-blocks-integration',
            plugin_dir_url(__FILE__) . '/tokenCheckout.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            null,
            true
        );
        if( function_exists( 'wp_set_script_translations' ) ) {            
            wp_set_script_translations( 'wc_gateway_ccbill-blocks-integration');
            
        }
        return [ 'wc_gateway_ccbill-blocks-integration' ];
    }
    
    public function get_payment_method_script_handles_for_admin() {
        return $this->get_payment_method_script_handles();
    }
    
    public function get_payment_method_data() {
        
        $recurringItemCount = 0;
        
        if ($this->gateway->cart_contains_recurring_items()) {
          
            // Fill in recurring data
            $recurringItemCount = $this->gateway->get_cart_recurring_item_count();
        }
        
        if (!$this->gateway->advanced_integration) {
          echo wpautop( wp_kses_post( $this->gateway->description) );
          
          return;
        }
        
        // Get the OAuth token for the front end
        $oauthToken = $this->gateway->get_oauth_token_frontend();
        $authTokenUsername = esc_js( $this->gateway->frontend_username );
        $authTokenPassword = esc_js( $this->gateway->frontend_password );
        $currencyCode = $this->gateway->get_store_ccbill_currency_code();
        $userIpAddress = $this->gateway->get_user_ip();
        $currencyCode = $this->gateway->get_store_ccbill_currency_code();
        $integrationMethod = $this->gateway->integration_method;
        
        
        $paymentMethodData = [
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'supports' => $this->gateway->supports,
            'oauthToken' => $oauthToken,
            'applicationId' => $authTokenUsername,
            'clientAccnum' => $this->gateway->account_no_advanced,
            'clientSubacc' => $this->gateway->sub_account_token_generation,
            'userIpAddress' => $userIpAddress,
            'currencyCode' => $currencyCode,
            'integration_method' => $integrationMethod,
            'debug' => $this->gateway->debug,
        ];
        //die('paymentMethodData = ' . json_encode($paymentMethodData));
        return $paymentMethodData;
    }
    public function process_store_api_payment( $context, $result ) {
        
        $this->gateway->logMessage('process_store_api_payment hit');
        $this->gateway->logMessage( 'process_store_api_payment | context: ' . json_encode( $context ) );
        $this->gateway->logMessage( 'process_store_api_payment | context->payment_method: ' . $context->payment_method );
        $this->gateway->logMessage( 'process_store_api_payment | this->name: ' . $this->name );
        
        if ( str_replace('-', '_', $context->payment_method) !== str_replace('-', '_', $this->name) ) {
            
            $this->gateway->logMessage( 'process_store_api_payment | context->payment_method does not match this->name.  Exiting.' );
            return;
        }
        
        $this->gateway->logMessage("process_store_api_payment 2");
        
        $order = $context->order; // WC_Order for the draft order
        $data  = $context->payment_data; // data returned from your JS: paymentMethodData
            
        $orderId = $order->get_id();
        $orderTotal = $order->get_total();
        $periodInDays = 2;
        $isRecurringCharge = false;
        $subscription = null;
        
        if (function_exists('wcs_get_subscription')) {
            $subscription = wcs_get_subscription( $orderId );
        }
            
        // Read values emitted from JS
        // property names in this object are lowercase
        $paymentToken = $this->gateway->getCleanStringValue( $data['token'] );
        $ccbillAuthSubscriptionId  =  $this->gateway->getCleanStringValue( $data['subscriptionid'] );
        $save = ! empty( $data['save'] );
        
        $this->gateway->logMessage( 'process_store_api_payment | ccbillAuthSubscriptionId: ' . $ccbillAuthSubscriptionId );
        $this->gateway->logMessage( 'process_store_api_payment | order: ' . json_encode( $order ) );
        $this->gateway->logMessage( 'process_store_api_payment | data: ' . json_encode( $data ) );
        
        $wooCurrency = $order->get_currency();
        $ccbillCurrencyCode = $this->gateway->get_ccbill_currency_code( $wooCurrency );
        
        $this->gateway->logMessage( 'process_store_api_payment | wooCurrency: ' . $wooCurrency );
        $this->gateway->logMessage( 'process_store_api_payment | ccbillCurrencyCode: ' . $ccbillCurrencyCode );
        
        $subscriptions = [];
        
        if (function_exists('wcs_get_subscriptions_for_order')) {
            $subscriptions = wcs_get_subscriptions_for_order( $order, array( 'order_type' => 'parent' ) );
        }        
                
        $this->gateway->logMessage( 'process_store_api_payment | subscriptions for order: ' . json_encode($subscriptions) );
        
        foreach ( $subscriptions as $tSubscription ) {
            $this->gateway->logMessage( 'process_payment_advanced_integration | adding meta data to subscription ' . $tSubscription->get_id() . ': ' . json_encode($tSubscription) );
            
            add_action( 'woocommerce_subscription_status_active', function( $tSubscription ) use ( $paymentToken ) {
                $tSubscription->update_meta_data( '_ccbill_payment_token', $paymentToken );
                $tSubscription->save();
            } );
          /*
            $tSubscription->update_meta_data( '_ccbill_payment_token', $paymentToken );
            $tSubscription->update_meta_data( '_ccbill_subscription_id', $ccbillAuthSubscriptionId );
            $tSubscription->save();
            */
        }
        
        $this->gateway->logMessage( 'process_store_api_payment | pre-charge calculations.');
        $this->gateway->logMessage( 'process_store_api_payment | orderTotal: ' . $orderTotal );
        $this->gateway->logMessage( 'process_store_api_payment | periodInDays: ' . $periodInDays );
        $this->gateway->logMessage( 'process_store_api_payment | isRecurringCharge: ' . $isRecurringCharge );
        $this->gateway->logMessage( 'process_store_api_payment | ccbillCurrencyCode: ' . $ccbillCurrencyCode );
        
        // If this order has zero due, and a subscription is present, then it has a free trial
        // Set the subscription from the payment token and mark the order as complete without charging
        if ( !($orderTotal > 0) ) { 
            $this->gateway->logMessage( 'process_store_api_payment | The order total is not greater than zero.');
            if ( empty( $subscriptions ) ) {
                $this->gateway->logMessage( 'process_store_api_payment | No subscriptions are present.  Minimum price not met.');
                wc_add_notice( 'Minimum initial price not met.  Initial Price: ' . $orderTotal, 'error' );
                  return null;
            }
            else {
                $this->gateway->logMessage( 'process_store_api_payment | Subscriptions are present.  Saving subscriptionId and marking order as complete.');
                $order->payment_complete( $ccbillAuthSubscriptionId );
                $order->update_meta_data( '_ccbill_transaction_id', $ccbillAuthSubscriptionId );
                $order->update_meta_data( '_ccbill_subscription_id', $ccbillAuthSubscriptionId );
                $order->save();
            }            
        }
        else {
            $this->gateway->logMessage( 'process_store_api_payment | preparing to charge via api_charge.');
            $charge = $this->gateway->api_charge( $orderTotal, $periodInDays, $isRecurringCharge, $ccbillCurrencyCode, $paymentToken, $order, $subscription );
            
            $this->gateway->logMessage( 'process_store_api_payment | api_charge complete.  charge: ' . json_encode($charge) );
            
            if ( is_wp_error( $charge ) || !$charge['subscriptionId']) {
                
                if ( is_wp_error( $charge ) ) {
                    $this->gateway->logMessage( 'process_store_api_payment | payment declined.  charge is_wp_error' );
                    wc_add_notice( 'Payment declined', 'error' );
                }
                else {
                    if ($charge['declineText'])
                    wc_add_notice( $charge['declineText'], 'error' );
                    else if ($charge['generalMessage'])
                    wc_add_notice( $charge['generalMessage'], 'error' );
                    $this->gateway->logMessage( 'process_store_api_payment | payment declined.  is_wp_error: ' . is_wp_error( $charge ) . '; subscriptionId: ' . $charge['subscriptionId'] );
                }
                return;
            }
            
            // On success, complete order and save token on subscription
            $order->payment_complete( $charge['subscriptionId'] );
            
            $this->gateway->logMessage( 'process_store_api_payment | api_charge complete.');
        }
        
        // Mark the order as complete if it contains only virtual products
        if ($this->gateway->markVirtualOrdersCompleteWhenPaid && $this->gateway->order_contains_only_virtual_products( $orderId ))
          $order->update_status( 'completed' );
          
        WC()->cart->empty_cart();
        
        $this->gateway->logMessage( 'process_store_api_payment | Checkout complete.');
    
        // Update the order status to success
        $orderUrl = $this->gateway->get_return_url( $order );
        $result->set_status( 'success' );
        $result->set_redirect_url( $orderUrl );
    }
}
?>