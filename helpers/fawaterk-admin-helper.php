<?php
class FawaterkAdminHelper
{


    public static function return_admin_options($method_title, $method_description)
    {
        $options = [
            'enabled'     => array(
                'title'   => __('Enable/Disable', 'woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Enable ', 'woocommerce'),
                'default' => 'no',
            ),
            'title'                 => array(
                'title'       => __('Method Title', 'woocommerce'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                'default'     => __($method_title, 'woocommerce'),
                'desc_tip'    => true,
            ),
            'description'           => array(
                'title'       => __('Method Description', 'woocommerce'),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => __('This controls the description which the user sees during checkout.', 'woocommerce'),
                'default'     => __($method_description, 'woocommerce'),
            ),
            'api_key'        => array(
                'title'       => __('API KEY', 'woocommerce'),
                'description' => __('Enter your Fawaterak Api Key to process payments via Fawaterak.', 'woocommerce'),
                'type'        => 'text',
            ),
            'webhook_url'           => array(
                'title'       => __('WebHook Url', 'woocommerce'),
                'type'        => 'text',
                'description' =>  __('Copy This to the redirect url field at Fawaterak Website'),
                'default' => get_site_url() . '/wc-api/fawaterk_webhook',
                'custom_attributes' => array('readonly' => 'readonly'),
            ),


        ];


        return $options;
    }

    public static function webhook_callback($api_key)
    {
        global $wpdb, $woocommerce;

        try {
            $vendor_key = $api_key;

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


            $orderMeta = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key='invoice_key' AND  meta_value = '" . $invoice_key . "' limit 1");


            if (count($orderMeta) == 0)
                throw new \Exception('Invalid invoice key', 400);

            $order = wc_get_order($orderMeta[0]->post_id);
            if ($order->is_paid()) throw new \Exception('Order already processed', 200);

            if ($invoice_status == 'paid') {
                // we received the payment
                $order->payment_complete();
                wc_reduce_stock_levels($orderMeta[0]->post_id);
                // some notes to customer (replace true with false to make it private)
                $order->add_order_note(sprintf('Hey, your order is paid via Fawaterak ( %s ) Thank you!', $payement_method), true);

                // Empty cart
                $woocommerce->cart->empty_cart();
                throw new \Exception('Order paid successfully', 200);
            } elseif ($invoice_status == 'expired') {
                // cancel order
                $order->update_status('cancelled', 'Order was cancelled as Fawry invoice has expired');
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
