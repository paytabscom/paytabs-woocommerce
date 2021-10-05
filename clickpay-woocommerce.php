<?php

/**
 * @package Clickpay_PayPage
 */

/**
 * Plugin Name:   Clickpay - WooCommerce Payment Gateway
 * Plugin URI:    https://clickpay.com/
 * Description:   clickpay is a <strong>3rd party payment gateway</strong>. Ideal payment solutions for your internet business.
 * Version:       4.5.5
 * Author:        Clickpay
 * Author URI:    https://clickpay.com/
 * Revision Date: 05/October/2021
 */

if (!function_exists('add_action')) {
  exit;
}

//load plugin function when woocommerce loaded
add_action('plugins_loaded', 'woocommerce_clickpay_init', 0);

define('CLICKPAY_PAYPAGE_VERSION', '4.5.5');
define('CLICKPAY_PAYPAGE_DIR', plugin_dir_path(__FILE__));
define('CLICKPAY_PAYPAGE_ICONS_URL', plugins_url("icons/", __FILE__));
define('CLICKPAY_DEBUG_FILE', WP_CONTENT_DIR . "/debug_clickpay.log");
$CLICKPAY_PAYPAGE_METHODS = [
  'all'        => 'WC_Gateway_clickpay_All',
  'creditcard' => 'WC_Gateway_clickpay_Creditcard',
  'mada'       => 'WC_Gateway_clickpay_Mada',
  'stcpay'     => 'WC_Gateway_Clickpay_Stcpay',
  // 'stcpayqr' => 'WC_Gateway_clickpay_Stcpayqr',
  'applepay'   => 'WC_Gateway_clickpay_Applepay',
  'omannet'    => 'WC_Gateway_clickpay_Omannet',
  'sadad'      => 'WC_Gateway_clickpay_Sadad',
  'atfawry'    => 'WC_Gateway_clickpay_Atfawry',
  'knpay'      => 'WC_Gateway_clickpay_Knpay',
  'amex'       => 'WC_Gateway_clickpay_Amex',
  'valu'       => 'WC_Gateway_clickpay_Valu',
];

// require_once CLICKPAY_PAYPAGE_DIR . "includes/clickpay_api.php";
require_once CLICKPAY_PAYPAGE_DIR . "includes/clickpay_functions.php";


//paytab plugin function
function woocommerce_clickpay_init()
{
  if (!class_exists('WooCommerce') || !class_exists('WC_Payment_Gateway')) {
    add_action('admin_notices', 'woocommerce_clickpay_missing_wc_notice');
    return;
  }

  // PT
  require_once CLICKPAY_PAYPAGE_DIR . "includes/clickpay_core.php";
  require_once CLICKPAY_PAYPAGE_DIR . "includes/clickpay_payment_methods.php";
  require_once CLICKPAY_PAYPAGE_DIR . "includes/clickpay_gateways.php";
  require_once CLICKPAY_PAYPAGE_DIR . "includes/clickpay_payment_token.php";


  /**
   * Add the Gateway to WooCommerce
   **/
  function woocommerce_add_clickpay_gateway($gateways)
  {
    global $CLICKPAY_PAYPAGE_METHODS;
    foreach ($CLICKPAY_PAYPAGE_METHODS as $code => $clickpay_gateway) {
      $gateways[] = $clickpay_gateway;
    }

    return $gateways;
  }

  function clickpay_filter_gateways($load_gateways)
  {
    global $CLICKPAY_PAYPAGE_METHODS;
    if (is_admin()) return $load_gateways;

    $gateways = [];

    $currency = get_woocommerce_currency();


    foreach ($load_gateways as $gateway) {
      $add = true;

      foreach ($CLICKPAY_PAYPAGE_METHODS as $code => $clickpay_gateway) {
        if ($clickpay_gateway == $gateway) {
          $allowed = ClickpayHelper::paymentAllowed($code, $currency);
          if (!$allowed) {
            $add = false;
            break;
          }
        }
      }

      if ($add) {
        $gateways[] = $gateway;
      }
    }

    return $gateways;
  }


  /**
   * Add URL link to ClickPay plugin name pointing to WooCommerce payment tab
   */
  function clickpay_add_action_links($links)
  {
    $settings_url = admin_url('admin.php?page=wc-settings&tab=checkout');

    $mylinks = [
      "<a href='{$settings_url}'>Settings</a>",
    ];

    return array_merge($links, $mylinks);
  }


  add_filter('woocommerce_payment_gateways', 'woocommerce_add_clickpay_gateway');
  add_filter('woocommerce_payment_gateways', 'clickpay_filter_gateways', 10, 1);
  add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'clickpay_add_action_links');

  define('WooCommerce2', !woocommerce_clickpay_version_check('3.0'));
}
