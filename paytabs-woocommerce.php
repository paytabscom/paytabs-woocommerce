<?php

/**
 * @package PayTabs_PayPage
 */

/**
 * Plugin Name:   PayTabs - WooCommerce Payment Gateway
 * Plugin URI:    https://paytabs.com/
 * Description:   PayTabs is a <strong>3rd party payment gateway</strong>. Ideal payment solutions for your internet business.

 * Version:       4.17.3
 * Requires PHP:  7.0
 * Author:        PayTabs
 * Author URI:    integration@paytabs.com
 */

if (!function_exists('add_action')) {
  exit;
}



define('PAYTABS_PAYPAGE_VERSION', '4.17.3');
define('PAYTABS_PAYPAGE_DIR', plugin_dir_path(__FILE__));
define('PAYTABS_PAYPAGE_ICONS_URL', plugins_url("icons/", __FILE__));
define('PAYTABS_PAYPAGE_IMAGES_URL', plugins_url("images/", __FILE__));
define('PAYTABS_DEBUG_FILE', WP_CONTENT_DIR . "/debug_paytabs.log");
define('PAYTABS_HTACCESS_FILE', WP_CONTENT_DIR . "/.htaccess");
define('PAYTABS_DEBUG_FILE_URL', get_bloginfo('url') . "/wp-content/debug_paytabs.log");


define('PAYTABS_PAYPAGE_METHODS', [
  'mada'       => 'WC_Gateway_Paytabs_Mada',
  'all'        => 'WC_Gateway_Paytabs_All',
  'creditcard' => 'WC_Gateway_Paytabs_Creditcard',
  'stcpay'     => 'WC_Gateway_Paytabs_Stcpay',
  // 'stcpayqr' => 'WC_Gateway_Paytabs_Stcpayqr',
  'applepay'   => 'WC_Gateway_Paytabs_Applepay',
  'omannet'    => 'WC_Gateway_Paytabs_Omannet',
  'sadad'      => 'WC_Gateway_Paytabs_Sadad',
  'fawry'      => 'WC_Gateway_Paytabs_Fawry',
  'knet'       => 'WC_Gateway_Paytabs_Knpay',
  'amex'       => 'WC_Gateway_Paytabs_Amex',
  'valu'       => 'WC_Gateway_Paytabs_Valu',
  'meeza'      => 'WC_Gateway_Paytabs_Meeza',
  'meezaqr'    => 'WC_Gateway_Paytabs_Meezaqr',
  'unionpay'   => 'WC_Gateway_Paytabs_Unionpay',
  'aman'       => 'WC_Gateway_Paytabs_Aman',
  'urpay'      => 'WC_Gateway_Paytabs_Urpay',
  'paypal'     => 'WC_Gateway_Paytabs_Paypal',
  'installment' => 'WC_Gateway_Paytabs_Installment',
  'touchpoints' => 'WC_Gateway_Paytabs_Touchpoints',
  // 'samsungpay' => 'WC_Gateway_Paytabs_Samsungpay',
  'forsa'       => 'WC_Gateway_Paytabs_Forsa',
  'tabby'       => 'WC_Gateway_Paytabs_Tabby',
]);

require_once PAYTABS_PAYPAGE_DIR . 'includes/paytabs_core.php';
require_once PAYTABS_PAYPAGE_DIR . 'includes/paytabs_functions.php';

// Plugin activated
register_activation_hook(__FILE__, 'woocommerce_paytabs_activated');

// Load plugin function when woocommerce loaded
add_action('plugins_loaded', 'woocommerce_paytabs_init', 0);

function woocommerce_paytabs_init()
{

  if (!class_exists('WooCommerce') || !class_exists('WC_Payment_Gateway')) {
    add_action('admin_notices', 'woocommerce_paytabs_missing_wc_notice');
    return;
  }

  define('WooCommerce2', !woocommerce_paytabs_version_check('3.0'));

  // PT
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
  add_filter('woocommerce_payment_methods_list_item', 'get_account_saved_payment_methods_list_item_paytabs', 10, 2);
  add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'paytabs_add_action_links');
}


function woocommerce_paytabs_activated()
{
  PaytabsHelper::log("Activate hook.", 1);
  woocommerce_paytabs_check_log_permission();
}


function woocommerce_paytabs_check_log_permission()
{
  //print message for the merchant to make sure allow the webserver setting.
  add_action('admin_notices', 'woocommerce_paytabs_htaccess_notice');

  $permission = "<Files " . PAYTABS_DEBUG_FILE_NAME . ">
      Order Allow,Deny
      Deny from all
  </Files>";
  $htaccess_file_content = file_get_contents(PAYTABS_HTACCESS_FILE);

  // prevent debug file from opening inside the browser
  if (!file_exists(PAYTABS_HTACCESS_FILE)) {
    $myhtaccessfile = fopen(PAYTABS_HTACCESS_FILE, "w");
    fwrite($myhtaccessfile, $permission);
    fclose($myhtaccessfile);

    PaytabsHelper::log("Debug file secured.", 1);
  } else {
    $url = PAYTABS_DEBUG_FILE_URL;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, true);    // we want headers
    curl_setopt($ch, CURLOPT_NOBODY, true);    // we don't need body
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $output = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode === 403) {
      PaytabsHelper::log("Debug file already secured.", 1);
    } elseif (strpos($htaccess_file_content, PAYTABS_DEBUG_FILE_NAME) !== false) {
      PaytabsHelper::log("Paytabs needs to allow override all into your webserver to enable the proper functioning of the .htaccess file.", 1);
    } else {
      $htaccessFile = PAYTABS_HTACCESS_FILE;
      file_put_contents($htaccessFile, $permission, FILE_APPEND);

      PaytabsHelper::log("Debug file appended to htaccess.", 1);
    }
  }
}
