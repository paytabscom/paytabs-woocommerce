<?php

/**
 * @package ClickPay_PayPage
 */

/**
 * Plugin Name:   ClickPay - WooCommerce Payment Gateway
 * Plugin URI:    https://ClickPay.com.sa/
 * Description:   ClickPay is a <strong>3rd party payment gateway</strong>. Ideal payment solutions for your internet business.

 * Version:       4.11.1
 * Requires PHP:  7.0
 * Author:        ClickPay
 * Author URI:    w.elsaeed@paytabs.com
 * Revision Date: 12/April/2022
 */

if (!function_exists('add_action')) {
  exit;
}



define('CLICKPAY_PAYPAGE_VERSION', '4.11.1');
define('CLICKPAY_PAYPAGE_DIR', plugin_dir_path(__FILE__));
define('CLICKPAY_PAYPAGE_ICONS_URL', plugins_url("icons/", __FILE__));
define('CLICKPAY_PAYPAGE_IMAGES_URL', plugins_url("images/", __FILE__));
define('CLICKPAY_DEBUG_FILE', WP_CONTENT_DIR . "/debug_clickpay.log");
define('CLICKPAY_PAYPAGE_METHODS', [
  'mada'       => 'WC_Gateway_clickpay_Mada',
  'all'        => 'WC_Gateway_clickpay_All',
  'creditcard' => 'WC_Gateway_clickpay_Creditcard',
  'stcpay'     => 'WC_Gateway_clickpay_Stcpay',
  // 'stcpayqr' => 'WC_Gateway_clickpay_Stcpayqr',
  'applepay'   => 'WC_Gateway_clickpay_Applepay',
  // 'sadad'      => 'WC_Gateway_clickpay_Sadad',
  'amex'       => 'WC_Gateway_clickpay_Amex',
  // 'samsungpay' => 'WC_Gateway_clickpay_Samsungpay',
]);


//load plugin function when woocommerce loaded
add_action('plugins_loaded', 'woocommerce_clickpay_init', 0);


function woocommerce_clickpay_init()
{
  require_once CLICKPAY_PAYPAGE_DIR . 'includes/clickpay_functions.php';

  if (!class_exists('WooCommerce') || !class_exists('WC_Payment_Gateway')) {
    add_action('admin_notices', 'woocommerce_clickpay_missing_wc_notice');
    return;
  }

  define('WooCommerce2', !woocommerce_clickpay_version_check('3.0'));

  // PT
  require_once CLICKPAY_PAYPAGE_DIR . 'includes/clickpay_core.php';
  require_once CLICKPAY_PAYPAGE_DIR . 'includes/clickpay_payment_methods.php';
  require_once CLICKPAY_PAYPAGE_DIR . 'includes/clickpay_gateways.php';
  require_once CLICKPAY_PAYPAGE_DIR . 'includes/clickpay_payment_token.php';


  /**
   * Add the Gateway to WooCommerce
   **/
  function woocommerce_add_clickpay_gateway($gateways)
  {
    $clickpay_gateways = array_values(CLICKPAY_PAYPAGE_METHODS);
    $gateways = array_merge($gateways, $clickpay_gateways);

    return $gateways;
  }

  function clickpay_filter_gateways($load_gateways)
  {
    if (is_admin()) return $load_gateways;

    $gateways = [];
    $currency = get_woocommerce_currency();

    foreach ($load_gateways as $gateway) {

      $code = array_search($gateway, CLICKPAY_PAYPAGE_METHODS);

      if ($code) {
        $allowed = clickpayHelper::paymentAllowed($code, $currency);
        if ($allowed) {
          $gateways[] = $gateway;
        }
      } else {
        // Not clickpay Gateway
        $gateways[] = $gateway;
      }
    }

    return $gateways;
  }


  /**
   * Add URL link to clickpay plugin name pointing to WooCommerce payment tab
   */
  function clickpay_add_action_links($links)
  {
    $settings_url = admin_url('admin.php?page=wc-settings&tab=checkout');

    $links[] = "<a href='{$settings_url}'>Settings</a>";

    return $links;
  }


  add_filter('woocommerce_payment_gateways', 'woocommerce_add_clickpay_gateway');
  add_filter('woocommerce_payment_gateways', 'clickpay_filter_gateways', 10, 1);
  add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'clickpay_add_action_links');
}
