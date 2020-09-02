<?php

defined('PAYTABS_PAYPAGE_VERSION') or die;

class WC_Gateway_Paytabs extends WC_Payment_Gateway
{
    protected $_code = '';
    protected $_title = '';
    protected $_description = '';
    //
    protected $_paytabsApi;

    //

    public function __construct()
    {
        $this->id = "paytabs_{$this->_code}"; // payment gateway plugin ID
        $this->icon = $this->getIcon(); // URL of the icon that will be displayed on checkout page near the gateway name
        $this->has_fields = false; // in case you need a custom credit card form
        $this->method_title = $this->_title;
        $this->method_description = $this->_description; // will be displayed on the options page

        // gateways can support subscriptions, refunds, saved payment methods,
        // Supports simple payments
        $this->supports = array(
            'products',
            'refunds'
        );

        // Method with all the options fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');

        // PT
        $this->merchant_id = $this->get_option('profile_id');
        $this->merchant_key = $this->get_option('server_key');

        $this->hide_shipping = $this->get_option('hide_shipping') == 'yes';

        if ($this->_code == 'valu') {
            $this->valu_product_id = $this->get_option('valu_product_id');
        }

        // This action hook saves the settings
        add_action("woocommerce_update_options_payment_gateways_{$this->id}", array($this, 'process_admin_options'));


        // We need custom JavaScript to obtain a token
        // add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

        // Register a webhook
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
            // PT
            'profile_id' => array(
                'title'       => __('Profile ID', 'PayTabs'),
                'type'        => 'text',
                'description' => __('Please enter the "Profile ID" of your PayTabs Merchant account.', 'PayTabs'),
                'default'     => '',
                'required'    => true
            ),
            'server_key' => array(
                'title'       => __('Server Key', 'PayTabs'),
                'type'        => 'text',
                'description' => __('Please enter your PayTabs "Server Key". You can find it on your Merchant’s Portal', 'PayTabs'),
                'default'     => '',
                'required'    => true
            ),
            'hide_shipping' => array(
                'title'       => __('Hide shipping info', 'PayTabs'),
                'label'       => __('Hide shipping info', 'PayTabs'),
                'type'        => 'checkbox',
                'description' => 'Enable if you wish to hide Shipping info of the customer in PayTabs payment page.',
                'default'     => 'no'
            ),
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
        $order = wc_get_order($order_id);

        $values = WooCommerce2 ? $this->prepareOrder2($order) : $this->prepareOrder($order);

        $_paytabsApi = PaytabsApi::getInstance($this->merchant_id, $this->merchant_key);
        $paypage = $_paytabsApi->create_pay_page($values);


        /**
         * Your API interaction could be built with wp_remote_post()
         */
        // $response = wp_remote_post('{payment processor endpoint}', $args);

        $success = $paypage->success;
        $message = $paypage->message;

        if ($success) {
            $payment_url = $paypage->payment_url;

            return array(
                'result'    => 'success',
                'redirect'  => $payment_url,
            );
        } else {
            $_logPaypage = json_encode($paypage);
            $_logParams = json_encode($values);
            PaytabsHelper::log("create PayPage failed for Order {$order_id}, [{$_logPaypage}], [{$_logParams}]", 3);

            $errorMessage = $message;

            wc_add_notice($errorMessage, 'error');
            return null;
        }
    }


    public function process_refund($order_id, $amount = null, $reason = '')
    {
        if (!$amount) {
            return false;
        }

        $order = wc_get_order($order_id);
        $transaction_id = $order->get_transaction_id();

        if (!$transaction_id) {
            return false;
        }

        // PT
        $currency = $order->get_currency();
        if (empty($reason)) $reason = 'Admin request';

        $pt_refundHolder = new PaytabsRefundHolder();
        $pt_refundHolder
            ->set01RefundInfo($amount, $currency)
            ->set02Transaction($order_id, $transaction_id, $reason);

        $values = $pt_refundHolder->pt_build();

        $_paytabsApi = PaytabsApi::getInstance($this->merchant_id, $this->merchant_key);
        $refundRes = $_paytabsApi->refund($values);

        $success = $refundRes->success;
        $message = $refundRes->message;
        $pending_success = $refundRes->pending_success;

        $order->add_order_note('Refund status: ' . $message, true);

        if ($success) {
            $order->update_status('refunded', __('Payment Refunded: ', 'PayTabs'));
        } else if ($pending_success) {
            $order->update_status('on-hold', __('Payment Pending Refund: ', 'PayTabs'));
        }

        return $success;
    }


    private function checkCallback()
    {
        // PT
        $param_paymentRef = 'tranRef';

        if (isset($_POST[$param_paymentRef], $_POST['cartId'])) {
            $payment_reference = $_POST[$param_paymentRef];
            $orderId = $_POST['cartId'];

            // $orderId = wc_get_order_id_by_order_key($key);
            $order = wc_get_order($orderId);
            if ($order) {
                if ($order->needs_payment()) {
                    $payment_id = $this->getPaymentMethod($order);
                    if ($payment_id == $this->id) {
                        $this->callback($payment_reference, $orderId, $order);
                    }
                }
            } else {
                PaytabsHelper::log("callback failed for Order {$orderId}, payemnt_reference [{$payment_reference}]", 3);
            }
        }
    }


    /**
     * In case you need a webhook, like PayPal IPN etc
     */
    public function callback($payment_reference, $order_id, $order)
    {
        if (!$payment_reference) return;

        $_paytabsApi = PaytabsApi::getInstance($this->merchant_id, $this->merchant_key);
        $result = $_paytabsApi->verify_payment($payment_reference);
        // $valid_redirect = $_paytabsApi->is_valid_redirect($_POST);

        $success = $result->success;
        $message = $result->message;
        $orderId = @$result->reference_no;
        $transaction_ref = @$result->transaction_id;

        $_logVerify = json_encode($result);

        if (!$orderId) {
            PaytabsHelper::log("callback failed for Order {$order_id}, response [{$_logVerify}]", 3);
            wc_add_notice($message, 'error');

            // return false;
            // wp_redirect(get_site_url());
            return;
        }

        if ($orderId != $order_id) {
            PaytabsHelper::log("callback failed for Order {$order_id}, Order mismatch [{$_logVerify}]", 3);
            return;
        }

        if ($success) {
            $this->orderSuccess($order, $transaction_ref, $message);

            // exit;
        } else {
            $_data = WooCommerce2 ? $order->data : $order->get_data();
            $_logOrder = (json_encode($_data));
            PaytabsHelper::log("callback failed for Order {$order_id}, response [{$_logVerify}], Order [{$_logOrder}]", 3);

            $this->orderFailed($order, $message);

            // exit;
        }
    }


    /**
     * Payment successed => Order status change to success
     */
    private function orderSuccess($order, $transaction_id, $message)
    {
        global $woocommerce;

        $order->payment_complete($transaction_id);
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
        // PT
        // global $woocommerce;

        // $order->add_order_note();

        $total = $order->get_total();
        // $discount = $order->get_total_discount();
        // $shipping = $order->get_total_shipping();
        // $tax = $order->get_total_tax();

        $amount = $total; // + $discount;
        // $other_charges = $shipping + $tax;
        // $totals = $order->get_order_item_totals();

        $currency = $order->get_currency();
        $ip_customer = $order->get_customer_ip_address();

        //

        // $siteUrl = get_site_url();
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

        // $cdetails = PaytabsHelper::getCountryDetails($order->get_billing_country());
        // $phoneext = $cdetails['phone'];

        $telephone = $order->get_billing_phone();

        $countryBilling = PaytabsHelper::countryGetiso3($order->get_billing_country());
        $countryShipping = PaytabsHelper::countryGetiso3($order->get_shipping_country());

        $addressBilling = trim($order->get_billing_address_1() . ' ' . $order->get_billing_address_2());
        $addressShipping = trim($order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2());

        $lang_code = get_locale();
        // $lang = ($lang_code == 'ar' || substr($lang_code, 0, 3) == 'ar_') ? 'Arabic' : 'English';

        $holder = new PaytabsHolder2();
        $holder
            ->set01PaymentCode($this->_code)
            ->set02Transaction('sale', 'ecom')
            ->set03Cart($order->get_id(), $currency, $amount, json_encode($items_arr))
            ->set04CustomerDetails(
                $order->get_formatted_billing_full_name(),
                $order->get_billing_email(),
                $telephone,
                $addressBilling,
                $order->get_billing_city(),
                $order->get_billing_state(),
                $countryBilling,
                $order->get_billing_postcode(),
                $ip_customer
            )
            ->set05ShippingDetails(
                $order->get_formatted_shipping_full_name(),
                $order->get_billing_email(),
                null,
                $addressShipping,
                $order->get_shipping_city(),
                $order->get_shipping_state(),
                $countryShipping,
                $order->get_shipping_postcode(),
                null
            )
            ->set06HideShipping($this->hide_shipping)
            ->set07URLs(
                $return_url,
                null
            )
            ->set08Lang($lang_code);

        if ($this->_code == 'valu') {
            // $holder->set20ValuParams($this->valu_product_id, 0);
        }

        $post_arr = $holder->pt_build(true);

        return $post_arr;
    }


    /**
     * $this->prepareOrder which support WooCommerce version 2.x
     */
    private function prepareOrder2($order)
    {
        // PT
        // global $woocommerce;

        // $order->add_order_note();

        $total = $order->get_total();
        $discount = $order->get_total_discount();
        // $shipping = $order->get_total_shipping();
        // $tax = $order->get_total_tax();

        $amount = $total; // + $discount;
        // $other_charges = $shipping + $tax;
        // $totals = $order->get_order_item_totals();

        $currency = $order->get_order_currency();
        // $ip_customer = $order->get_customer_ip_address();

        //

        // $siteUrl = get_site_url();
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

        // $cdetails = PaytabsHelper::getCountryDetails($order->billing_country);
        // $phoneext = $cdetails['phone'];

        $telephone = $order->billing_phone;

        $countryBilling = PaytabsHelper::countryGetiso3($order->billing_country);
        $countryShipping = PaytabsHelper::countryGetiso3($order->shipping_country);

        $addressBilling = trim($order->billing_address_1 . ' ' . $order->billing_address_2);
        $addressShipping = trim($order->shipping_address_1 . ' ' . $order->shipping_address_2);

        $lang_code = get_locale();
        // $lang = ($lang_code == 'ar' || substr($lang_code, 0, 3) == 'ar_') ? 'Arabic' : 'English';

        $holder = new PaytabsHolder();
        $holder
            ->set01PaymentCode($this->_code)
            ->set02Transaction('sale', 'ecom')
            ->set03Cart($order->id, $currency, $amount, json_encode($items_arr))
            ->set04CustomerDetails(
                $order->get_formatted_billing_full_name(),
                $order->billing_email,
                $telephone,
                $addressBilling,
                $order->billing_city,
                $order->billing_state,
                $countryBilling,
                $order->billing_postcode,
                null
            )
            ->set05ShippingDetails(
                $order->get_formatted_shipping_full_name(),
                $order->billing_email,
                null,
                $addressShipping,
                $order->shipping_city,
                $order->shipping_state,
                $countryShipping,
                $order->shipping_postcode,
                null
            )
            ->set06HideShipping(false)
            ->set07URLs(
                $return_url,
                null
            )
            ->set08Lang($lang_code);

        if ($this->_code == 'valu') {
            // $holder->set20ValuParams($this->valu_product_id, 0);
        }

        $post_arr = $holder->pt_build(true);

        return $post_arr;
    }

    //

    private function getPaymentMethod($order)
    {
        return WooCommerce2 ? $order->payment_method : $order->get_payment_method();
    }
}
