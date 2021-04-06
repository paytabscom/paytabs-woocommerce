<?php

/**
 * @package PayTabs_PayPage
 */

/**
 * Plugin Name:   PayTabs - WooCommerce Payment Gateway
 * Plugin URI:    https://paytabs.com/
 * Description:   PayTabs is a <strong>3rd party payment gateway</strong>. Ideal payment solutions for your internet business.
 * Version:       4.2.0
 * Author:        PayTabs
 * Author URI:    https://paytabs.com/
 * Revision Date: 29/March/2021
 */

if (!function_exists('add_action')) {
  exit;
}

//load plugin function when woocommerce loaded
add_action('plugins_loaded', 'woocommerce_paytabs_init', 0);

define('PAYTABS_PAYPAGE_VERSION', '4.2.0');
define('PAYTABS_PAYPAGE_DIR', plugin_dir_path(__FILE__));
define('PAYTABS_PAYPAGE_ICONS_URL', plugins_url("icons/", __FILE__));
define('PAYTABS_DEBUG_FILE', WP_CONTENT_DIR . "/debug_paytabs.log");
$PAYTABS_PAYPAGE_METHODS = [
  'all'        => 'WC_Gateway_Paytabs_All',
  'creditcard' => 'WC_Gateway_Paytabs_Creditcard',
  'mada'       => 'WC_Gateway_Paytabs_Mada',
  'stcpay'     => 'WC_Gateway_Paytabs_Stcpay',
  // 'stcpayqr' => 'WC_Gateway_Paytabs_Stcpayqr',
  'applepay'   => 'WC_Gateway_Paytabs_Applepay',
  'omannet'    => 'WC_Gateway_Paytabs_Omannet',
  'sadad'      => 'WC_Gateway_Paytabs_Sadad',
  'atfawry'    => 'WC_Gateway_Paytabs_Atfawry',
  'knpay'      => 'WC_Gateway_Paytabs_Knpay',
  'amex'       => 'WC_Gateway_Paytabs_Amex',
  'valu'       => 'WC_Gateway_Paytabs_Valu',
];

// require_once PAYTABS_PAYPAGE_DIR . "includes/paytabs_api.php";
require_once PAYTABS_PAYPAGE_DIR . "includes/paytabs_functions.php";


//paytab plugin function
function woocommerce_paytabs_init()
{
  if (!class_exists('WooCommerce') || !class_exists('WC_Payment_Gateway')) {
    add_action('admin_notices', 'woocommerce_paytabs_missing_wc_notice');
    return;
  }

  // PT
  require_once PAYTABS_PAYPAGE_DIR . "includes/paytabs_core.php";
  require_once PAYTABS_PAYPAGE_DIR . "includes/paytabs_payment_methods.php";
  require_once PAYTABS_PAYPAGE_DIR . "includes/paytabs_gateways.php";
  require_once PAYTABS_PAYPAGE_DIR . "includes/paytabs_payment_token.php";


  /**
   * Add the Gateway to WooCommerce
   **/
  function woocommerce_add_paytabs_gateway($gateways)
  {
    global $PAYTABS_PAYPAGE_METHODS;
    foreach ($PAYTABS_PAYPAGE_METHODS as $code => $paytabs_gateway) {
      $gateways[] = $paytabs_gateway;
    }

    return $gateways;
  }

  function paytabs_filter_gateways($load_gateways)
  {
    global $PAYTABS_PAYPAGE_METHODS;
    if (is_admin()) return $load_gateways;

    $gateways = [];

    $currency = get_woocommerce_currency();


    foreach ($load_gateways as $gateway) {
      $add = true;

      foreach ($PAYTABS_PAYPAGE_METHODS as $code => $paytabs_gateway) {
        if ($paytabs_gateway == $gateway) {
          $allowed = PaytabsHelper::paymentAllowed($code, $currency);
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
   * Add URL link to PayTabs plugin name pointing to WooCommerce payment tab
   */
  function paytabs_add_action_links($links)
  {
    $settings_url = admin_url('admin.php?page=wc-settings&tab=checkout');

    $mylinks = [
      "<a href='{$settings_url}'>Settings</a>",
    ];

    return array_merge($links, $mylinks);
  }


  add_filter('woocommerce_payment_gateways', 'woocommerce_add_paytabs_gateway');
  add_filter('woocommerce_payment_gateways', 'paytabs_filter_gateways', 10, 1);
  add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'paytabs_add_action_links');

  define('WooCommerce2', !woocommerce_paytabs_version_check('3.0'));
}
