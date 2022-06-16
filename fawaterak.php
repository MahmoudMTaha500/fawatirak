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
    include_once 'helpers/fawaterk-admin-page.php';

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

    $fawaterk_settings = get_option('fawaterk_plugin_options');
    $fawaterk_api_key = $fawaterk_settings['private_key'];
    $fawry_title = isset($fawaterk_settings['fawry_title']) && $fawaterk_settings['fawry_title'] !== '' ? $fawaterk_settings['fawry_title'] : false;
    $mobile_wallet_title = isset($fawaterk_settings['mobile_wallet_title']) && $fawaterk_settings['mobile_wallet_title'] !== '' ? $fawaterk_settings['mobile_wallet_title'] : false;

    $response = wp_remote_get(' https://app.fawaterk.com/api/v2/getPaymentmethods', array('headers' => array('Authorization' => 'Bearer ' . $fawaterk_api_key, 'content-type' => 'application/json')));

    if (!is_wp_error($response)) {

        $raw_response =  json_decode($response['body'], true);
        include_once('methods/redirect_payments.php');
        include_once('methods/no_redirect_payments.php');

        unset($available_gateways['fawaterak']);
        if (isset($raw_response['data']) && !is_null($raw_response['data'])) {
            foreach ($raw_response['data'] as $payment_option) {

                $get_payment_title = $payment_option['name_en'];
                if ($payment_option['name_en'] == 'fawry' && $fawry_title) {
                    $get_payment_title = $fawry_title;
                } elseif ($payment_option['name_en'] == 'mobile wallet' && $mobile_wallet_title) {
                    $get_payment_title = $mobile_wallet_title;
                }

                if ($payment_option['redirect'] === 'true') {
                    $gateWay = new WC_Gateway_Fawaterk_Redirect_Payments($payment_option['paymentId'], WOOCOMMERCE_GATEWAY_FAWATERAK_URL . '/assets/images/' . $payment_option['paymentId'] . '.png', $get_payment_title);
                    // $available_gateways[$gateWay->id] = $gateWay;
                    $available_gateways = [$gateWay->id => $gateWay] + $available_gateways;
                } else {
                    $gateWay = new WC_Gateway_Fawaterk_NO_Redirect_Payments($payment_option['paymentId'], WOOCOMMERCE_GATEWAY_FAWATERAK_URL . '/assets/images/' . $payment_option['paymentId'] . '.png', $get_payment_title);
                    // $available_gateways[$gateWay->id] = $gateWay;
                    $available_gateways = [$gateWay->id => $gateWay] + $available_gateways;
                }
            }
        }
    }

    return $available_gateways;
}



/**
 * Add custom field for mobile payment after custom information section
 */
// Add field to the checkout form
add_filter('woocommerce_checkout_fields', function ($fields) {

    $labels = [
        'title' => get_locale() === 'ar' ?  __('الرجاء إدخال رقم محفظتك', 'fawaterk') : __('Please enter your wallet phone number', 'fawaterk')
    ];
    $fields['billing']['fawaterk_wallet_number'] = array(
        'type' => 'text',
        'required'      => true,
        'label' => $labels['title']
    );
    return $fields;
});

// Save the field when the checkout is processed
add_action('woocommerce_checkout_update_order_meta', function ($order_id, $posted) {
    if (isset($posted['fawaterk_wallet_number'])) {
        update_post_meta($order_id, '_fawaterk_wallet_number', sanitize_text_field($posted['fawaterk_wallet_number']));
    }
}, 10, 2);

// Display the field in order details page
add_action('woocommerce_admin_order_data_after_order_details', function ($order) {
    $labels = [
        'title' => get_locale() === 'ar' ?  __('رقم المحفظة', 'fawaterk') : __('Wallet Number', 'fawaterk')
    ];
?>
    <div class="order_data_column">
        <h4><?php _e('Extra Details', 'woocommerce'); ?></h4>
        <?php
        echo '<p><strong>' . $labels['title'] . ':</strong>' . get_post_meta($order->id, '_fawaterk_wallet_number', true) . '</p>'; ?>
    </div>
<?php
});
add_action('woocommerce_checkout_create_order', function ($order, $data) {
    if (isset($_POST['fawaterk_wallet_number'])) {
        $order->update_meta_data('fawaterk_wallet_number', sanitize_text_field($_POST['fawaterk_wallet_number']));
    }
}, 10, 2);
