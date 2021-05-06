<?php

defined('PAYTABS_PAYPAGE_VERSION') or die;

class WC_Gateway_Paytabs extends WC_Payment_Gateway
{
    protected $_code = '';
    protected $_title = '';
    protected $_description = '';
    protected $_icon = null;
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
            'refunds',

            'subscriptions',
            'subscription_cancellation',
            'subscription_suspension',
            'subscription_reactivation',
            'subscription_amount_changes',
            'subscription_date_changes',
            'subscription_payment_method_change',
            'subscription_payment_method_change_customer',
            'subscription_payment_method_change_admin',
            'multiple_subscriptions',

            'pre-orders',
            'tokenization',
            // 'token_editor',
            // 'add_payment_method',
        );

        // Method with all the options fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');

        // PT
        $this->paytabs_endpoint = $this->get_option('endpoint');
        $this->merchant_id = $this->get_option('profile_id');
        $this->merchant_key = $this->get_option('server_key');

        $this->hide_shipping = $this->get_option('hide_shipping') == 'yes';

        $this->order_status_success = $this->get_option('status_success');
        $this->order_status_failed  = $this->get_option('status_failed');

        if ($this->_code == 'valu') {
            $this->valu_product_id = $this->get_option('valu_product_id');
        }

        // This action hook saves the settings
        add_action("woocommerce_update_options_payment_gateways_{$this->id}", array($this, 'process_admin_options'));

        add_action('woocommerce_scheduled_subscription_payment_' . $this->id, array($this, 'scheduled_subscription_payment'), 10, 2);

        //

        $this->tokenise_param = "wc-{$this->id}-new-payment-method";
        $this->token_id_param = "wc-{$this->id}-payment-token";

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
        $icon_name = $this->_icon ?? "{$this->_code}.png";

        $iconPath = PAYTABS_PAYPAGE_DIR . "icons/{$icon_name}";
        $icon = '';
        if (file_exists($iconPath)) {
            $icon = PAYTABS_PAYPAGE_ICONS_URL . "{$icon_name}";
        }

        return $icon;
    }


    /**
     * Plugin options
     */
    public function init_form_fields()
    {
        $orderStatuses = wc_get_order_statuses();
        $orderStatuses = array_merge(
            ['default' => 'Default (recommended option)'],
            $orderStatuses
        );

        $endpoints = PaytabsApi::getEndpoints();

        $this->form_fields = array(
            'enabled' => array(
                'title'       => __('Enable/Disable', 'PayTabs'),
                'label'       => __('Enable Payment Gateway.', 'PayTabs'),
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'endpoint' => array(
                'title'       => __('PayTabs endpoint region', 'PayTabs'),
                'type'        => 'select',
                'description' => 'Select your domain',
                'options'     => $endpoints,
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
            'status_success' => array(
                'title'       => __('Success Order status', 'PayTabs'),
                'type'        => 'select',
                'description' => 'Set the Order status after successful payment. <br><strong>Warning</strong> Be very careful when you change the Default option because when you change it, you change the normal flow of the Order into the WooCommerce system, you may encounter some consequences based on the new value you set',
                'options'     => $orderStatuses,
            ),
            'status_failed' => array(
                'title'       => __('Failed Order status', 'PayTabs'),
                'type'        => 'select',
                'description' => 'Set the Order status after failed payment. <br><strong>Warning</strong> Be very careful when you change the Default option because when you change it, you change the normal flow of the Order into the WooCommerce system, you may encounter some consequences based on the new value you set',
                'options'     => $orderStatuses,
            ),
        );
    }


    /**
     *  There are no payment fields for paytabs, but we want to show the description if set.
     **/
    function payment_fields()
    {
        if ($this->description) echo wpautop(wptexturize($this->description));

        $this->tokenization_script();
        $this->saved_payment_methods();
        $this->save_payment_method_checkbox();
        // $this->form();
    }


    private function has_subscription($order_id)
    {
        return (function_exists('wcs_order_contains_subscription') &&
            (wcs_order_contains_subscription($order_id) ||
                wcs_is_subscription($order_id) ||
                wcs_order_contains_renewal($order_id)));
    }

    private function is_tokenise()
    {
        return (bool) $_POST[$this->tokenise_param];
    }

    private function get_token()
    {
        $token_id = $_POST[$this->token_id_param];

        if (!$token_id) {
            return null;
        }

        if ($token_id === 'new') {
            return false;
        }

        $tokenObj = WC_Payment_Tokens::get($token_id);

        if ($tokenObj->get_user_id() !== get_current_user_id()) {
            // Optionally display a notice with `wc_add_notice`
            return null;
        }

        // $tokens = WC_Payment_Tokens::get_customer_tokens(get_current_user_id());
        // $token = WC_Payment_Tokens::get_customer_default_token(get_current_user_id());

        // Get tokens associated with Order
        // $tokens = WC_Payment_Tokens::get_order_tokens($order_id);
        // $tokens = $order->get_payment_tokens();

        // WC_Payment_Tokens::delete(10);
        // $order->add_payment_token($token);

        return $tokenObj;
    }


    /**
     * We're processing the payments here
     **/
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        $_paytabsApi = PaytabsApi::getInstance($this->paytabs_endpoint, $this->merchant_id, $this->merchant_key);


        $saved_token = $this->get_token();
        if ($saved_token) {
            $values = $this->prepareOrder_Tokenised($order, $saved_token);
        } else {
            $values = WooCommerce2 ? $this->prepareOrder2($order) : $this->prepareOrder($order);
        }

        $paypage = $_paytabsApi->create_pay_page($values);

        //

        $success = $paypage->success;
        $message = $paypage->message;
        $is_redirect = @$paypage->is_redirect;
        $is_completed = @$paypage->is_completed;

        if ($success) {
            if ($is_completed) {
                return $this->validate_payment($paypage, $order_id, $order, true);
            } else {
                $payment_url = $paypage->payment_url;

                return array(
                    'result'   => 'success',
                    'redirect' => $payment_url,
                );
            }
        } else {
            $_logPaypage = json_encode($paypage);
            $_logParams = json_encode($values);
            PaytabsHelper::log("create PayPage failed for Order {$order_id}, [{$_logPaypage}], [{$_logParams}]", 3);

            $errorMessage = $message;

            wc_add_notice($errorMessage, 'error');
            return null;
        }
    }


    public function scheduled_subscription_payment($amount_to_charge, $renewal_order)
    {
        $user_id = $renewal_order->get_user_id();
        $tokenObj = WC_Payment_Tokens::get_customer_default_token($user_id);
        if (!$tokenObj) {
            // ToDo: Try to fetch User's Tokens
            paytabs_error_log("Subscription renewal error: The User {$user_id} does not have saved Token.");
            return false;
        }
        $values = $this->prepareOrder_Tokenised($renewal_order, $tokenObj, $amount_to_charge);

        $_paytabsApi = PaytabsApi::getInstance($this->paytabs_endpoint, $this->merchant_id, $this->merchant_key);
        $paypage = $_paytabsApi->create_pay_page($values);

        $success = $paypage->success;
        $transaction_id = @$paypage->tran_ref;
        $message = $paypage->message;
        $is_completed = @$paypage->is_completed;
        if ($success) {
            // $this->validate_payment($paypage, $renewal_order->get_id(), $renewal_order, true);
            $renewal_order->payment_complete($transaction_id);
            return true;
        } else {
            $renewal_order->add_order_note("Renewal failed [{$message}]");
        }

        return false;

        // $this->process_subscription_payment($amount_to_charge, $renewal_order, true, false);
    }


    public function process_refund($order_id, $amount = null, $reason = '')
    {
        global $woocommerce;

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

        $pt_refundHolder = new PaytabsFollowupHolder();
        $pt_refundHolder
            ->set02Transaction(PaytabsEnum::TRAN_TYPE_REFUND, PaytabsEnum::TRAN_CLASS_ECOM)
            ->set03Cart($order_id, $currency, $amount, $reason)
            ->set30TransactionInfo($transaction_id)
            ->set99PluginInfo('WooCommerce', $woocommerce->version, PAYTABS_PAYPAGE_VERSION);

        $values = $pt_refundHolder->pt_build();

        $_paytabsApi = PaytabsApi::getInstance($this->paytabs_endpoint, $this->merchant_id, $this->merchant_key);
        $refundRes = $_paytabsApi->request_followup($values);

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

        $_paytabsApi = PaytabsApi::getInstance($this->paytabs_endpoint, $this->merchant_id, $this->merchant_key);
        $result = $_paytabsApi->verify_payment($payment_reference);
        // $valid_redirect = $_paytabsApi->is_valid_redirect($_POST);

        $this->validate_payment($result, $order_id, $order);
    }


    private function validate_payment($result, $order_id, $order, $is_tokenise = false)
    {
        $success = $result->success;
        $message = $result->message;
        $orderId = @$result->reference_no;
        $transaction_ref = @$result->transaction_id;
        $token = @$result->token;

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
            return $this->orderSuccess($order, $transaction_ref, $token, $message, $is_tokenise);

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
    private function orderSuccess($order, $transaction_id, $token_str, $message, $is_tokenise)
    {
        global $woocommerce;

        $order->payment_complete($transaction_id);
        // $order->reduce_order_stock();

        $this->setNewStatus($order, true);

        $woocommerce->cart->empty_cart();

        $order->add_order_note($message, true);
        // wc_add_notice(__('Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.', 'woocommerce'), 'success');

        if ($token_str) {
            $token = new WC_Payment_Token_Paytabs();

            $token->set_token($token_str);
            $token->set_tran_ref($transaction_id);

            $token->set_gateway_id($this->id);
            $user_id = $order->get_user_id();
            $token->set_user_id($user_id);

            $tokeId = $token->save();

            $order->add_payment_token($token);
            $order->save();
        }

        $redirect_url = $this->get_return_url($order);

        if ($is_tokenise) {
            return array(
                'result' => 'success',
                'redirect' => $redirect_url,
            );
        } else {
            wp_redirect($redirect_url);
        }
    }


    /**
     * Payment failed => Order status change to failed
     */
    private function orderFailed($order, $message)
    {
        wc_add_notice($message, 'error');

        $order->update_status('failed', $message);

        $this->setNewStatus($order, false);

        // wp_redirect($order->get_cancel_order_url());
    }


    private function setNewStatus($order, $isSuccess)
    {
        if ($isSuccess) {
            $configStatus = $this->order_status_success;
            $defaultStatus = 'wc-processing';
        } else {
            $configStatus = $this->order_status_failed;
            $defaultStatus = 'wc-failed';
        }
        $isDefault = $configStatus == 'default' || $configStatus == $defaultStatus;

        if (!$isDefault) {
            $newMsg = "Order status changed as in the admin configuration!";
            $order->update_status($configStatus, $newMsg, true);
        }
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
        global $woocommerce;

        // $order->add_order_note();

        $is_subscription = $this->has_subscription($order->get_id());
        $tokenise = $this->is_tokenise() || $is_subscription;

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
            return "{$p->get_name()} ({$p->get_quantity()})";
        }, $products);

        $cart_desc = implode(', ', $items_arr);

        // $cdetails = PaytabsHelper::getCountryDetails($order->get_billing_country());
        // $phoneext = $cdetails['phone'];

        $telephone = $order->get_billing_phone();

        $countryBilling = $order->get_billing_country();
        $addressBilling = trim($order->get_billing_address_1() . ' ' . $order->get_billing_address_2());

        $is_diff_shipping_address = (bool) $_POST["ship_to_different_address"];
        if ($is_diff_shipping_address) {
            $countryShipping = $order->get_shipping_country();
            $addressShipping = trim($order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2());
        } else {
            $addressShipping = null;
            $countryShipping = null;
        }

        $lang_code = get_locale();
        $lang = ($lang_code == 'ar' || substr($lang_code, 0, 3) == 'ar_') ? 'ar' : 'en';

        //

        $nameBilling = $order->get_formatted_billing_full_name();
        $email = $order->get_billing_email();
        $cityBilling = $order->get_billing_city();
        $stateBilling = $order->get_billing_state();
        $zipBilling = $order->get_billing_postcode();

        //

        $holder = new PaytabsRequestHolder();
        $holder
            ->set01PaymentCode($this->_code)
            ->set02Transaction(PaytabsEnum::TRAN_TYPE_SALE, PaytabsEnum::TRAN_CLASS_ECOM)
            ->set03Cart($order->get_id(), $currency, $amount, $cart_desc)
            ->set04CustomerDetails(
                $nameBilling,
                $email,
                $telephone,
                $addressBilling,
                $cityBilling,
                $stateBilling,
                $countryBilling,
                $zipBilling,
                $ip_customer
            );

        if ($is_diff_shipping_address) {
            $holder->set05ShippingDetails(
                false,
                $order->get_formatted_shipping_full_name(),
                $order->get_billing_email(),
                null,
                $addressShipping,
                $order->get_shipping_city(),
                $order->get_shipping_state(),
                $countryShipping,
                $order->get_shipping_postcode(),
                null
            );
        } else if (!$this->hide_shipping) {
            $holder->set05ShippingDetails(true);
        }

        $holder->set06HideShipping($this->hide_shipping)
            ->set07URLs(
                $return_url,
                null
            )
            ->set08Lang($lang)
            ->set10Tokenise($tokenise)
            ->set99PluginInfo('WooCommerce', $woocommerce->version, PAYTABS_PAYPAGE_VERSION);

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
        global $woocommerce;

        // $order->add_order_note();

        $total = $order->get_total();
        // $discount = $order->get_total_discount();
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
            return "{$p['name']} ({$p['qty']})";
        }, $products);

        $cart_desc = implode(', ', $items_arr);

        // $cdetails = PaytabsHelper::getCountryDetails($order->billing_country);
        // $phoneext = $cdetails['phone'];

        $telephone = $order->billing_phone;

        $countryBilling = $order->billing_country;
        $addressBilling = trim($order->billing_address_1 . ' ' . $order->billing_address_2);

        $is_diff_shipping_address = (bool) $_POST["ship_to_different_address"];
        if ($is_diff_shipping_address) {
            $addressShipping = trim($order->shipping_address_1 . ' ' . $order->shipping_address_2);
            $countryShipping = $order->shipping_country;
        } else {
            $addressShipping = null;
            $countryShipping = null;
        }

        $lang_code = get_locale();
        $lang = ($lang_code == 'ar' || substr($lang_code, 0, 3) == 'ar_') ? 'ar' : 'en';

        $holder = new PaytabsRequestHolder();
        $holder
            ->set01PaymentCode($this->_code)
            ->set02Transaction(PaytabsEnum::TRAN_TYPE_SALE, PaytabsEnum::TRAN_CLASS_ECOM)
            ->set03Cart($order->id, $currency, $amount, $cart_desc)
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
            );

        if ($is_diff_shipping_address) {
            $holder->set05ShippingDetails(
                false,
                $order->get_formatted_shipping_full_name(),
                $order->billing_email,
                null,
                $addressShipping,
                $order->shipping_city,
                $order->shipping_state,
                $countryShipping,
                $order->shipping_postcode,
                null
            );
        } else if (!$this->hide_shipping) {
            $holder->set05ShippingDetails(true);
        }

        $holder->set06HideShipping($this->hide_shipping)
            ->set07URLs(
                $return_url,
                null
            )
            ->set08Lang($lang)
            ->set99PluginInfo('WooCommerce', $woocommerce->version, PAYTABS_PAYPAGE_VERSION);

        if ($this->_code == 'valu') {
            // $holder->set20ValuParams($this->valu_product_id, 0);
        }

        $post_arr = $holder->pt_build(true);

        return $post_arr;
    }

    //

    private function prepareOrder_Tokenised($order, $tokenObj, $amount_to_charge = null)
    {
        global $woocommerce;

        $amount = $order->get_total();
        if ($amount_to_charge) {
            $amount = $amount_to_charge;
        }
        $currency = $order->get_currency();

        //

        $token = $tokenObj->get_token();
        $tran_ref = $tokenObj->get_tran_ref();

        //

        $products = $order->get_items();
        $items_arr = array_map(function ($p) {
            return "{$p->get_name()} ({$p->get_quantity()})";
        }, $products);

        $cart_desc = implode(', ', $items_arr);

        //

        $holder = new PaytabsTokenHolder();
        $holder
            ->set02Transaction(PaytabsEnum::TRAN_TYPE_SALE, PaytabsEnum::TRAN_CLASS_RECURRING)
            ->set03Cart($order->get_id(), $currency, $amount, $cart_desc)
            ->set20Token($token, $tran_ref)
            ->set99PluginInfo('WooCommerce', $woocommerce->version, PAYTABS_PAYPAGE_VERSION);

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
