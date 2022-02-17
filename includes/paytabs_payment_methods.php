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

    const PT_HANDLED = '_pt_handled';
    const PT_TRAN_TYPE = '_pt_transaction_type';

    //

    public function __construct()
    {
        $this->id = "paytabs_{$this->_code}"; // payment gateway plugin ID
        $this->icon = $this->getIcon(); // URL of the icon that will be displayed on checkout page near the gateway name
        $this->has_fields = false;
        $this->method_title = $this->_title;
        $this->method_description = $this->_description;

        //
        $this->_is_card_method = PaytabsHelper::isCardPayment($this->_code);
        $this->_support_tokenise = PaytabsHelper::supportTokenization($this->_code);
        $this->_support_auth_capture = PaytabsHelper::supportAuthCapture($this->_code);
        $this->_support_iframe = PaytabsHelper::supportIframe($this->_code);

        $tokenise_features = [
            'tokenization',

            'subscriptions',
            'subscription_cancellation',
            'subscription_suspension',
            'subscription_reactivation',
            'subscription_amount_changes',
            'subscription_date_changes',
            'multiple_subscriptions',
            // 'subscription_payment_method_change',
            // 'subscription_payment_method_change_customer',
            // 'subscription_payment_method_change_admin',
        ];

        $this->supports = array(
            'products',
            'refunds',

            'pre-orders',
            // 'token_editor',
            // 'add_payment_method',
        );

        if ($this->_support_tokenise) {
            $this->supports = array_merge($this->supports, $tokenise_features);
        }

        // Method with all the options fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->payment_form = $this->get_option('payment_form');
        $this->is_frammed_page = ($this->payment_form === 'iframe');

        // PT
        $this->paytabs_endpoint = $this->get_option('endpoint');
        $this->merchant_id = $this->get_option('profile_id');
        $this->merchant_key = $this->get_option('server_key');

        $this->hide_shipping = $this->get_option('hide_shipping') == 'yes';

        $this->order_status_success = $this->get_option('status_success');
        $this->order_status_failed  = $this->get_option('status_failed');

        $this->trans_type = $this->get_option('trans_type', PaytabsEnum::TRAN_TYPE_SALE);
        $this->order_status_auth_success = $this->get_option('status_auth_success', 'wc-on-hold');


        if ($this->_code == 'valu') {
            $this->valu_product_id = $this->get_option('valu_product_id');
        }

        $this->enable_tokenise = $this->get_option('enable_tokenise') == 'yes';
        $this->allow_associated_methods = $this->get_option('allow_associated_methods') == 'yes';

        $this->ipn_enable = $this->get_option('ipn_enable') == 'yes';

        // This action hook saves the settings
        add_action("woocommerce_update_options_payment_gateways_{$this->id}", array($this, 'process_admin_options'));

        //

        add_action('woocommerce_scheduled_subscription_payment_' . $this->id, array($this, 'scheduled_subscription_payment'), 10, 2);

        $this->tokenise_param = "wc-{$this->id}-new-payment-method";
        $this->token_id_param = "wc-{$this->id}-payment-token";

        // We need custom JavaScript to obtain a token
        // add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

        // Register a webhook
        // add_action('woocommerce_api_paytabs_callback', array($this, 'callback'));
        add_action('woocommerce_api_wc_gateway_' . $this->id, array($this, 'callback_response'));
        add_action('woocommerce_api_wc_gateway_r_' . $this->id, array($this, 'return_response'));

        if (!has_filter('woocommerce_api_wc_gateway_paytabs')) {
            add_action('woocommerce_api_wc_gateway_paytabs', array($this, 'ipn_response'));
        }

        add_action('woocommerce_order_status_completed', array($this, 'process_capture'), 10, 1);
        add_action('woocommerce_order_status_cancelled', array($this, 'process_void'), 10, 1);
        // $this->checkCallback();

        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
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


    private function get_ipn_url()
    {
        // $siteUrl = get_site_url();
        $ipn_url = add_query_arg('wc-api', 'wc_gateway_paytabs', home_url('/'));

        return  $ipn_url;
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

        $ipn_url = $this->get_ipn_url();

        $addional_fields = [];

        if ($this->_is_card_method) {
            $addional_fields['allow_associated_methods'] = [
                'title'       => __('Allow associated methods', 'PayTabs'),
                'type'        => 'checkbox',
                'description' => 'Accept all associated methods of the current payment method, do not limit to this one only.',
                'default'     => 'yes'
            ];
        }

        if ($this->_support_tokenise) {
            $addional_fields['enable_tokenise'] = [
                'title'       => __('Enable Tokenise', 'PayTabs'),
                'type'        => 'checkbox',
                'description' => 'Allow your customers to save their payment methods for later use.',
                'default'     => 'yes'
            ];
        }

        if ($this->_support_auth_capture) {
            $addional_fields['trans_type'] = [
                'title'       => __('Transaction Type', 'PayTabs'),
                'label'       => __('Transaction Type', 'PayTabs'),
                'type'        => 'select',
                'description' => 'Set the transaction type to Auth or Sale',
                'options'     => array(
                    PaytabsEnum::TRAN_TYPE_SALE => __('Sale', 'PayTabs'),
                    PaytabsEnum::TRAN_TYPE_AUTH => __('Auth', 'PayTabs'),
                ),
                'default'     => PaytabsEnum::TRAN_TYPE_SALE
            ];

            $addional_fields['status_auth_success'] = [
                'title'       => __('Auth Order status', 'PayTabs'),
                'type'        => 'select',
                'description' => 'Set the Order status if the Auth succeed.',
                'options'     => $orderStatuses,
                'default'     => 'wc-on-hold'
            ];
        }

        if ($this->_support_iframe) {
            $addional_fields['payment_form'] = [
                'title'       => __('Payment form type', 'PayTabs'),
                'type'        => 'select',
                'options'     => array(
                    'redirect' => __('Redirect to hosted form on PayTabs server', 'PayTabs'),
                    'iframe'   => __('iFrame payment form integrated into checkout', 'PayTabs'),
                ),
                'description' => __("Hosted form on PayTabs server is the secure solution of choice, While iFrame provides better customer experience (https strongly advised)", 'PayTabs'),
                'default'     => 'redirect',
                'desc_tip'    => false,
            ];
        }

        $fields = array(
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
                'title'       => __('Captured Order status', 'PayTabs'),
                'type'        => 'select',
                'description' => 'Set the Order status after successful payment.'
                    . '<br><strong>Warning</strong> Be very careful when you change the Default option because when you change it, you change the normal flow of the Order into WooCommerce system, you may encounter some consequences based on the new value you set',
                'options'     => $orderStatuses,
            ),
            'status_failed' => array(
                'title'       => __('Failed Order status', 'PayTabs'),
                'type'        => 'select',
                'description' => 'Set the Order status after failed payment.'
                    . '<br><strong>Warning</strong> Be very careful when you change the Default option because when you change it, you change the normal flow of the Order into WooCommerce system, you may encounter some consequences based on the new value you set',
                'options'     => $orderStatuses,
            ),
            'ipn_enable' => array(
                'title'       => __('Allow IPN events', 'PayTabs'),
                'type'        => 'checkbox',
                'description' => "<strong>$ipn_url</strong>"
                    . "<br>Copy the link provided and use it in the merchant's dashboard."
                    . "<br>Supported events: <strong>Capture (Full)</strong>, <strong>Void (Full)</strong>, <strong>Refund (Full & Partial)</strong>.",
                'required'    => false,
            ),
        );

        $this->form_fields = array_merge($fields, $addional_fields);
    }


    public function is_available()
    {
        if (is_add_payment_method_page()) {
            if (!$this->supports('add_payment_method')) {
                return false;
            }
        }

        return parent::is_available();
    }


    /**
     *  There are no payment fields for paytabs, but we want to show the description if set.
     **/
    function payment_fields()
    {
        if ($this->description) echo wpautop(wptexturize($this->description));

        if (!$this->supports('tokenization') || !$this->enable_tokenise) {
            return;
        }

        if (!is_checkout()) {
            return;
        }

        $this->tokenization_script();
        $this->saved_payment_methods();

        $has_subscription = class_exists('WC_Subscriptions_Cart') && WC_Subscriptions_Cart::cart_contains_subscription();
        if ($has_subscription) {
            echo wpautop('Will Save to Account');
        } else {
            $this->save_payment_method_checkbox();
        }
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
        return (bool) filter_input(INPUT_POST, $this->tokenise_param, FILTER_VALIDATE_BOOLEAN);
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
        } elseif ($this->is_frammed_page) {

            return array(
                'result'   => 'success',
                'redirect' => $order->get_checkout_payment_url(true) . "&t={$this->is_tokenise()}"
            );
        } else {
            $values = WooCommerce2 ? $this->prepareOrder2($order) : $this->prepareOrder($order);
        }

        $paypage = $_paytabsApi->create_pay_page($values);

        //

        $success = $paypage->success;
        $message = @$paypage->message;
        $is_redirect = @$paypage->is_redirect;
        $is_completed = @$paypage->is_completed;

        if ($success) {
            $this->set_handled($order_id, false);
            if ($is_completed) {
                return $this->validate_payment($paypage, $order, true, false);
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
            PaytabsHelper::log("Create PayPage failed, Order {$order_id}, [{$_logPaypage}], [{$_logParams}]", 3);

            $errorMessage = $message;

            wc_add_notice($errorMessage, 'error');
            return null;
        }
    }


    /**
     * return the last saved Token for the selected payment method
     */
    private function get_user_token($user_id)
    {
        /*
        $token_default = WC_Payment_Tokens::get_customer_default_token($user_id);
        if ($token_default) {
            return $token_default;
        }
        */

        $tokens = WC_Payment_Tokens::get_customer_tokens($user_id, $this->id);
        if ($tokens && count($tokens) > 0) {
            return end($tokens);
        }

        /*
        $tokens = WC_Payment_Tokens::get_customer_tokens($user_id);
        if ($tokens && count($tokens) > 0) {
            return end($tokens);
        }
        */

        // $tokens1 = WC_Payment_Tokens::get_order_tokens();

        return false;
    }


    private function pt_echo_animation($show = true)
    {
        if ($show) {
            $loaderPath = PAYTABS_PAYPAGE_IMAGES_URL . "logo-animation.gif";
            echo "<div id='pt_loader'><img src='{$loaderPath}' style='width: 150px; margin: auto' /></div>";
        } else {
            echo "<script>document.getElementById(\"pt_loader\").style.display=\"none\";</script>";
        }
    }

    function receipt_page($order_id)
    {
        $order = wc_get_order($order_id);

        $is_tokenize = (bool) filter_input(INPUT_GET, 't');
        $values = WooCommerce2 ? $this->prepareOrder2($order, $is_tokenize) : $this->prepareOrder($order, $is_tokenize);

        $_paytabsApi = PaytabsApi::getInstance($this->paytabs_endpoint, $this->merchant_id, $this->merchant_key);
        $paypage = $_paytabsApi->create_pay_page($values);

        //

        $success = $paypage->success;
        $message = @$paypage->message;

        if ($success) {
            $this->set_handled($order_id, false);

            $payment_url = $paypage->payment_url;

            if ($this->is_frammed_page) {
                $this->pt_echo_animation(true);

                echo "<iframe src='{$payment_url}' width='100%' height='auto' style='min-width: 400px; min-height: 700px; border: 0' onload='document.getElementById(\"pt_loader\").style.display=\"none\";' />";
            }
        } else {
            $_logPaypage = json_encode($paypage);
            $_logParams = json_encode($values);
            PaytabsHelper::log("Create PayPage failed, Order {$order_id}, [{$_logPaypage}], [{$_logParams}]", 3);

            $errorMessage = $message;

            echo "<h2>$errorMessage</h2>";
        }
    }


    public function scheduled_subscription_payment($amount_to_charge, $renewal_order)
    {
        $user_id = $renewal_order->get_user_id();
        $tokenObj = $this->get_user_token($user_id);

        if (!$tokenObj) {
            $renewal_order->add_order_note("Renewal failed [No Saved payment token found]");
            paytabs_error_log("Subscription renewal error: The User {$user_id} does not have saved Tokens.");
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
            $this->pt_set_tran_ref($renewal_order, PaytabsEnum::TRAN_TYPE_SALE, $transaction_id);
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

        PaytabsHelper::log("Refund request, Order {$order_id} - {$amount} {$currency}", 3);

        $_paytabsApi = PaytabsApi::getInstance($this->paytabs_endpoint, $this->merchant_id, $this->merchant_key);
        $refundRes = $_paytabsApi->request_followup($values);

        $tran_ref = @$refundRes->tran_ref;
        $success = $refundRes->success;
        $message = $refundRes->message;
        $pending_success = $refundRes->pending_success;

        PaytabsHelper::log("Refund request done, Order {$order_id} - {$success} {$message} {$tran_ref}", 3);

        if ($success) {
            $this->pt_set_tran_ref($order, PaytabsEnum::TRAN_TYPE_REFUND, $tran_ref);
            // $order->update_status('refunded', __('Payment Refunded: ', 'PayTabs'));
        } else if ($pending_success) {
            $order->update_status('on-hold', __('Payment Pending Refund: ', 'PayTabs'));
        }

        $order->add_order_note('Refund status: ' . $message, true);

        return $success;
    }


    public function process_capture($order_id)
    {
        global $woocommerce;

        $order = wc_get_order($order_id);

        $amount = $order->get_total();

        $transaction_id = $order->get_transaction_id();

        if (!$transaction_id) {
            return false;
        }

        // PT
        $currency = $order->get_currency();

        $payment_id = $this->getPaymentMethod($order);
        if ($payment_id != $this->id) {
            return;
        }

        $transaction_type = $this->pt_get_tran_type($order_id);

        if (!in_array(PaytabsEnum::TRAN_TYPE_AUTH, $transaction_type)) {
            // $order->add_order_note('Capture status: ' . "can't make capture on non Auth transaction", false);
            PaytabsHelper::log("Capture not required for non Auth transactions, {$order_id}", 3);
            return;
        }

        // Process Capture

        $reason = 'Admin request';

        $pt_capHolder = new PaytabsFollowupHolder();
        $pt_capHolder
            ->set02Transaction(PaytabsEnum::TRAN_TYPE_CAPTURE, PaytabsEnum::TRAN_CLASS_ECOM)
            ->set03Cart($order_id, $currency, $amount, $reason)
            ->set30TransactionInfo($transaction_id)
            ->set99PluginInfo('WooCommerce', $woocommerce->version, PAYTABS_PAYPAGE_VERSION);

        $values = $pt_capHolder->pt_build();

        PaytabsHelper::log("Capture request, Order {$order_id} - {$amount} {$currency}", 3);

        $_paytabsApi = PaytabsApi::getInstance($this->paytabs_endpoint, $this->merchant_id, $this->merchant_key);
        $capRes = $_paytabsApi->request_followup($values);

        $tran_ref = @$capRes->tran_ref;
        $success = $capRes->success;
        $message = $capRes->message;
        // $pending_success = $capRes->pending_success;

        PaytabsHelper::log("Capture request done, Order {$order_id} - {$success} {$message} {$tran_ref}", 3);

        if ($success) {
            $this->pt_set_tran_ref($order, PaytabsEnum::TRAN_TYPE_CAPTURE, $tran_ref);
            $order->set_transaction_id($tran_ref);
            $order->save();
        } else {
            PaytabsHelper::log("Capture failed, {$order_id} - {$message}", 3);
            $order->update_status('on-hold', __('Capture failed: ' . $message, 'PayTabs'));
        }

        $order->add_order_note('Capture status: ' . $message, true);

        return $success;
    }


    public function process_void($order_id)
    {
        global $woocommerce;

        $order = wc_get_order($order_id);

        $amount = $order->get_total();

        $transaction_id = $order->get_transaction_id();

        if (!$transaction_id) {
            return false;
        }

        // PT
        $currency = $order->get_currency();

        $payment_id = $this->getPaymentMethod($order);
        if ($payment_id != $this->id) {
            return;
        }

        $transaction_type = $this->pt_get_tran_type($order_id);

        if (!in_array(PaytabsEnum::TRAN_TYPE_AUTH, $transaction_type)) {
            // $order->add_order_note('Capture status: ' . "can't make capture on non Auth transaction", false);
            PaytabsHelper::log("Void not required for non Auth transactions, {$order_id}", 3);
            return;
        }

        // Process Capture

        $reason = 'Admin request';

        $pt_voidHolder = new PaytabsFollowupHolder();
        $pt_voidHolder
            ->set02Transaction(PaytabsEnum::TRAN_TYPE_VOID, PaytabsEnum::TRAN_CLASS_ECOM)
            ->set03Cart($order_id, $currency, $amount, $reason)
            ->set30TransactionInfo($transaction_id)
            ->set99PluginInfo('WooCommerce', $woocommerce->version, PAYTABS_PAYPAGE_VERSION);

        $values = $pt_voidHolder->pt_build();

        PaytabsHelper::log("Void request, Order {$order_id} - {$amount} {$currency}", 3);

        $_paytabsApi = PaytabsApi::getInstance($this->paytabs_endpoint, $this->merchant_id, $this->merchant_key);
        $voidRes = $_paytabsApi->request_followup($values);

        $tran_ref = @$voidRes->tran_ref;
        $success = $voidRes->success;
        $message = $voidRes->message;
        // $pending_success = $capRes->pending_success;

        PaytabsHelper::log("Void request done, Order {$order_id} - {$success} {$message} {$tran_ref}", 3);

        if ($success) {
            $this->pt_set_tran_ref($order, PaytabsEnum::TRAN_TYPE_VOID, $tran_ref);
            // $order->set_transaction_id($tran_ref);
            // $order->save();
        } else {
            PaytabsHelper::log("Void failed, {$order_id} - {$message}", 3);
            $order->update_status('on-hold', __('Void failed: ' . $message, 'PayTabs'));
        }

        $order->add_order_note('Void status: ' . $message, true);

        return $success;
    }

    public function return_response()
    {
        PaytabsHelper::log("Return fired", 3);
        $this->handle_response(false);
    }

    public function callback_response()
    {
        PaytabsHelper::log("Callback fired", 3);
        $this->handle_response(true);
    }

    public function ipn_response()
    {
        PaytabsHelper::log("IPN fired", 3);
        $this->handle_ipn();
    }

    //

    private function handle_ipn()
    {
        $response_data = PaytabsHelper::read_ipn_response();
        if (!$response_data) {
            return;
        }

        $orderId = @$response_data->cart_id;
        if (!$orderId) {
            return;
        }

        $order = wc_get_order($orderId);
        if (!$order || !is_a($order, 'WC_Order')) {
            return;
        }

        $payment_id = $this->getPaymentMethod($order);
        if ($payment_id != $this->id) {
            // return;
        }
        $payment_gateway = wc_get_payment_gateway_by_order($order);

        if (!$payment_gateway->ipn_enable) {
            PaytabsHelper::log("IPN handling is disabled, {$orderId}", 3);
            return;
        }

        $_paytabsApi = PaytabsApi::getInstance($payment_gateway->paytabs_endpoint, $payment_gateway->merchant_id, $payment_gateway->merchant_key);

        $response_data = $_paytabsApi->read_response(true);
        if (!$response_data) {
            return;
        }

        //

        $payment_gateway->pt_process_ipn($response_data);
    }


    public function pt_process_ipn($ipn_data)
    {
        $pt_success = $ipn_data->success;
        $pt_message = $ipn_data->message;
        $pt_token = @$ipn_data->token;

        $pt_tran_ref = $ipn_data->tran_ref;

        $pt_order_id = $ipn_data->cart_id;
        $pt_tran_total = $ipn_data->tran_total;
        $pt_tran_currency = $ipn_data->tran_currency;

        $pt_tran_type = strtolower($ipn_data->tran_type);

        //

        PaytabsHelper::log("IPN handling the Order {$pt_order_id} - {$pt_tran_type} : {$pt_tran_ref}", 3);

        //

        $order = wc_get_order($pt_order_id);
        // $payment_gateway = wc_get_payment_gateway_by_order($order);
        $ec_total = $order->get_total();
        $ec_currency = $order->get_currency();

        $same_currency = strcasecmp($ec_currency, $pt_tran_currency) == 0;
        $same_total = abs($ec_total - $pt_tran_total) < 0.001;
        $same_payment = $same_currency && $same_total;

        $ec_tran_ref = $order->get_transaction_id();
        $ec_tran_type = $this->pt_get_tran_type($pt_order_id);
        $ec_is_paid = $order->is_paid();
        // $order->maybe_set_date_paid();

        $is_registered = $this->pt_has_tran_ref($pt_order_id, $pt_tran_type, $pt_tran_ref);
        if ($is_registered) {
            PaytabsHelper::log("{$pt_tran_type} already registered, {$pt_order_id} - {$pt_message}", 3);
            return;
        }

        switch ($pt_tran_type) {
            case PaytabsEnum::TRAN_TYPE_SALE:
            case PaytabsEnum::TRAN_TYPE_AUTH:
            case PaytabsEnum::TRAN_TYPE_REGISTER:
                PaytabsHelper::log("IPN does not support creating new Order", 3);
                break;

            case PaytabsEnum::TRAN_TYPE_CAPTURE:
                if (!in_array(PaytabsEnum::TRAN_TYPE_AUTH, $ec_tran_type)) {
                    PaytabsHelper::log("Capture not required for non Auth transactions, {$pt_order_id}", 3);
                    return;
                }
                if ($pt_success) {
                    if ($same_payment) {
                        $this->pt_set_tran_ref($order, $pt_tran_type, $pt_tran_ref);

                        $order->set_transaction_id($pt_tran_ref);
                        $order->save();

                        $this->setNewStatus($order, true, $pt_tran_type, true);
                        PaytabsHelper::log("{$pt_tran_type} done, {$pt_order_id} - {$pt_tran_ref}", 3);
                    } else {
                        PaytabsHelper::log('Capture could not be registered, only Full & Same Capture allowed');
                    }
                } else {
                    PaytabsHelper::log("Capture failed, {$pt_order_id} - {$pt_message}", 3);
                    // $order->update_status('on-hold', __('Capture failed: ' . $pt_message, 'PayTabs'));
                    $this->setNewStatus($order, false, $pt_tran_type, true);
                }
                break;

            case PaytabsEnum::TRAN_TYPE_VOID:
            case PaytabsEnum::TRAN_TYPE_RELEASE:
                if (!in_array(PaytabsEnum::TRAN_TYPE_AUTH, $ec_tran_type)) {
                    // $order->add_order_note('Capture status: ' . "can't make Void on non Auth transaction", false);
                    PaytabsHelper::log("Void not allowed on non Auth transactions, {$pt_order_id}", 3);
                    return;
                }
                if ($pt_success) {
                    if ($same_payment) {
                        $this->pt_set_tran_ref($order, $pt_tran_type, $pt_tran_ref);

                        $this->setNewStatus($order, true, $pt_tran_type, true);
                        PaytabsHelper::log("{$pt_tran_type} done, {$pt_order_id} - {$pt_tran_ref}", 3);
                    } else {
                        PaytabsHelper::log('Void could not be registered, only Full & Same Void allowed');
                    }
                } else {
                    PaytabsHelper::log("Void failed, {$pt_order_id} - {$pt_message}", 3);
                    $order->update_status('on-hold', __('Void failed: ' . $pt_message, 'PayTabs'));
                }
                break;

            case PaytabsEnum::TRAN_TYPE_REFUND:
                if (!$pt_success) {
                    PaytabsHelper::log("Refund failed, {$pt_order_id} - {$pt_message}", 3);
                    return;
                }

                $refund = wc_create_refund([
                    'amount'         => $pt_tran_total,
                    'reason'         => 'PayTabs dashboard',
                    'order_id'       => $pt_order_id,
                    'refund_payment' => false,
                    // 'refund_id' => 0,
                    // 'line_items'   => $line_items,
                    // 'restock_items'  => false
                ]);

                if (!is_wp_error($refund)) {
                    PaytabsHelper::log("{$pt_tran_type} done, {$pt_order_id} - {$pt_tran_ref}", 3);
                    $this->pt_set_tran_ref($order, $pt_tran_type, $pt_tran_ref);
                    // $this->setNewStatus($order, true, $pt_tran_type, true);
                } else {
                    PaytabsHelper::log("Refund failed, {$pt_order_id} - {$refund->get_error_message()}", 3);
                }

                break;

            default:
                break;
        }

        return;
    }


    private function handle_response($is_ipn)
    {
        $_paytabsApi = PaytabsApi::getInstance($this->paytabs_endpoint, $this->merchant_id, $this->merchant_key);

        $response_data = $_paytabsApi->read_response($is_ipn);
        if (!$response_data) {
            return;
        }

        $orderId = @$response_data->reference_no;

        $handler = $is_ipn ? 'Callback' : 'Return';

        $order = wc_get_order($orderId);
        if ($order) {
            $payment_id = $this->getPaymentMethod($order);
            if ($payment_id == $this->id) {

                $pt_reach = false;
                if ($order->needs_payment()) {
                    if (!$this->pt_handled($order)) {
                        $pt_reach = true;
                        $this->validate_payment($response_data, $order, false, $is_ipn);
                    } else {
                        PaytabsHelper::log("{$handler} handling skipped for Order {$order->get_id()}", 3);
                    }
                } else {
                    PaytabsHelper::log("{$handler} failed, Order {$orderId}, No need for Payment", 3);
                }

                if (!$is_ipn && !$pt_reach) {
                    wp_redirect($order->get_checkout_order_received_url());
                }
            } else {
                PaytabsHelper::log("{$handler} failed, Order {$orderId}, Payment method mismatch", 3);
            }
        } else {
            $json_response = json_encode($response_data);
            PaytabsHelper::log("{$handler} failed, Order {$orderId}, payment response [{$json_response}]", 3);
        }
    }


    /**
     * In case you need a webhook, like IPN etc
     */
    public function callback($payment_reference, $order, $is_ipn)
    {
        $_paytabsApi = PaytabsApi::getInstance($this->paytabs_endpoint, $this->merchant_id, $this->merchant_key);
        $result = $_paytabsApi->verify_payment($payment_reference);
        // $valid_redirect = $_paytabsApi->is_valid_redirect($_POST);

        $this->validate_payment($result, $order, false, $is_ipn);
    }


    private function validate_payment($result, $order, $is_tokenise = false, $is_ipn = false)
    {
        $order_id = $order->get_id();
        $handler = $is_ipn ? 'Callback' : 'Return';

        $this->set_handled($order_id);
        PaytabsHelper::log("{$handler} handling the Order {$order_id}", 3);

        $success = $result->success;
        $response_status = $result->response_status;
        $message = $result->message;
        // $orderId = @$result->reference_no;
        $transaction_ref = @$result->transaction_id;
        $transaction_type = @$result->tran_type;
        if ($transaction_type) $transaction_type = strtolower($transaction_type);
        $token = @$result->token;

        //

        if ($success) {
            return $this->orderSuccess($order, $transaction_ref, $transaction_type, $token, $message, $is_tokenise, $is_ipn);
        } else {
            $_logVerify = json_encode($result);
            // $_data = WooCommerce2 ? $order->data : $order->get_data();
            // $_logOrder = (json_encode($_data));
            PaytabsHelper::log("{$handler} Validating failed, Order {$order_id}, response [{$_logVerify}]", 3);
            if ($result->is_on_hold) {
                $this->orderHoldOnReject($order, $message, $is_ipn);
            } else {
                $this->orderFailed($order, $message, $is_ipn);
            }
        }
    }

    private function pt_handled($order)
    {
        $pt_handled = (bool) get_post_meta($order->get_id(), $this::PT_HANDLED, true);
        return $pt_handled;
    }

    private function set_handled($order_id, $handled = true)
    {
        update_post_meta($order_id, $this::PT_HANDLED, $handled);
    }

    private function pt_set_tran_type($order, $transaction_type)
    {
        if (!$transaction_type) $transaction_type = $this->trans_type;
        update_post_meta($order->get_id(), $this::PT_TRAN_TYPE, $transaction_type);
    }

    private function pt_get_tran_type($order_id)
    {
        $transaction_type = get_post_meta($order_id, $this::PT_TRAN_TYPE);

        return $transaction_type;
    }

    private function pt_set_tran_ref($order, $transaction_type, $transaction_id)
    {
        if (!$transaction_type) $transaction_type = $this->trans_type;

        add_post_meta($order->get_id(), '_pt_tran_ref_' . $transaction_type, $transaction_id);
        $this->pt_set_tran_type($order, $transaction_type);
    }

    private function pt_get_tran_ref($order_id, $transaction_type)
    {
        $transaction_ref = get_post_meta($order_id, '_pt_tran_ref_' . $transaction_type);

        return $transaction_ref;
    }

    private function pt_has_tran_ref($order_id, $transaction_type, $tran_ref)
    {
        $transaction_refs = $this->pt_get_tran_ref($order_id, $transaction_type);

        foreach ($transaction_refs as $tran_ref_prev) {
            if ($tran_ref_prev == $tran_ref) {
                return true;
            }
        }
        return false;
    }


    /**
     * Payment successed => Order status change to success
     */
    private function orderSuccess($order, $transaction_id, $transaction_type, $token_str, $message, $is_tokenise, $is_ipn)
    {
        global $woocommerce;

        $order->payment_complete($transaction_id);
        // $order->reduce_order_stock();

        $this->pt_set_tran_ref($order, $transaction_type, $transaction_id);

        $this->setNewStatus($order, true, $transaction_type);

        $woocommerce->cart->empty_cart();

        $order->add_order_note($message, true);
        // wc_add_notice(__('Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.', 'woocommerce'), 'success');

        if ($token_str) {
            $this->saveToken($order, $token_str, $transaction_id);
        }

        if ($is_ipn) {
            return;
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


    private function saveToken($order, $token_str, $transaction_id)
    {
        $user_id = $order->get_user_id();

        $token = new WC_Payment_Token_Paytabs();
        $token->set_token($token_str);
        $token->set_tran_ref($transaction_id);
        $token->set_gateway_id($this->id);
        $token->set_user_id($user_id);
        $tokeId = $token->save();

        $order->add_payment_token($token);
        $order->save();
    }


    /**
     * Payment failed => Order status change to failed
     */
    private function orderFailed($order, $message, $is_ipn)
    {
        wc_add_notice($message, 'error');

        $order->update_status('failed', $message);

        $this->setNewStatus($order, false);

        if ($is_ipn) {
            return;
        }

        wp_redirect($order->get_checkout_payment_url());
    }


    private function orderHoldOnReject($order, $message, $is_ipn)
    {
        wc_add_notice($message, 'error');

        // $order->update_status('failed', $message);
        $order->update_status('wc-on-hold', 'Payment for this order is On-Hold, you can Capture/Decline manualy from your dashboard on PayTabs portal', true);

        // $this->setNewStatus($order, false);

        if ($is_ipn) {
            return;
        }

        wp_redirect($order->get_checkout_payment_url());
    }


    public function setNewStatus($order, $isSuccess, $transaction_type = null, $force_set = false)
    {
        if ($isSuccess) {
            if (!$transaction_type) $transaction_type = $this->trans_type;

            switch (strtolower($transaction_type)) {
                case PaytabsEnum::TRAN_TYPE_AUTH:
                    $configStatus = $this->order_status_auth_success;
                    $defaultStatus = 'wc-processing';
                    break;

                case PaytabsEnum::TRAN_TYPE_SALE:
                case PaytabsEnum::TRAN_TYPE_CAPTURE:
                    $configStatus = $this->order_status_success;
                    $defaultStatus = 'wc-processing';
                    break;

                case PaytabsEnum::TRAN_TYPE_VOID:
                    $configStatus = 'wc-cancelled';
                    $defaultStatus = 'wc-cancelled';
                    break;

                case PaytabsEnum::TRAN_TYPE_REFUND:
                    $configStatus = 'wc-refunded';
                    $defaultStatus = 'wc-refunded';
                    break;
            }
        } else {
            $configStatus = $this->order_status_failed;
            $defaultStatus = 'wc-failed';
        }

        $newStatus = ($configStatus == 'default') ? $defaultStatus : $configStatus;

        $isDefault = $newStatus == $defaultStatus;

        if (!$isDefault || $force_set) {
            $newMsg = "Order status changed as in the admin configuration!";
            $order->update_status($newStatus, $newMsg, true);
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
    private function prepareOrder($order, $isTokenize = false)
    {
        // PT
        global $woocommerce;

        // $order->add_order_note();

        $is_subscription = $this->has_subscription($order->get_id());
        $tokenise = $isTokenize || $this->is_tokenise() || $is_subscription;

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
        // $return_url = $order->get_checkout_payment_url(true);
        $return_url = add_query_arg('wc-api', 'wc_gateway_r_' . $this->id, home_url('/'));

        $callback_url = add_query_arg('wc-api', 'wc_gateway_' . $this->id, home_url('/'));

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

        $is_diff_shipping_address = (bool) filter_input(INPUT_POST, 'ship_to_different_address', FILTER_VALIDATE_BOOLEAN);
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
            ->set01PaymentCode($this->_code, $this->allow_associated_methods, $currency)
            ->set02Transaction($this->trans_type, PaytabsEnum::TRAN_CLASS_ECOM)
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
                $callback_url
            )
            ->set08Lang($lang)
            ->set09Framed($this->is_frammed_page, 'top')
            ->set10Tokenise($tokenise)
            ->set99PluginInfo('WooCommerce', $woocommerce->version, PAYTABS_PAYPAGE_VERSION);

        if ($this->_code == 'valu') {
            // $holder->set20ValuParams($this->valu_product_id, 0);
        }

        $post_arr = $holder->pt_build();

        return $post_arr;
    }


    /**
     * $this->prepareOrder which support WooCommerce version 2.x
     */
    private function prepareOrder2($order, $isTokenize = false)
    {
        // PT
        global $woocommerce;

        // $order->add_order_note();

        $is_subscription = $this->has_subscription($order->get_id());
        $tokenise = $isTokenize || $this->is_tokenise() || $is_subscription;

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
        $return_url = add_query_arg('wc-api', 'wc_gateway_r_' . $this->id, home_url('/'));

        $callback_url = add_query_arg('wc-api', 'wc_gateway_' . $this->id, home_url('/'));

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

        $is_diff_shipping_address = (bool) filter_input(INPUT_POST, 'ship_to_different_address', FILTER_VALIDATE_BOOLEAN);
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
            ->set02Transaction($this->trans_type, PaytabsEnum::TRAN_CLASS_ECOM)
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
                $callback_url
            )
            ->set08Lang($lang)
            ->set09Framed($this->is_frammed_page, 'top')
            ->set10Tokenise($tokenise)
            ->set99PluginInfo('WooCommerce', $woocommerce->version, PAYTABS_PAYPAGE_VERSION);

        if ($this->_code == 'valu') {
            // $holder->set20ValuParams($this->valu_product_id, 0);
        }

        $post_arr = $holder->pt_build();

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
            ->set20Token($tran_ref, $token)
            ->set99PluginInfo('WooCommerce', $woocommerce->version, PAYTABS_PAYPAGE_VERSION);

        if ($this->_code == 'valu') {
            // $holder->set20ValuParams($this->valu_product_id, 0);
        }

        $post_arr = $holder->pt_build();

        return $post_arr;
    }

    //

    private function getPaymentMethod($order)
    {
        return WooCommerce2 ? $order->payment_method : $order->get_payment_method();
    }
}
