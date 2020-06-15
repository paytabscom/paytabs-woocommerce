<?php

if (!defined('PAYTABS_PAYPAGE_VERSION')) {
    return;
}

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

        $this->checkCallback();
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
            $icon = PAYTABS_PAYPAGE_ICONS_URL . "{$this->_code}.png";
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

        $values = WooCommerce2 ? $this->prepareOrder2($order) : $this->prepareOrder($order);

        $paytabsApi = new PaytabsApi($this->merchant_email, $this->secret_key);
        $paypage = $paytabsApi->create_pay_page($values);


        /**
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
            $_logPaypage = json_encode($paypage);
            paytabs_error_log("PayTabs: create PayPage failed for Order {$order_id}, [{$_logPaypage}]");

            $errorMessage = 'PayTabs could not create PayPage';
            try {
                $errorMessage = isset($paypage->details) ? $paypage->details : $paypage->result;
            } catch (\Throwable $th) {
            }
            wc_add_notice($errorMessage, 'error');
            return null;
        }
    }

    private function checkCallback()
    {
        if (isset($_REQUEST['payment_reference'], $_REQUEST['key'])) {
            $payment_reference = $_REQUEST['payment_reference'];
            $key = $_REQUEST['key'];

            $orderId = wc_get_order_id_by_order_key($key);
            $order = wc_get_order($orderId);
            if ($order) {
                $payment_id = $this->getPaymentMethod($order);
                if ($payment_id == $this->id) {
                    $this->callback($payment_reference, $orderId);
                }
            } else {
                paytabs_error_log("PayTabs: callback failed for Order {$orderId}, payemnt_reference [{$payment_reference}]");
            }
        }
    }

    /**
     * In case you need a webhook, like PayPal IPN etc
     */
    public function callback($payment_reference, $order_id)
    {
        if (!$payment_reference) return;

        $paytabsApi = new PaytabsApi($this->merchant_email, $this->secret_key);
        $result = $paytabsApi->verify_payment($payment_reference);

        $_logVerify = json_encode($result);

        $response = ($result && isset($result->response_code));
        if (!$response) {
            paytabs_error_log("PayTabs: callback failed for Order {$order_id}, empty response [{$_logVerify}]");
            return;
        }

        $success = $result->response_code == 100;
        $message = $result->result;

        if (!isset($result->reference_no)) {
            paytabs_error_log("PayTabs: callback failed for Order {$order_id}, response [{$_logVerify}]");
            wc_add_notice($message, 'error');

            // return false;
            // wp_redirect(get_site_url());
            return;
        }

        $orderId = $result->reference_no;
        if ($orderId != $order_id) {
            paytabs_error_log("PayTabs: callback failed for Order {$order_id}, Order mismatch [{$_logVerify}]");
            return;
        }

        $order = wc_get_order($orderId);

        if (!$order) {
            paytabs_error_log("PayTabs: callback failed for Order {$order_id}, Order not found, response [{$_logVerify}]");
            return;
        }

        if ($success) {
            $this->orderSuccess($order, $message);

            // exit;
        } else {
            $_logOrder = (json_encode($order->get_data()));
            paytabs_error_log("PayTabs: callback failed for Order {$order_id}, response [{$_logVerify}], Order [{$_logOrder}]");

            $this->orderFailed($order, $message);

            // exit;
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

        $order->add_order_note($message, true);
        // wc_add_notice(__('Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.', 'woocommerce'), 'success');

        wp_redirect($this->get_return_url($order));
    }

    /**
     * Payment failed => Order status change to failed
     */
    private function orderFailed($order, $message)
    {
        wc_add_notice($message, 'error');

        $order->update_status('failed', __('Payment Cancelled', 'error'));

        // wp_redirect($order->get_cancel_order_url());
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
        $tax = $order->get_total_tax();

        $amount = $total + $discount;
        $other_charges = $shipping + $tax;
        // $totals = $order->get_order_item_totals();

        $currency = $order->get_currency();
        $ip_customer = $order->get_customer_ip_address();

        //

        $siteUrl = get_site_url();
        $return_url = $order->get_checkout_payment_url(true);
        // $return_url = "$siteUrl?wc-api=paytabs_callback&order={$order->id}";

        $products = $order->get_items();

        $items_arr = array_map(function ($p) {
            return [
                'name' => $p->get_name(),
                'quantity' => $p->get_quantity(),
                'price' => $p->get_subtotal() / $p->get_quantity()
            ];
        }, $products);

        $products_arr = PaytabsHelper::prepare_products($items_arr);


        $cdetails = PaytabsHelper::getCountryDetails($order->get_billing_country());
        $phoneext = $cdetails['phone'];

        $telephone = $order->get_billing_phone();
        // Remove country_code from phone_number if it is same as the user's Country code
        $telephone = preg_replace("/^[\+|00]+{$phoneext}/", '', $telephone);

        $countryBilling = PaytabsHelper::countryGetiso3($order->get_billing_country());
        $countryShipping = PaytabsHelper::countryGetiso3($order->get_shipping_country());

        $postalCodeBilling = PaytabsHelper::getNonEmpty($order->get_billing_postcode(), '11111');
        $postalCodeShipping = PaytabsHelper::getNonEmpty($order->get_shipping_postcode(), $postalCodeBilling);

        $stateBilling = PaytabsHelper::getNonEmpty($order->get_billing_state(), $order->get_billing_city());
        $stateShipping = PaytabsHelper::getNonEmpty($order->get_shipping_state(), $order->get_shipping_city());

        $addressBilling = trim($order->get_billing_address_1() . ' ' . $order->get_billing_address_2());
        $addressShipping = trim($order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2());

        $lang_code = get_locale();
        $lang = ($lang_code == 'ar' || substr($lang_code, 0, 3) == 'ar_') ? 'Arabic' : 'English';

        $params = [
            'payment_type'         => $this->_code,

            'title'                => $order->get_formatted_billing_full_name(),

            'currency'             => $currency,
            'amount'               => $amount,
            'other_charges'        => $other_charges,
            'discount'             => $discount,

            'reference_no'         => $order->get_id(),

            'cc_first_name'        => $order->get_billing_first_name(),
            'cc_last_name'         => $order->get_billing_last_name(),
            'cc_phone_number'      => $phoneext,
            'phone_number'         => $telephone,
            'email'                => $order->get_billing_email(),

            'billing_address'      => $addressBilling,
            'state'                => $stateBilling,
            'city'                 => $order->get_billing_city(),
            'postal_code'          => $postalCodeBilling,
            'country'              => $countryBilling,

            'shipping_firstname'   => PaytabsHelper::getNonEmpty($order->get_shipping_first_name(), $order->get_billing_first_name()),
            'shipping_lastname'    => PaytabsHelper::getNonEmpty($order->get_billing_last_name(), $order->get_shipping_last_name()),
            'address_shipping'     => PaytabsHelper::getNonEmpty($addressShipping, $addressBilling),
            'city_shipping'        => PaytabsHelper::getNonEmpty($order->get_shipping_city(), $order->get_billing_city()),
            'state_shipping'       => PaytabsHelper::getNonEmpty($stateShipping, $stateBilling),
            'postal_code_shipping' => $postalCodeShipping,
            'country_shipping'     => PaytabsHelper::getNonEmpty($countryShipping, $countryBilling),

            'site_url'             => $siteUrl,
            'return_url'           => $return_url,

            'msg_lang'             => $lang,
            'cms_with_version'     => "WooCommerce {$woocommerce->version}",

            'ip_customer'          => $ip_customer,
        ];

        $post_arr = array_merge($params, $products_arr);

        $sums = PaytabsHelper::round_amount($post_arr);

        return $post_arr;
    }

    /**
     * $this->prepareOrder which support WooCommerce version 2.x
     */
    private function prepareOrder2($order)
    {
        global $woocommerce;

        // $order->add_order_note();

        $total = $order->get_total();
        $discount = $order->get_total_discount();
        $shipping = $order->get_total_shipping();
        $tax = $order->get_total_tax();

        $amount = $total + $discount;
        $other_charges = $shipping + $tax;
        // $totals = $order->get_order_item_totals();

        $currency = $order->get_order_currency();
        // $ip_customer = $order->get_customer_ip_address();

        //

        $siteUrl = get_site_url();
        $return_url = $order->get_checkout_payment_url(true);
        // $return_url = "$siteUrl?wc-api=paytabs_callback&order={$order->id}";

        $products = $order->get_items();

        $items_arr = array_map(function ($p) {
            return [
                'name' => $p['name'],
                'quantity' => $p['qty'],
                'price' => round($p['line_subtotal'] / $p['qty'], 2)
            ];
        }, $products);

        $products_arr = PaytabsHelper::prepare_products($items_arr);


        $cdetails = PaytabsHelper::getCountryDetails($order->billing_country);
        $phoneext = $cdetails['phone'];

        $telephone = $order->billing_phone;
        // Remove country_code from phone_number if it is same as the user's Country code
        $telephone = preg_replace("/^[\+|00]+{$phoneext}/", '', $telephone);

        $countryBilling = PaytabsHelper::countryGetiso3($order->billing_country);
        $countryShipping = PaytabsHelper::countryGetiso3($order->shipping_country);

        $postalCodeBilling = PaytabsHelper::getNonEmpty($order->billing_postcode, '11111');
        $postalCodeShipping = PaytabsHelper::getNonEmpty($order->shipping_postcode, $postalCodeBilling);

        $stateBilling = PaytabsHelper::getNonEmpty($order->billing_state, $order->billing_city);
        $stateShipping = PaytabsHelper::getNonEmpty($order->shipping_state, $order->shipping_city);

        $addressBilling = trim($order->billing_address_1 . ' ' . $order->billing_address_2);
        $addressShipping = trim($order->shipping_address_1 . ' ' . $order->shipping_address_2);

        $lang_code = get_locale();
        $lang = ($lang_code == 'ar' || substr($lang_code, 0, 3) == 'ar_') ? 'Arabic' : 'English';

        $params = [
            'payment_type'         => $this->_code,

            'title'                => $order->get_formatted_billing_full_name(),

            'currency'             => $currency,
            'amount'               => $amount,
            'other_charges'        => $other_charges,
            'discount'             => $discount,

            'reference_no'         => $order->id,

            'cc_first_name'        => $order->billing_first_name,
            'cc_last_name'         => $order->billing_last_name,
            'cc_phone_number'      => $phoneext,
            'phone_number'         => $telephone,
            'email'                => $order->billing_email,

            'billing_address'      => $addressBilling,
            'state'                => $stateBilling,
            'city'                 => $order->billing_city,
            'postal_code'          => $postalCodeBilling,
            'country'              => $countryBilling,

            'shipping_firstname'   => PaytabsHelper::getNonEmpty($order->shipping_first_name, $order->billing_first_name),
            'shipping_lastname'    => PaytabsHelper::getNonEmpty($order->shipping_last_name, $order->billing_last_name),
            'address_shipping'     => PaytabsHelper::getNonEmpty($addressShipping, $addressBilling),
            'city_shipping'        => PaytabsHelper::getNonEmpty($order->shipping_city, $order->billing_city),
            'state_shipping'       => PaytabsHelper::getNonEmpty($stateShipping, $stateBilling),
            'postal_code_shipping' => $postalCodeShipping,
            'country_shipping'     => PaytabsHelper::getNonEmpty($countryShipping, $countryBilling),

            'site_url'             => $siteUrl,
            'return_url'           => $return_url,

            'msg_lang'             => $lang,
            'cms_with_version'     => "WooCommerce {$woocommerce->version}",
        ];

        $post_arr = array_merge($params, $products_arr);

        return $post_arr;
    }

    //

    private function getPaymentMethod($order)
    {
        return WooCommerce2 ? $order->payment_method : $order->get_payment_method();
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

class WC_Gateway_Paytabs_Valu extends WC_Gateway_Paytabs
{
    protected $_code = 'valu';
    protected $_title = 'PayTabs - valU';
    protected $_description = 'valU payments powered by PayTabs';
}
