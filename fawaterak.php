<?php
/*
 * Plugin Name: Fawaterak
 * Plugin URI:  https://www.fawaterk.com/
 * Description: Fawaterak payment gateway.
 * Author: Fawaterak
 * Author URI: https://www.fawaterk.com/
 * Version: 1.2.11
 *
*/

if (!defined('ABSPATH')) {
    exit;
}

define('FAWATERK_ENABLE_STAGING', false);
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
            // print_r($payment_data);
            // print_r($payment_method_id);die;
    $new_str = '';
    if ($payment_method_id === 'fawaterk_3') {
        // Fawry payment message
        $new_str .= '<br><br>' . '<Strong>Thank you. Payment is pending, Please go to the nearest Fawry machine and complete the payment process.</Strong>' . '<br><br>';
        $new_str .= '<br>' . '<Strong style="font-size:20px;">Fawry Refrence Number: </Strong> <span style="font-size: 20px;background: green;padding: 0 5px;font-weight: bold;color: aliceblue;">' . $payment_data['fawryCode'] . '</span>';
        $new_str .= '<br>' . '<Strong style="font-size:20px;">Fawry Expiration Date: </Strong> <span style="font-size: 20px;background: red;padding: 0 5px;font-weight: bold;color: aliceblue;"> ' . $payment_data['expireDate'] . '</span>';
        $new_str .= '<br>' . '<Strong>برجاء التوجة إلى أقرب ماكينة فوري وإتمام عملية الدفع  من خلال رقم الخدمه 788</strong>';
    } elseif ($payment_method_id === 'fawaterk_4') {
        $new_str .= '<br><br>' . '<Strong>Thank you. Payment is pending, You will receive a message on the wallet number with how to complete the payment process.</Strong>' . '<br><br>';
        $new_str .= '<br>' . '<Strong>ستصلك رسالة على رقم المحفظة بكيفية إتمام عملية الدفع</strong>';
    } elseif ($payment_method_id === 'fawaterk_12') {
        // Aman payment message
        $new_str .= '<br><br>' . '<Strong>Thank you. Payment is pending, Please go to the nearest Aman machine and complete the payment process.</Strong>' . '<br><br>';
        $new_str .= '<br>' . '<Strong style="font-size:20px;">Aman Refrence Number: </Strong> <span style="font-size: 20px;background: green;padding: 0 5px;font-weight: bold;color: aliceblue;">' . $payment_data['amanCode'] . '</span>';
        $new_str .= '<br>' . '<Strong>برجاء التوجة إلى أقرب ماكينة امان وإتمام عملية الدفع</strong>';

    } elseif ($payment_method_id === 'fawaterk_4_mobile_wallet') {
        // Aman payment message
        
    
        $new_str .= "<input type='hidden'   id='qrdata' vaule='$payment_data[meezaQrCode]'  ";
        $new_str .= " <span id='qrcode' > </span>";
        $new_str .= '<br>' . '<Strong>   ستصلك رساله علي هاتفك بخطوات اتمام عمليه الدفع</strong>';
        // wp_add_inline_script( 'qrcode-js', " 
       
        // <script>
        // var qr_data = document.getElementById('qrdata');
        // var qr_code =   new QRCode(document.getElementById('qrcode'));
        // var data = qr_data.value;
        // alert(12)
        // qr_code.makeCode(data);
        //  </script>
        // ", 'after' );
       
       
        
        
       
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

    $payments_gateway_url = FAWATERK_ENABLE_STAGING ? 'https://fawaterkstage.com/api/v2/getPaymentmethods' : 'https://app.fawaterk.com/api/v2/getPaymentmethods';
    $response = wp_remote_get($payments_gateway_url, array('headers' => array('Authorization' => 'Bearer ' . $fawaterk_api_key, 'content-type' => 'application/json')));

    if (!is_wp_error($response)) {


        $raw_response =  json_decode($response['body'], true);
// echo  WOOCOMMERCE_GATEWAY_FAWATERAK_URL;
        // echo "<pre>";        print_r($raw_response); echo "</pre>";  die;

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
                // echo "<pre>";        print_r($payment_method_registry); echo "</pre>";  die;

                if ($payment_option['redirect'] === 'true') {
                    $gateWay = new WC_Gateway_Fawaterk_Redirect_Payments($payment_option['paymentId'], $payment_option['logo'], $get_payment_title);
                    $available_gateways[$gateWay->id] = $gateWay;
                // echo "<pre>";        print_r($gateWay); echo "</pre>";  die;


                    // Change gateway HTML id
                    if ($payment_option['name_en'] == 'mobile wallet') {
                        $gateWay->id = $gateWay->id . '_mobile_wallet';
                    }
                    $available_gateways = [$gateWay->id => $gateWay] + $available_gateways;
                } else {
                    $gateWay = new WC_Gateway_Fawaterk_NO_Redirect_Payments($payment_option['paymentId'], $payment_option['logo'], $get_payment_title);
                    // $available_gateways[$gateWay->id] = $gateWay;

                    // Change gateway HTML id
                    if ($payment_option['name_en'] == 'mobile wallet') {
                        $gateWay->id = $gateWay->id . '_mobile_wallet';
                    }
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
        'required'      => false,
        'label' => false
    );
    return $fields;
});

// Save the field when the checkout is processed
add_action('woocommerce_checkout_update_order_meta', function ($order_id, $posted) {
    if (isset($posted['fawaterk_wallet_number'])) {
        update_post_meta($order_id, '_fawaterk_wallet_number', sanitize_text_field($posted['fawaterk_wallet_number']));
    }
}, 10, 2);

// Change custom wallet field HTML output
add_filter('woocommerce_form_field_text', function ($field, $key, $args, $value) {
    if (!empty($field) && $key === 'fawaterk_wallet_number') {

        $labels = [
            'title' => get_locale() === 'ar' ?  __('الرجاء إدخال رقم محفظتك', 'fawaterk') : __('Please enter your wallet phone number', 'fawaterk')
        ];
        $submit_label = get_locale() === 'ar' ? esc_html__('ادخال', 'fawaterk') : esc_html__('Submit', 'fawaterk');
        $close_button = '<span class="close-field"><svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px"
width="30" height="30"
viewBox="0 0 30 30"
style=" fill:#000000;">    <path d="M 7 4 C 6.744125 4 6.4879687 4.0974687 6.2929688 4.2929688 L 4.2929688 6.2929688 C 3.9019687 6.6839688 3.9019687 7.3170313 4.2929688 7.7070312 L 11.585938 15 L 4.2929688 22.292969 C 3.9019687 22.683969 3.9019687 23.317031 4.2929688 23.707031 L 6.2929688 25.707031 C 6.6839688 26.098031 7.3170313 26.098031 7.7070312 25.707031 L 15 18.414062 L 22.292969 25.707031 C 22.682969 26.098031 23.317031 26.098031 23.707031 25.707031 L 25.707031 23.707031 C 26.098031 23.316031 26.098031 22.682969 25.707031 22.292969 L 18.414062 15 L 25.707031 7.7070312 C 26.098031 7.3170312 26.098031 6.6829688 25.707031 6.2929688 L 23.707031 4.2929688 C 23.316031 3.9019687 22.682969 3.9019687 22.292969 4.2929688 L 15 11.585938 L 7.7070312 4.2929688 C 7.5115312 4.0974687 7.255875 4 7 4 z"></path></svg></span>';
        // $submit_button = '<a href="#" class="submit-field">' . $submit_label . '</a>';
        $submit_button = '<button   type="button"  id="wallet_btn" oncilck="alert_wallet()"   class=" submit-field btn btn-primary " >' . $submit_label . '</button>';
 

        if ($args['required']) {
            $args['class'][] = 'validate-required';
            $required = ' <abbr class="required" title="' . esc_attr__('required', 'woocommerce') . '">*</abbr>';
        } else {
            $required = '';
        }

        $args['maxlength'] = ($args['maxlength']) ? 'maxlength="' . absint($args['maxlength']) . '"' : '';

        $args['autocomplete'] = ($args['autocomplete']) ? 'autocomplete="' . esc_attr($args['autocomplete']) . '"' : '';

        if (
            is_string($args['label_class'])
        ) {
            $args['label_class'] = array($args['label_class']);
        }

        if (
            is_null($value)
        ) {
            $value = $args['default'];
        }

        // Custom attribute handling
        $custom_attributes = array();

        // Custom attribute handling
        $custom_attributes = array();

        if (
            !empty($args['custom_attributes']) && is_array($args['custom_attributes'])
        ) {
            foreach ($args['custom_attributes'] as $attribute => $attribute_value) {
                $custom_attributes[] = esc_attr($attribute) . '="' . esc_attr($attribute_value) . '"';
            }
        }

        $field = '';
        $label_id = $args['id'];
        $field_container = '<p class="form-row %1$s" id="%2$s">%3$s</p>';

        $field .= '<div class="mobile-wallet-container hidden fawaterk-mobile-waller-container"><div class="mobile-wallet-field-container"><h3>' . $labels['title'] . '</h3><input type="' . esc_attr($args['type']) . '" class="input-text ' . esc_attr(implode(' ', $args['input_class'])) . '" name="' . esc_attr($key) . '" id="' . esc_attr($args['id']) . '" placeholder="' . esc_attr($args['placeholder']) . '" ' . $args['maxlength'] . ' ' . $args['autocomplete'] . ' value="' . esc_attr($value) . '" ' . implode(' ', $custom_attributes) . ' />  '.  $submit_button  .  ' </div> ' . $close_button . ' </div>';

        if (
            !empty($field)
        ) {
            $field_html = '';

            $field_html .= $field;

            if ($args['description']) {
                $field_html .= '<span class="description">' . esc_html($args['description']) . '</span>';
            }

            if ($args['label'] && 'checkbox' != $args['type']) {
                $field_html .= '<label for="' . esc_attr($label_id) . '" class="' . esc_attr(implode(' ', $args['label_class'])) . '">' . $args['label'] . $required . '</label>';
            }

            $container_class = 'form-row ' . esc_attr(implode(' ', $args['class']));
            $container_id = esc_attr($args['id']) . '_field';

            $after = !empty($args['clear']) ? '<div class="clear"></div>' : '';

            $field = sprintf($field_container, $container_class, $container_id, $field_html) . $after;
        }
    }
    return $field;
}, 10, 4);

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


/**
 * add custom css
 */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('fawaterk-frontend', untrailingslashit(plugin_dir_url(__FILE__)) . '/assets/main.css');
    wp_enqueue_script('fawaterk-frontend', untrailingslashit(plugin_dir_url(__FILE__)) . '/assets/main.js');

});

// wp_register_script( 'qrcode-js', '/assets/qrcode.min.js' , '', '', true );
// wp_enqueue_script( 'qrcode-js' );

/**
 * Custom thank you redirect
 */
// add_action('woocommerce_thankyou', function ($order_id) {
    // $fawaterk_settings = get_option('fawaterk_plugin_options');
    // $payment_pending_page = $fawaterk_settings['payment_pending_page'];
    // $order = wc_get_order($order_id);
    // if ($order->has_status('failed') || $order->has_status('pending')) {
    //     wp_safe_redirect($payment_pending_page);
    //     exit;
    // }
// });



// <!-- <script>
//     function alert_wallet(){
//         alert('please contine the prosses to pay your order ')
//     }
// </script> -->

