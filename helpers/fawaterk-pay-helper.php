<?php
class FawaterkPayHelper
{
    const HOST = "https://app.fawaterk.com/api/v2/invoiceInitPay";

    public function __construct(WC_Order $order, array $config, $return_url, $get_order = false)
    {
        $this->order                = $order;
        $this->api_key              = $config['api_key'];
        $this->payment_method_id    = $config['payment_method_id'];
        $this->return_url           = $return_url;
        $this->processOrderData();
        $this->order_currency  = $order->get_currency();
    }

    public function isValid()
    {
        return $this->is_valid;
    }

    public function processOrderData()
    {
        global $woocommerce;

        try {
            $order = $this->order;
            $return_url = $this->return_url;
            $this->cartTotal = WC()->cart->cart_contents_total;
            $this->cartItems = [];

            /**
             * Updating order items list
             * get the list of products
             * @since 1.2.4
             */
            $order_items = $order->get_items();
            if (!empty($order_items)) {
                foreach ($order_items as $item_id => $order_item) {
                    $item_product   = $order_item->get_product();
                    $item_active_price   = $item_product->get_price();
                    $item_name = $order_item->get_name();
                    $item_quantity = intval($order_item->get_quantity());
                    $this->cartItems[] = [
                        'name' => $item_name,
                        'price' => $item_active_price,
                        'quantity' => $item_quantity
                    ];
                }
            }

            // Get mobile wallet number field
            $mobile_wallet_number = false;
            try {
                $json = json_decode($order);
                if (property_exists($json, 'meta_data')) {
                    foreach ($json->meta_data as $key => $value) {
                        if (property_exists($value, 'key') && $value->key == 'fawaterk_wallet_number') {
                            $mobile_wallet_number = $value->value;
                            $this->fawaterk_wallet_number = $value->value;
                        }
                    }
                }
            } catch (Exception $error) {
                // do something
            }

            // Use the phone number if there's no wallet number provided
            if (!$mobile_wallet_number) {
                $mobile_wallet_number = $order->get_billing_phone() ? $order->get_billing_phone() : false;
            }

            $this->customer = [
                "email"           => $order->get_billing_email() ? $order->get_billing_email() : 'none',
                "first_name"      => $order->get_billing_first_name() ? $order->get_billing_first_name() : 'none',
                "last_name"       => $order->get_billing_last_name() ? $order->get_billing_last_name() : 'none',
                "address"         => $order->get_billing_address_1() . ' - ' . $order->get_billing_address_2(),
                // "phone"           => $order->get_billing_phone() ? $order->get_billing_phone(): '00000000000'
                "phone" => $mobile_wallet_number ? $mobile_wallet_number : '00000000000'
            ];

            $this->redirectionUrls = [
                "successUrl"          => $return_url,
                "failUrl"             => $return_url,
                "pendingUrl"          => $return_url
            ];

            $this->processStoreConfig();
        } catch (\Exception $error) {
            throw new \Exception($error->getMessage());
        }
    }



    public function processStoreConfig()
    {
        $valid = true;
        $reasons = array();

        if (!version_compare(PHP_VERSION, '5.4.0', '>=')) {
            $valid = false;
            $reasons[] = "Required php version >= 5.4.0, your PHP version is: " . PHP_VERSION;
        }

        if (!extension_loaded('curl')) {
            $valid = false;
            $reasons[] = "Required php extension: cURL, this extension is not enabled on this server.";
        }

        $this->error = $reasons;
        $this->is_valid = $valid;
    }

    public function processOrder()
    {
        global $woocommerce;
        $payment_data = '';
        foreach ($this->response['payment_data'] as $key => $value) {
            $payment_data = $payment_data . $key . ': <b style="color:DodgerBlue;">' . $value . '</b> <br>';
        }

        $this->order->update_status('pending-payment');
        $this->order->add_order_note('(Awaiting Payment)' . '</br>' . ' Payment Data: <br>' . $payment_data);
        $this->order->update_meta_data('payment_data', $this->response['payment_data']);
        $this->order->update_meta_data('invoice_key', $this->response['invoice_key']);
        $this->order->save();
        $woocommerce->cart->empty_cart();
    }

    public function getError()
    {
        $error = "";

        if (is_string($this->error) && $this->error != '') {
            $error .= "<li>$this->error</li>";
        } else if (is_array($this->error) && !empty($this->error)) {
            foreach ($this->error as $key => $data) {
                if (is_array($data)) {
                    $hints  = "";
                    $field = "$key: ";
                    foreach ($data as $text) {
                        $hints .= " $text " . json_encode($this->cartItems);
                    }
                } else {
                    $field = "";
                    $hints = $data;
                }

                $error .= "<li>$field$hints</li>";
            }
        }
        return $error;
    }

    public function requestOrder()
    {

        $data = [
            "payment_method_id" => $this->payment_method_id,
            "cartTotal"         => $this->cartTotal,
            "cartItems"         => $this->cartItems,
            "currency"          => $this->order_currency,
            "customer"          => $this->customer,
            "redirectionUrls"   => $this->redirectionUrls
        ];
        // Chage $order to Response
        $this->response = $this->HttpPost($data);


        if ($this->response) {
            if (isset($this->response['invoice_key'])) {
                // Return the invoice Key
                return $this->response['invoice_key'];
            }
        }

        return false;
    }

    public function getPaymentData()
    {
        if ($this->response) {
            return $this->response['payment_data'];
        }

        return false;
    }


    public function requestWalletUrl($phone_number)
    {
        $data = [
            "source"        => ["identifier" => $phone_number, "subtype" => "WALLET"],
            "customer"       => $this->customer,
            "payment_token" => $this->payment_token,
        ];

        $request = $this->HttpPost("acceptance/payments/pay", $data);

        if ($request) {
            if (isset($request->redirect_url)) {
                return $request->redirect_url;
            }
        }

        return false;
    }

    private function HttpPost($data = [])
    {
        $response = wp_remote_post(
            self::HOST,
            [
                'method' => 'POST',
                'timeout' => 90,
                'sslverify' => false,
                'redirection' => 10,
                'httpversion' => '2',
                'blocking' => true,
                'headers' => [
                    "Content-Type" => "application/json",
                    "Authorization" => "Bearer $this->api_key"
                ],
                'body' => json_encode($data),
            ]
        );


        if (is_wp_error($response)) {
            $this->error = $response->get_error_message();
        }

        $this->response_raw_data = json_decode($response['body'], true);


        if ($this->response_raw_data['status'] === 'error') {
            $this->error = $this->response_raw_data['message'];
            throw new Exception($this->getError());
        }
        return $this->response_raw_data['data'];
    }
}
