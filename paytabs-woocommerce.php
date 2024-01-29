<?php

/**
 * @package PayTabs_PayPage
 */

/**
 * Plugin Name:   PayTabs - WooCommerce Payment Gateway
 * Plugin URI:    https://paytabs.com/
 * Description:   PayTabs is a <strong>3rd party payment gateway</strong>. Ideal payment solutions for your internet business.

 * Version:       4.22.0
 * Requires PHP:  7.0
 * Author:        PayTabs
 * Author URI:    integration@paytabs.com
 */

if (!function_exists('add_action')) {
  exit;
}

if( !session_id() ) {
  session_start();
}

define('PAYTABS_PAYPAGE_VERSION', '4.22.0');
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
  'souhoola'    => 'WC_Gateway_Paytabs_Souhoola',
  'amaninstallments' => 'WC_Gateway_Paytabs_AmanInstallments',
]);

require_once PAYTABS_PAYPAGE_DIR . 'includes/paytabs_core.php';
require_once PAYTABS_PAYPAGE_DIR . 'includes/paytabs_functions.php';

// Plugin activated
register_activation_hook(__FILE__, 'woocommerce_paytabs_activated');

// Load plugin function when woocommerce loaded
add_action('plugins_loaded', 'woocommerce_paytabs_init', 0);
add_action('admin_enqueue_scripts', 'enqueue_paytabs_script');
function enqueue_paytabs_script() {
  wp_enqueue_script('paytabs-custom-script', plugin_dir_url(__FILE__) . 'includes/scripts/paytabs-custom-script.js', array('jquery'), '1.0', true);
}

add_action( 'woocommerce_admin_order_data_after_payment_info', 'add_paytabs_capture_button', 10, 1 );
function add_paytabs_capture_button( $order ) {
  $disabled = false;
  $order_id = $order->get_id();
  $transaction_type = get_post_meta($order_id, WC_Gateway_Paytabs::PT_TRAN_TYPE);
  if (!in_array(PaytabsEnum::TRAN_TYPE_AUTH, $transaction_type)){
    $disabled = true;
  }

  echo '<div>
        <button id="paytabs_capture_btn" data-order-id="'.$order_id.'" data-payment-method="'.$order->get_payment_method().'" data-nonce="'. wp_create_nonce("paytabs_capture_nonce") .'" type="button" class="button button-primary" '; echo $disabled ? "disabled" : ""; echo '>
          Paytabs Capture
        </button>
    </div>';
    ?>
  <?php
}

//

add_action('wp_ajax_paytabs_capture', 'wc_pt_capture');
function wc_pt_capture(){
  if (isset($_POST['payment_method'])) {
    $payment_method = $_POST['payment_method'];
  }

  if (isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];
  }
  
  $order = wc_get_order( $order_id );
  $className = "WC_Gateway_".$payment_method;
  $payment_method_obj = new $className();
  
  $capture_success = $payment_method_obj->process_capture($order_id);
  if ($capture_success) {
    $_SESSION['paytabs_capture_type'] = 'success';
    $_SESSION['paytabs_capture_message'] = 'Paytabs capture succeeded';
  } else {
    $_SESSION['paytabs_capture_type'] = 'error';
    $_SESSION['paytabs_capture_message'] = 'Paytabs capture failed';
  }
  wp_die();
}

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
  require_once PAYTABS_PAYPAGE_DIR . 'includes/widgets/valu.php';


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

  add_action('woocommerce_single_product_summary', 'valu_widget', 21);

  function valu_widget()
  {
    $enabled_gateways = WC()->payment_gateways->get_available_payment_gateways();

    if (array_key_exists('paytabs_valu', $enabled_gateways)) {
      $valu_payment = $enabled_gateways['paytabs_valu'];

      $valu_widget = new ValuWidget();
      $valu_widget->init($valu_payment);
    }
  }
}


function woocommerce_paytabs_activated()
{
  PaytabsHelper::log("Activate hook.", 1);
  woocommerce_paytabs_check_log_permission();
}
