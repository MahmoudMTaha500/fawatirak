<?php

class WC_Gateway_Fawaterak extends WC_Payment_Gateway
{

    public function __construct()
    {

        $this->id = 'fawaterak'; // payment gateway plugin ID

        // The title to be used for the vertical tabs that can be ordered top to bottom
        $this->title = __('Fawaterak', 'fawaterak');

        $this->icon = WOOCOMMERCE_GATEWAY_FAWATERAK_URL . '/assets/images/paywf.png'; // URL of the icon that will be displayed on checkout page near your gateway name
        // Bool. Can be set to true if you want payment fields to show on the checkout
        // if doing a direct integration, which we are doing in this case
        $this->has_fields = true;

        // Supports the default credit card form
        $this->supports = array('default_credit_card_form');

        $this->method_title = __('Fawterak Gateway', 'fawaterak');

        /**
         * Moved settings page message
         */
        $settings_hint_text  = get_locale() === 'ar' ? esc_html__('تم نقل صفحة الإعدادات إلى ', 'fawaterk') : esc_html__('Settings page has been moved to ', 'fawaterk');
        $settings_page_url = get_admin_url() . '/admin.php?page=fawaterk_settings';
        $settings_page_url_text = get_locale() === 'ar' ? esc_html__('هنا', 'fawaterk') : esc_html__('here', 'fawaterk');

        $settings_hint = wp_sprintf('<a class="button-secondary" href="%2$s">%1$s  %3$s</a>', $settings_hint_text, $settings_page_url, $settings_page_url_text);

        $this->method_description = $settings_hint;

        // gateways can support subscriptions, refunds, saved payment methods,
        $this->supports = array(
            'products'
        );

        // Method with all the options fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        // Turn these settings into variables we can use
        foreach ($this->settings as $setting_key => $value) {
            $this->$setting_key = $value;
        }

        // Lets check for SSL
        // add_action('admin_notices', array($this,    'do_ssl_check'));


        // Save settings
        if (is_admin()) {
            // Versions over 2.0
            // Save our administration options. Since we are not going to be doing anything special
            // we have not defined 'process_admin_options' in this class so the method in the parent
            // class will be used instead
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }

        // You can also register a webhook here

        add_action('woocommerce_api_fawaterak_webhook', array($this, 'webhook'));
    }


    /**
     * Plugin options
     */
    public function init_form_fields()
    {
        if (strpos(http_build_query($_GET), 'page=wc-settings&tab=checkout&section=fawaterak') !== false) {
            add_action('admin_head', function () {
                echo '<style>p.submit {display: none;}</style>';
            });
            wp_redirect(get_admin_url() . '/admin.php?page=fawaterk_settings');
        }
        // Commented to enable custom settings page
        // $this->form_fields = array(
        //     'enabled'               => array(
        //         'title'   => __('Enable/Disable', 'woocommerce'),
        //         'type'    => 'checkbox',
        //         'label'   => __('Enable Fawaterak', 'woocommerce'),
        //         'default' => 'no',
        //     ),
        //     'title'                 => array(
        //         'title'       => __('Title', 'woocommerce'),
        //         'type'        => 'text',
        //         'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
        //         'default'     => __('Fawaterak', 'woocommerce'),
        //         'desc_tip'    => true,
        //     ),
        //     'description'           => array(
        //         'title'       => __('Description', 'woocommerce'),
        //         'type'        => 'text',
        //         'desc_tip'    => true,
        //         'description' => __('This controls the description which the user sees during checkout.', 'woocommerce'),
        //         'default'     => __("Pay via Fawaterak: You can pay with Credit/Debit cards or via Fawry and mobile wallets.", 'woocommerce'),
        //     ),
        //     'mobile_wallet_title'                 => array(
        //         'title'       => __('Mobile Wallet Title English', 'woocommerce'),
        //         'type'        => 'text',
        //         'description' => __('This controls mobile wallet payment title which the user sees during checkout.', 'woocommerce'),
        //         'default'     => __('Mobile Wallet', 'woocommerce'),
        //         'desc_tip'    => true,
        //     ),
        //     'fawry_title'                 => array(
        //         'title'       => __('Fawry Title English', 'woocommerce'),
        //         'type'        => 'text',
        //         'description' => __('This controls mobile wallet payment title which the user sees during checkout.', 'woocommerce'),
        //         'default'     => __('Fawry', 'woocommerce'),
        //         'desc_tip'    => true,
        //     ),
        //     'private_key'           => array(
        //         'title'       => __('API credentials', 'woocommerce'),
        //         'type'        => 'text',
        //         'description' =>  __('Enter your Fawaterak API credentials to process payments via Fawaterak.'),
        //     ),
        //     'webhook_url'           => array(
        //         'title'       => __('WebHook Url', 'woocommerce'),
        //         'type'        => 'text',
        //         'description' =>  __('Copy This to the redirect url field at Fawaterak Website'),
        //         'default' => get_site_url() . '/wc-api/fawaterak_webhook',
        //         'custom_attributes' => array('readonly' => 'readonly'),
        //     ),
        // );
    }
    /*
    * Fields validation
    */
    public function validate_fields()
    {
        return true;
    }

    /*
    * We're processing the payments here
    */
    public function process_payment($order_id)
    {
        global $woocommerce;

        // we need it to get any order detailes
        $order = wc_get_order($order_id);

        /*
        * Array with parameters for API interaction
        */
        $args = array(
            'method'      => 'POST',
            'headers'   => [
                'Content-Type' => 'application/json'
            ],
            'timeout'   => 90,
            'sslverify' => false,

        );

        $i = 0;
        $cartItems = array();

        if (defined('FAWATERAK_AWEBOOKING_STORED')) {
            include_once(WOOCOMMERCE_GATEWAY_FAWATERAK_URL . '/awebooking_Integration/process_payment.php');
        } else {

            foreach ($order->get_items() as $item_id => $item_data) {
                // Get an instance of corresponding the WC_Product object
                $product = $item_data->get_product();
                $product_name = $product->get_name();
                $product_price = $product->get_price();

                $item_quantity = intval($item_data->get_quantity()); // Get the item quantity
                $cartItems[$i] = [
                    "name" => $product_name,
                    "price" => $product_price,
                    "quantity" => $item_quantity
                ];
                $i++;
            }

        }


        // Get mobile wallet number field
        $mobile_wallet_number = intval(get_post_meta($order->id, '_fawaterk_wallet_number', true)) != '' ? intval(get_post_meta($order->id, '_fawaterk_wallet_number', true)) : intval($order->get_billing_phone());

        $payload = array(
            "vendorKey" => get_option('fawaterk_plugin_options')['private_key'],
            "currency" => get_woocommerce_currency(),
            "cartTotal" => floatval($order->get_total()),
            "customer" => [
                'first_name' => $order->get_billing_first_name(),
                "last_name" => $order->get_billing_last_name(),
                "email" => $order->get_billing_email(),
                // "phone" => intval($order->get_billing_phone()),
                "phone" => $mobile_wallet_number,
                "address" => $order->get_billing_address_1() ? $order->get_billing_address_1() :  'none'
            ],

            "redirectUrl" => $order->get_checkout_order_received_url()
        );

        // Apply coupons if any
        $couponsAsCodes = $woocommerce->cart->get_applied_coupons();

        foreach ($couponsAsCodes as $couponCode) {
            $coupon = new WC_Coupon($couponCode);
            $cartItems[] = [
                "name" => sprintf('Coupon (%s)', $couponCode),
                "price" => -$woocommerce->cart->get_coupon_discount_amount(
                    $coupon->get_code(),
                    $woocommerce->cart->display_cart_ex_tax
                ),
                "quantity" => 1
            ];
        }

        // Add Fees as Cart Items
        $fees = $woocommerce->cart->get_fees();
        foreach ($fees as $fee) {
            $cartItems[] = [
                "name" => $fee->name,
                "price" => round($fee->amount),
                "quantity" => 1
            ];
        }


        if ($order->get_total_shipping()) {
            $cartItems[] = [
                "name" => 'Shipping fees',
                "price" => $order->get_total_shipping(),
                "quantity" => 1
            ];
        }
        $payload['cartItems'] = $cartItems;


        $args['body'] = json_encode($payload);
        echo "<pre> xxxxxx";        print_r($args); echo "</pre>"; 
        

        /*
        * Your API interaction could be built with wp_remote_post()
        */
        $response = wp_remote_post('https://fawaterak.herokuapp.com/api/invoice', $args);




        if (!is_wp_error($response)) {

            $body = json_decode($response['body'], true);

            // it could be different depending on your payment processor
            if ($response['response']['code'] == 200) {

                // Save invoice key to order meta
                $order->update_meta_data('invoiceKey', $body['invoiceKey']);
                $order->update_meta_data('invoiceId', $body['invoiceId']);

                $order->save();

                $woocommerce->cart->empty_cart();

                // Redirect to the thank you page
                return array(
                    'result' => 'success',
                    'redirect' => $body['url']
                );
            } else {
                wc_add_notice('Please try again.', 'error');
                return;
            }
        } else {
            wc_add_notice('Connection error.', 'error');
            return;
        }

    }

    // Check if we are forcing SSL on checkout pages
    // Custom function not required by the Gateway
    public function do_ssl_check()
    {
        if ($this->enabled == "yes") {
            if (get_option('woocommerce_force_ssl_checkout') == "no") {
                echo "<div class=\"error\"><p>" . sprintf(__("<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>"), $this->method_title, admin_url('admin.php?page=wc-settings&tab=checkout')) . "</p></div>";
            }
        }
    }

    /*
    * In case you need a webhook
    */
    public function webhook()
    {

        global $wpdb, $woocommerce;

        try {
            $vendor_key = get_option('fawaterk_plugin_options')['private_key'];

            if (!isset($_POST['api_key']) | $_POST['api_key'] != $vendor_key)
                throw new \Exception('Invalid api key', 401);

            if (!isset($_POST['payment_method']))
                throw new \Exception('Missing payment method', 400);

            $payement_method = $_POST['payment_method'];

            if (!isset($_POST['invoice_status']))
                throw new \Exception('Missing invoice status', 400);

            $invoice_status = $_POST['invoice_status'];

            if (!isset($_POST['invoice_key']))
                throw new \Exception('Missing invoice key', 400);

            $invoice_key = $_POST['invoice_key'];

            $args = array(
                'invoiceKey' => $invoice_key,
            );

            $orders = wc_get_orders($args);

            $orderMeta = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key='invoice_key' AND  meta_value = '" . $invoice_key . "' limit 1");




            if (count($orderMeta) == 0)
                throw new \Exception('Invalid invoice key', 400);

            $order = wc_get_order($orderMeta[0]->post_id);
            if ($order->is_paid()) throw new \Exception('Order already processed', 200);

            if ($invoice_status == 'paid') {
                // we received the payment
                $order->payment_complete();
                $order->reduce_order_stock();
                // some notes to customer (replace true with false to make it private)
                $order->add_order_note(sprintf('Hey, your order is paid via Fawaterak ( %s ) Thank you!', $payement_method), true);

                // Empty cart
                $woocommerce->cart->empty_cart();
                throw new \Exception('Order paid successfully', 200);
            } elseif ($invoice_status == 'expired') {
                // cancel order
                $order->cancel_order('Order was cancelled as Fawry invoice has expired');
                throw new \Exception('Order cancelled successfully', 200);
            } else {
                throw new \Exception('Invalid invoice status', 400);
            }
        } catch (\Exception $e) {

            header('Content-type: application/json');
            header("HTTP/1.0 {$e->getCode()}");
            echo json_encode([
                'message' => $e->getMessage()
            ]);
            die;
        }
    }
}
