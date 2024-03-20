<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Dummy Payments Blocks integration
 *
 * @since 1.0.3
 */
final class WC_Gateway_Paytabs_Blocks_Support extends AbstractPaymentMethodType
{
	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	public $gateways = [];
	public $id = "paytabs_blocks";

	protected $name = 'paytabs_blocks';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize()
	{
		$this->gateways = $this->get_gateways();
	}

	public function get_gateways()
	{
		$gateways = WC()->payment_gateways->payment_gateways();
		$enabled_gateways = [];

		foreach ($gateways as $gateway) {
			if (str_starts_with($gateway->id, "paytabs_") && $gateway->enabled == "yes") {
				$enabled_gateways[$gateway->id] = $gateway;
			}
		}
		return $enabled_gateways;
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active()
	{
		return true; // $this->gateway->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles()
	{
		$script_path       = 'assets/js/frontend/blocks.js';
		$script_asset_path = PAYTABS_PAYPAGE_DIR . 'assets/js/frontend/blocks.asset.php';
		$script_asset      = file_exists($script_asset_path)
			? require($script_asset_path)
			: array(
				'dependencies' => array(),
				'version'      => '1.2.0'
			);
		$script_url = PAYTABS_PAYPAGE_URL . $script_path;

		wp_register_script(
			$this->id,
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		if (function_exists('wp_set_script_translations')) {
			// wp_set_script_translations( 'wc-dummy-payments-blocks', 'woocommerce-gateway-dummy', WC_Dummy_Payments::plugin_abspath() . 'languages/' );
		}

		return [$this->id];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data()
	{
		$data = [];

		foreach ($this->gateways as $gateway) {
			$gateWayData = [
				'name' => $gateway->id,
				'title' => $gateway->title,
				'supports' => array_filter($gateway->supports, [$gateway, 'supports']),
				'icon' => $gateway->getIcon(),
				'description' => $gateway->description,
			];

			$key = "blocks";
			$data[$key][] = $gateWayData;
		}
		return $data;
	}
}
