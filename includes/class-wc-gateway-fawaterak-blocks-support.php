<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Fawaterak payment method integration
 */
final class WC_Gateway_FAWATERAK_Blocks_Support extends AbstractPaymentMethodType {
	/**
	 * Payment method name defined by payment methods extending this class.
	 *
	 * @var string
	 */
	protected $name = 'fawaterak';


	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option('fawaterk_plugin_options', [] );

	}


	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return filter_var( $this->get_setting( 'enabled', false ), FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$asset_path = WOOCOMMERCE_GATEWAY_FAWATERAK_PATH . '/build/index.asset.php';
		$version      = WOOCOMMERCE_GATEWAY_FAWATERAK_VERSION;
		$dependencies = [];
		if ( file_exists( $asset_path ) ) {
			$asset        = require $asset_path;
			$version      = is_array( $asset ) && isset( $asset['version'] )
				? $asset['version']
				: $version;
			$dependencies = is_array( $asset ) && isset( $asset['dependencies'] )
				? $asset['dependencies']
				: $dependencies;
		}
		wp_register_script(
			'wc-fawaterak-blocks-integration',
			WOOCOMMERCE_GATEWAY_FAWATERAK_URL . '/build/index.js',
			$dependencies,
			$version,
			true
		);
		wp_localize_script('wc-fawaterak-blocks-integration', 'scriptVars', array('imageUrl'=> WOOCOMMERCE_GATEWAY_FAWATERAK_URL .'/assets/images/paywf.png'));

		return [ 'wc-fawaterak-blocks-integration' ];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return [
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'supports'    => $this->get_supported_features(),
		];
		
	}

	/**
	 * Returns an array of supported features.
	 *
	 * @return string[]
	 */
	public function get_supported_features() {
		$payment_gateways = WC()->payment_gateways->payment_gateways();
		return $payment_gateways['fawaterak']->supports;
	
	}
}
