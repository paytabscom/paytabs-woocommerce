<?php

/**
 * @package PayTabs_PayPage
 */

/**
 * Plugin Name:   PayTabs - WooCommerce Payment Gateway
 * Plugin URI:    https://paytabs.com/
 * Description:   PayTabs is a <strong>3rd party payment gateway</strong>. Ideal payment solutions for your internet business.

 * Version:       4.17.0
 * Requires PHP:  7.0
 * Author:        PayTabs
 * Author URI:    integration@paytabs.com
 */

if (!function_exists('add_action')) {
  exit;
}


define('PAYTABS_PAYPAGE_VERSION', '4.18.0');
define('PAYTABS_PAYPAGE_DIR', plugin_dir_path(__FILE__));
define('PAYTABS_PAYPAGE_ICONS_URL', plugins_url("icons/", __FILE__));
define('PAYTABS_PAYPAGE_IMAGES_URL', plugins_url("images/", __FILE__));
define('PAYTABS_DEBUG_FILE', WP_CONTENT_DIR . "/debug_paytabs.log");
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
]);


//load plugin function when woocommerce loaded
add_action('plugins_loaded', 'woocommerce_paytabs_init', 0);
add_action('woocommerce_single_product_summary', 'valu_widget', 30);

function valu_widget()
{
  $enabled_gateways = WC()->payment_gateways->get_available_payment_gateways();

  if (array_key_exists('paytabs_valu', $enabled_gateways)) {
    $valu_payment = $enabled_gateways['paytabs_valu'];
    if ($valu_payment->valu_widget_enable) {

      $product_price = get_product_price();
      if ($product_price) {
        if ($product_price >= $valu_payment->valu_widget_price_threshold) {
          $plan = call_valu_api($valu_payment, $product_price);

          if ($plan) {
            include('includes/_valu_widget.php');
          }
        }
      }
    }
  }
}


function get_product_price()
{
  // Get the current product's ID.
  $product_id = get_the_ID();
  $product = wc_get_product($product_id);
  $product_price = 0;

  if ($product) {
    // Get the product price.
    $product_price = $product->get_price();
  }

  return $product_price;
}

function call_valu_api($valu_payment, $product_price)
{
  $_paytabsApi = PaytabsApi::getInstance($valu_payment->paytabs_endpoint, $valu_payment->merchant_id, $valu_payment->merchant_key);
  $phone_number = $valu_payment->valu_widget_phone_number;

  $data = [
    'cart_amount' => $product_price,
    'cart_currency' => "EGP",
    'customer_details' => [
      "phone" => $phone_number
    ],
  ];

  PaytabsHelper::log("valU inqiry, {$product_price}", 1);
  $details = $_paytabsApi->inqiry_valu($data);

  if (!$details || !$details->success) {
    $_err_msg = json_encode($details);
    PaytabsHelper::log("valU Details error: [{$_err_msg}]", 3);
    return false;
  }

  $installments_count = 3;
  $valu_plan = getValUPlan($details, $installments_count);

  if (!$valu_plan) {
    return false;
  }

  try {
    $installment_amount = $valu_plan->emi;

    $calculated_installment = round($product_price / $installments_count, 2);
    $is_free_interest = $calculated_installment >= $installment_amount;

    $txt_free = $is_free_interest ? "interest-free" : "";

    $msg = "Pay {$installments_count} {$txt_free} payments of EGP $installment_amount.";

    return $msg;
  } catch (\Throwable $th) {
    PaytabsHelper::log("valU widget error: " . $th->getMessage(), 3);
  }

  return false;
}

function getValUPlan($details, $installments_count)
{
  try {
    $plansList = $details->valuResponse->productList[0]->tenureList;
    foreach ($plansList as $plan) {
      if ($plan->tenorMonth == $installments_count) {
        return $plan;
      }
    }
  } catch (\Throwable $th) {
    PaytabsHelper::log("valU Plan error: " . $th->getMessage(), 3);
  }

  $_log = json_encode($plansList);
  PaytabsHelper::log("valU Plan error: No Plan selected, [{$_log}]", 2);

  return false;
}

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
  add_filter('woocommerce_payment_methods_list_item', 'get_account_saved_payment_methods_list_item_paytabs', 10, 2);
  add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'paytabs_add_action_links');
}
