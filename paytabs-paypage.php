<?php

/**
 * @package PayTabs - PayPage
 */

/**
 * Plugin Name: PayTabs - WooCommerce Payment Gateway
 * Plugin URI: https://paytabs.com/
 * Description: PayTabs is a <strong>3rd party payment gateway</strong>. Ideal payment solutions for your internet business.
 * Version: 3.1.0
 * Author: PayTabs
 * Author URI: https://paytabs.com/
 * Revision Date : 02/April/2020
 */

if (!function_exists('add_action')) {
  exit;
}

//load plugin function when woocommerce loaded
add_action('plugins_loaded', 'woocommerce_paytabs_init', 0);

define('PAYTABS_PAYPAGE_DIR', plugin_dir_path(__FILE__));
define('PAYTABS_PAYPAGE_METHODS', [
  'creditcard' => 'WC_Gateway_Paytabs_Creditcard',
  'mada' => 'WC_Gateway_Paytabs_Mada',
  'stcpay' => 'WC_Gateway_Paytabs_Stcpay',
  // 'stcpayqr' => 'WC_Gateway_Paytabs_Stcpayqr',
  'applepay' => 'WC_Gateway_Paytabs_Applepay',
  'omannet' => 'WC_Gateway_Paytabs_Omannet',
  'sadad' => 'WC_Gateway_Paytabs_Sadad',
  'atfawry' => 'WC_Gateway_Paytabs_Atfawry',
  'knpay' => 'WC_Gateway_Paytabs_Knpay',
  'amex' => 'WC_Gateway_Paytabs_Amex',
]);

require_once PAYTABS_PAYPAGE_DIR . "includes/paytabs_api.php";


//paytab plugin function
function woocommerce_paytabs_init()
{
  if (!class_exists('WC_Payment_Gateway')) return;

  class WC_Gateway_Paytabs extends WC_Payment_Gateway
  {
    protected $_code = '';
    protected $_title = '';
    protected $_description = '';

    public function __construct()
    {
      $this->id = "paytabs_{$this->_code}"; // payment gateway plugin ID
      $this->icon = $this->getIcon(); // URL of the icon that will be displayed on checkout page near your gateway name
      $this->has_fields = false; // in case you need a custom credit card form
      $this->method_title = $this->_title;
      $this->method_description = $this->_description; // will be displayed on the options page

      // gateways can support subscriptions, refunds, saved payment methods,
      // but in this tutorial we begin with simple payments
      $this->supports = array(
        'products'
      );

      // Method with all the options fields
      $this->init_form_fields();

      // Load the settings.
      $this->init_settings();
      $this->title = $this->get_option('title');
      $this->description = $this->get_option('description');
      $this->enabled = $this->get_option('enabled');

      $this->merchant_email = $this->get_option('merchant_email');
      $this->secret_key = $this->get_option('secret_key');

      // This action hook saves the settings
      add_action("woocommerce_update_options_payment_gateways_{$this->id}", array($this, 'process_admin_options'));


      // We need custom JavaScript to obtain a token
      // add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

      // You can also register a webhook here
      // add_action('woocommerce_api_paytabs_callback', array($this, 'callback'));

      if (isset($_REQUEST['payment_reference'])) {
        $payment_reference = $_REQUEST['payment_reference'];
        $this->callback($payment_reference);
      }
    }

    /**
     * Returns the icon URL for this payment method
     * "icons" folder must contains .png file named like the "code" param of the payment method
     * example: stcpay.png, applepay.png ...
     * @return string
     */
    private function getIcon()
    {
      $iconPath = PAYTABS_PAYPAGE_DIR . "icons/{$this->_code}.png";
      $icon = '';
      if (file_exists($iconPath)) {
        $icon = plugins_url("icons/{$this->_code}.png", __FILE__);
      }

      return $icon;
    }

    /**
     * Plugin options
     */
    public function init_form_fields()
    {
      $this->form_fields = array(
        'enabled' => array(
          'title'       => __('Enable/Disable', 'PayTabs'),
          'label'       => __('Enable Payment Gateway.', 'PayTabs'),
          'type'        => 'checkbox',
          'description' => '',
          'default'     => 'no'
        ),
        'title' => array(
          'title'       => __('Title', 'PayTabs'),
          'type'        => 'text',
          'description' => __('This controls the title which the user sees during checkout.', 'PayTabs'),
          'default'     => $this->_title,
          'desc_tip'    => true,
        ),
        'description' => array(
          'title'       => __('Description', 'PayTabs'),
          'type'        => 'textarea',
          'description' => __('This controls the description which the user sees during checkout.', 'PayTabs'),
          'default'     => __('Pay securely through PayTabs Secure Servers.', 'PayTabs'),
        ),
        'merchant_email' => array(
          'title'       => __('Merchant e-Mail', 'PayTabs'),
          'type'        => 'text',
          'description' => __('Please enter the E-Mail of your PayTabs Merchant account.', 'PayTabs'),
          'default'     => '',
          'required'    => true
        ),
        'secret_key' => array(
          'title'       => __('Secret Key', 'PayTabs'),
          'type'        => 'text',
          'description' => __('Please enter your PayTabs Secret Key. You can find the secret key on your Merchant’s Portal', 'PayTabs'),
          'default'     => '',
          'required'    => true
        )
      );
    }

    /**
     *  There are no payment fields for paytabs, but we want to show the description if set.
     **/
    function payment_fields()
    {
      if ($this->description) echo wpautop(wptexturize($this->description));
    }


    /**
     * We're processing the payments here
     **/
    public function process_payment($order_id)
    {
      // we need it to get any order detailes
      $order = wc_get_order($order_id);

      $values = $this->prepareOrder($order);

      $paytabsApi = new PaytabsApi($this->merchant_email, $this->secret_key);
      $paypage = $paytabsApi->create_pay_page($values);


      /*
      * Your API interaction could be built with wp_remote_post()
      */
      // $response = wp_remote_post('{payment processor endpoint}', $args);

      if ($paypage && $paypage->response_code == 4012) {
        $payment_url = $paypage->payment_url;

        return array(
          'result'    => 'success',
          'redirect'  => $payment_url,
        );
      } else {
        $errorMessage = $paypage->details ?? $paypage->result;
        wc_add_notice($errorMessage, 'error');
        return;
      }
    }

    /**
     * In case you need a webhook, like PayPal IPN etc
     */
    public function callback($payment_reference)
    {
      if (!$payment_reference) return false;

      $paytabsApi = new PaytabsApi($this->merchant_email, $this->secret_key);
      $result = $paytabsApi->verify_payment($payment_reference);

      $response = ($result && isset($result->response_code));
      if (!$response) {
        return false;
      }

      $success = $result->response_code == 100;
      $message = $result->result;

      $orderId = $result->reference_no;
      $order = wc_get_order($orderId);

      if (!$order) return;

      if ($success) {
        $this->orderSuccess($order, $message);

        return true;
      } else {
        $this->orderFailed($order, $message);

        return false;
      }
    }

    /**
     * Payment successed => Order status change to success
     */
    private function orderSuccess($order, $message)
    {
      global $woocommerce;

      $order->payment_complete();
      // $order->reduce_order_stock();

      $woocommerce->cart->empty_cart();

      $order->add_order_note('Hey, your order is paid! Thank you!', true);
      wc_add_notice(__('Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.', 'woocommerce'), 'success');

      wp_redirect($this->get_return_url($order));
    }

    /**
     * Payment failed => Order status change to failed
     */
    private function orderFailed($order, $message)
    {
      wc_add_notice($message, 'error');

      $order->update_status('failed', __('Payment Cancelled', 'error'));

      wp_redirect($order->get_cancel_order_url());
    }


    //

    /**
     * Extract required parameters from the Order, to Pass to create_page API
     * -Client information
     * -Shipping address
     * -Products
     * @return Array of values to pass to create_paypage API
     */
    private function prepareOrder($order)
    {
      global $woocommerce;

      // $order->add_order_note();

      $total = $order->get_total();
      $discount = $order->get_total_discount();
      $shipping = $order->get_total_shipping();

      $amount = $total + $discount;
      $other_charges = $shipping;
      // $totals = $order->get_order_item_totals();

      $currency = $order->get_currency();
      $ip_customer = $order->get_customer_ip_address();

      //

      $siteUrl = get_site_url();
      $return_url = $order->get_checkout_payment_url(true);
      // $return_url = "$siteUrl?wc-api=paytabs_callback&order={$order->id}";

      $products = $order->get_items();

      $products_str = implode(' || ', array_map(function ($p) {
        return $p->get_name();
      }, $products));

      $quantity = implode(' || ', array_map(function ($p) {
        return $p->get_quantity();
      }, $products));

      $unit_price = implode(' || ', array_map(function ($p) {
        return $p->get_subtotal();
      }, $products));


      $cdetails = PaytabsHelper::getCountryDetails($order->get_billing_country());
      $phoneext = $cdetails['phone'];

      $countryBilling = PaytabsHelper::countryGetiso3($order->get_billing_country());
      $countryShipping = PaytabsHelper::countryGetiso3($order->get_shipping_country());

      $postalCodeBilling = $order->get_billing_postcode();
      if (empty($postalCodeBilling)) {
        $postalCodeBilling = '11111';
      }
      $postalCodeShipping = $order->get_shipping_postcode();
      if (empty($postalCodeShipping)) {
        $postalCodeShipping = '11111';
      }

      $lang_code = get_locale();
      $lang = ($lang_code == 'ar' || substr($lang_code, 0, 3) == 'ar_') ? 'Arabic' : 'English';

      $params = [
        'payment_type'         => $this->_code,
        'amount'               => $amount,
        'quantity'             => $quantity,
        'currency'             => $currency,
        "unit_price"           => $unit_price,
        'other_charges'        => $other_charges,
        'discount'             => $discount,
        "products_per_title"   => $products_str,

        'cc_first_name'        => $order->get_billing_first_name(),
        'cc_last_name'         => $order->get_billing_last_name(),
        'cc_phone_number'      => $phoneext,
        'phone_number'         => $order->get_billing_phone(),
        'country'              => $countryBilling,
        'state'                => $order->get_billing_state(),
        'city'                 => $order->get_billing_city(),
        'email'                => $order->get_billing_email(),
        'postal_code'          => $postalCodeBilling,
        'billing_address'      => $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(),

        'shipping_firstname'   => $order->get_shipping_first_name(),
        'shipping_lastname'    => $order->get_shipping_last_name(),
        'country_shipping'     => $countryShipping,
        'state_shipping'       => $order->get_shipping_state(),
        'city_shipping'        => $order->get_shipping_city(),
        'postal_code_shipping' => $postalCodeShipping,
        'address_shipping'     => $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2(),

        'title'                => $order->get_formatted_billing_full_name(),
        'reference_no'         => $order->get_id(),
        'cms_with_version'     => "WooCommerce {$woocommerce->version}",
        'site_url'             => $siteUrl,
        'return_url'           => $return_url,
        'msg_lang'             => $lang,
      ];

      return $params;
    }
  }

  class WC_Gateway_Paytabs_Creditcard extends WC_Gateway_Paytabs
  {
    protected $_code = 'creditcard';
    protected $_title = 'PayTabs - CreditCard';
    protected $_description = 'PayTabs - CreditCard payment method';
  }

  class WC_Gateway_Paytabs_Mada extends WC_Gateway_Paytabs
  {
    protected $_code = 'mada';
    protected $_title = 'PayTabs - Mada';
    protected $_description = 'PayTabs - Mada payment method';
  }

  class WC_Gateway_Paytabs_Stcpay extends WC_Gateway_Paytabs
  {
    protected $_code = 'stcpay';
    protected $_title = 'PayTabs - StcPay';
    protected $_description = 'PayTabs - StcPay payment method';
  }
  class WC_Gateway_Paytabs_Stcpayqr extends WC_Gateway_Paytabs
  {
    protected $_code = 'stcpayqr';
    protected $_title = 'PayTabs - StcPayQR';
    protected $_description = 'PayTabs - StcPayQR payment method';
  }
  class WC_Gateway_Paytabs_Applepay extends WC_Gateway_Paytabs
  {
    protected $_code = 'applepay';
    protected $_title = 'PayTabs - ApplePay';
    protected $_description = 'PayTabs - ApplePay payment method';
  }
  class WC_Gateway_Paytabs_Omannet extends WC_Gateway_Paytabs
  {
    protected $_code = 'omannet';
    protected $_title = 'PayTabs - OmanNet';
    protected $_description = 'PayTabs - OmanNet payment method';
  }
  class WC_Gateway_Paytabs_Sadad extends WC_Gateway_Paytabs
  {
    protected $_code = 'sadad';
    protected $_title = 'PayTabs - Sadad';
    protected $_description = 'PayTabs - Sadad payment method';
  }
  class WC_Gateway_Paytabs_Atfawry extends WC_Gateway_Paytabs
  {
    protected $_code = 'atfawry';
    protected $_title = 'PayTabs - @Fawry';
    protected $_description = 'PayTabs - @Fawry payment method';
  }
  class WC_Gateway_Paytabs_Knpay extends WC_Gateway_Paytabs
  {
    protected $_code = 'knpay';
    protected $_title = 'PayTabs - KnPay';
    protected $_description = 'PayTabs - KnPay payment method';
  }
  class WC_Gateway_Paytabs_Amex extends WC_Gateway_Paytabs
  {
    protected $_code = 'amex';
    protected $_title = 'PayTabs - Amex';
    protected $_description = 'PayTabs - Amex payment method';
  }


  /**
   * Add the Gateway to WooCommerce
   **/
  function woocommerce_add_paytabs_gateway($gateways)
  {
    foreach (PAYTABS_PAYPAGE_METHODS as $code => $paytabs_gateway) {
      $gateways[] = $paytabs_gateway;
    }

    return $gateways;
  }

  function paytabs_filter_gateways($load_gateways)
  {
    if (is_admin()) return $load_gateways;

    $gateways = [];

    $currency = get_woocommerce_currency();


    foreach ($load_gateways as $gateway) {
      $add = true;

      foreach (PAYTABS_PAYPAGE_METHODS as $code => $paytabs_gateway) {
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

  add_filter('woocommerce_payment_gateways', 'woocommerce_add_paytabs_gateway');
  add_filter('woocommerce_payment_gateways', 'paytabs_filter_gateways', 10, 1);
}
