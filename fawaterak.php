<?php
/*
 * Plugin Name: Fawaterak
 * Plugin URI:  https://www.fawaterk.com/
 * Description: Fawaterak payment gateway.
 * Author: Fawaterak
 * Author URI: https://www.fawaterk.com/
 * Version: 1.2.1
 *
*/

if (!defined('ABSPATH')) {
    exit;
}

define('WOOCOMMERCE_GATEWAY_FAWATERAK_VERSION', '1.0.6'); // WRCS: DEFINED_VERSION.
define('WOOCOMMERCE_GATEWAY_FAWATERAK_URL', untrailingslashit(plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__))));
define('WOOCOMMERCE_GATEWAY_FAWATERAK_PATH', untrailingslashit(plugin_dir_path(__FILE__)));

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action('plugins_loaded', 'fawaterak_init_gateway_class', 0);
function fawaterak_init_gateway_class()
{
    if (!class_exists('WC_Payment_Gateway')) return;
    include_once 'helpers/fawaterk-admin-helper.php';
    include_once 'helpers/fawaterk-pay-helper.php';

    // If we made it this far, then include our Gateway Class
    include_once('includes/WC_Gateway_Fawaterak.php');

    // Now that we have successfully included our class,
    // Lets add it too WooCommerce

    /*
    * This action hook registers our PHP class as a WooCommerce payment gateway
    */
    add_filter('woocommerce_payment_gateways', 'fawaterak_add_gateway_class');

    function fawaterak_add_gateway_class($gateways)
    {
        $gateways[] = 'WC_Gateway_Fawaterak';

        return $gateways;
    }

    //  Check if Awebooking is Enabled and if include the file
    if (class_exists('AweBooking')) {
        include_once('awebooking_Integration/store.php');
    }
}



add_action('woocommerce_blocks_loaded', 'woocommerce_fawaterak_woocommerce_blocks_support');

function woocommerce_fawaterak_woocommerce_blocks_support()
{
    if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        require_once dirname(__FILE__) . '/includes/class-wc-gateway-fawaterak-blocks-support.php';
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                $payment_method_registry->register(new WC_Gateway_FAWATERAK_Blocks_Support);
            }
        );
    }
}


add_filter('woocommerce_thankyou_order_received_text', 'woocommerce_fawaterk_modify_thank_you_text', 99999999, 2);

function woocommerce_fawaterk_modify_thank_you_text($str, $order)
{
    $payment_data =  get_post_meta($order->get_id(), 'payment_data', true);
    // This will give us the payment method id 
    $payment_method_id =  $order->get_payment_method();

    $new_str = '';
    if ($payment_method_id === 'fawaterk_3') {

        $new_str .= '<br><br>' . '<Strong>Thank you. Payment recieved</Strong>' . '<br><br>';
        $new_str .= '<br>' . '<Strong style="font-size:20px;">Fawry Refrence Number: </Strong> <span style="font-size: 20px;background: green;padding: 0 5px;font-weight: bold;color: aliceblue;">' . $payment_data['fawryCode'] . '</span>';
        $new_str .= '<br>' . '<Strong style="font-size:20px;">Fawry Expiration Date: </Strong> <span style="font-size: 20px;background: red;padding: 0 5px;font-weight: bold;color: aliceblue;"> ' . $payment_data['expireDate'] . '</span>';
        $new_str .= '<br>' . '<Strong>برجاء التوجة إلى أقرب ماكينة فوري وإتمام عملية الدفع</strong>';
    } elseif ($payment_method_id === 'fawaterk_4') {
        $new_str .= '<br><br>' . '<Strong>Thank you. Payment recieved</Strong>' . '<br><br>';
        $new_str .= '<br>' . '<Strong>ستصلك رسالة على رقم المحفظة بكيفية إتمام عملية الدفع</strong>';
    } else {
        return $str;
    }


    return $new_str;
}


add_filter('woocommerce_available_payment_gateways', 'custom_available_payment_gateways');
function custom_available_payment_gateways($available_gateways)
{
    global $woocommerce;
    if (!isset($available_gateways['fawaterak'])) {
        return $available_gateways;
    }

    $response = wp_remote_get(' https://app.fawaterk.com/api/v2/getPaymentmethods', array('headers' => array('Authorization' => 'Bearer ' . get_option('woocommerce_fawaterak_settings')['private_key'], 'content-type' => 'application/json')));


    if (!is_wp_error($response)) {

        $raw_response =  json_decode($response['body'], true);
        include_once('methods/redirect_payments.php');
        include_once('methods/no_redirect_payments.php');

        unset($available_gateways['fawaterak']);
        if (isset($raw_response['data']) && !is_null($raw_response['data'])) {
            foreach ($raw_response['data'] as $payment_option) {
                if ($payment_option['redirect'] === 'true') {

                    $gateWay = new WC_Gateway_Fawaterk_Redirect_Payments($payment_option['paymentId'], WOOCOMMERCE_GATEWAY_FAWATERAK_URL . '/assets/images/' . $payment_option['paymentId'] . '.png', $payment_option['name_en']);
                    $available_gateways[$gateWay->id] = $gateWay;
                } else {

                    $gateWay = new WC_Gateway_Fawaterk_NO_Redirect_Payments($payment_option['paymentId'], WOOCOMMERCE_GATEWAY_FAWATERAK_URL . '/assets/images/' . $payment_option['paymentId'] . '.png', $payment_option['name_en']);
                    $available_gateways[$gateWay->id] = $gateWay;
                }
            }
        }
    }

    return $available_gateways;
}
