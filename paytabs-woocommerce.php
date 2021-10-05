<?php

/**
 * @package PayTabs_PayPage
 */

/**
 * Plugin Name:   PayTabs - WooCommerce Payment Gateway
 * Plugin URI:    https://paytabs.com/
 * Description:   PayTabs is a <strong>3rd party payment gateway</strong>. Ideal payment solutions for your internet business.
 * Version:       4.6.0
 * Requires PHP:  7.0
 * Author:        PayTabs
 * Author URI:    w.kammoun@paytabs.com
 * Revision Date: 05/October/2021
 */

if (!function_exists('add_action')) {
  exit;
}


define('PAYTABS_PAYPAGE_VERSION', '4.6.0');
define('PAYTABS_PAYPAGE_DIR', plugin_dir_path(__FILE__));
define('PAYTABS_PAYPAGE_ICONS_URL', plugins_url("icons/", __FILE__));
define('PAYTABS_DEBUG_FILE', WP_CONTENT_DIR . "/debug_paytabs.log");
define('PAYTABS_PAYPAGE_METHODS', [
  'mada'       => 'WC_Gateway_Paytabs_Mada',
  'all'        => 'WC_Gateway_Paytabs_All',
  'creditcard' => 'WC_Gateway_Paytabs_Creditcard',
  'stcpay'     => 'WC_Gateway_Paytabs_Stcpay',
  // 'stcpayqr' => 'WC_Gateway_Paytabs_Stcpayqr',
  'applepay'   => 'WC_Gateway_Paytabs_Applepay',
  'omannet'    => 'WC_Gateway_Paytabs_Omannet',
  // 'sadad'      => 'WC_Gateway_Paytabs_Sadad',
  'fawry'      => 'WC_Gateway_Paytabs_Fawry',
  'knet'       => 'WC_Gateway_Paytabs_Knpay',
  'amex'       => 'WC_Gateway_Paytabs_Amex',
  'valu'       => 'WC_Gateway_Paytabs_Valu',
  'meeza'      => 'WC_Gateway_Paytabs_Meeza',
  'meezaqr'    => 'WC_Gateway_Paytabs_Meezaqr',
  'unionpay'   => 'WC_Gateway_Paytabs_Unionpay',
  // 'samsungpay' => 'WC_Gateway_Paytabs_Samsungpay',
]);


//load plugin function when woocommerce loaded
add_action('plugins_loaded', 'woocommerce_paytabs_init', 0);


function woocommerce_paytabs_init()
{
  require_once PAYTABS_PAYPAGE_DIR . 'includes/paytabs_functions.php';

  if (!class_exists('WooCommerce') || !class_exists('WC_Payment_Gateway')) {
    add_action('admin_notices', 'woocommerce_paytabs_missing_wc_notice');
    return;
  }

  define('WooCommerce2', !woocommerce_paytabs_version_check('3.0'));

  // PT
  require_once PAYTABS_PAYPAGE_DIR . 'includes/paytabs_core.php';
  require_once PAYTABS_PAYPAGE_DIR . 'includes/paytabs_payment_methods.php';
  require_once PAYTABS_PAYPAGE_DIR . 'includes/paytabs_gateways.php';
  require_once PAYTABS_PAYPAGE_DIR . 'includes/paytabs_payment_token.php';


  /**
   * Add the Gateway to WooCommerce
   **/
  function woocommerce_add_paytabs_gateway($gateways)
  {
    $paytabs_gateways = array_values(PAYTABS_PAYPAGE_METHODS);
    $gateways = array_merge($gateways, $paytabs_gateways);

    return $gateways;
  }

  function paytabs_filter_gateways($load_gateways)
  {
    if (is_admin()) return $load_gateways;

    $gateways = [];
    $currency = get_woocommerce_currency();

    foreach ($load_gateways as $gateway) {

      $code = array_search($gateway, PAYTABS_PAYPAGE_METHODS);

      if ($code) {
        $allowed = PaytabsHelper::paymentAllowed($code, $currency);
        if ($allowed) {
          $gateways[] = $gateway;
        }
      } else {
        // Not PayTabs Gateway
        $gateways[] = $gateway;
      }
    }

    return $gateways;
  }


  /**
   * Add URL link to PayTabs plugin name pointing to WooCommerce payment tab
   */
  function paytabs_add_action_links($links)
  {
    $settings_url = admin_url('admin.php?page=wc-settings&tab=checkout');

    $links[] = "<a href='{$settings_url}'>Settings</a>";

    return $links;
  }


  add_filter('woocommerce_payment_gateways', 'woocommerce_add_paytabs_gateway');
  add_filter('woocommerce_payment_gateways', 'paytabs_filter_gateways', 10, 1);
  add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'paytabs_add_action_links');
}
