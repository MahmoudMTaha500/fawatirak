<?php
class WC_Gateway_Fawaterk_Redirect_Payments extends WC_Payment_Gateway
{
    public function __construct($id, $icon, $title)
    {
        $this->id = 'fawaterk_' . $id;

        $this->icon = $icon;
        $this->has_fields         = true;
        $this->method_title       = __($title, 'fawaterk');
        $this->method_description = __('Adds an option to pay via ' . $title . 'by Fawaterk.', 'fawaterk');
        $this->method_public_description = __('Pay with ' . $title);
        $this->supports           = ['products'];
        $this->payment_method_id   = $id;



        $this->init_form_fields();
        $this->init_settings();
        $this->title                = $this->get_option('title');
        $this->description          = $this->get_option('description');
        $this->api_key              = $this->get_option('api_key');


        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_fawaterk_webhook', array($this, 'webhook_callback'));
    }


    public function init_form_fields()
    {

        $this->form_fields = FawaterkAdminHelper::return_admin_options(
            $this->method_title,
            $this->method_public_description,
        );

    }

    public function process_payment($order_id)
    {
        global $woocommerce;
        (function_exists("wc_get_order")) ? $order = wc_get_order($order_id) : $order = new WC_Order($order_id);

        $config = [
            "api_key" => get_option('fawaterk_plugin_options')['private_key'],
            "payment_method_id" => $this->payment_method_id,
        ];
        $return_url = WC_Payment_Gateway::get_return_url( $order );
        // echo "<pre>ءءء";        print_r($return_url); echo "</pre>";  


        $process = new FawaterkPayHelper($order, $config, $return_url , $order);

        if (!$process->isValid()) {
            throw new Exception("Please solve all the errors below.");
        }

        $invoice_key = $process->requestOrder();

        if (!$invoice_key) {
            throw new Exception("Failed to register order.");
        }
        $payment_data = $process->getPaymentData();
        echo "<pre>";        print_r($payment_data); echo "</pre>";  die;

        if (!$payment_data) {
            throw new Exception("Failed to Get Payment Data.");
        }
    //   die;
    // echo "<pre> order arrrrrrrrrrrrrrrryyyyyyyyy";        print_r($order); echo "</pre>";  die;

        $process->processOrder();
            
        
        return ['result' => 'success', 'redirect' => $payment_data['redirectTo']];
    }

    public function payment_fields()
    {
        echo "<p>$this->description</p>";
    }

    public function webhook_callback()
    {

        FawaterkAdminHelper::webhook_callback($this->api_key);
    }
}
