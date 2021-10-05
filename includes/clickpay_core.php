<?php

/**
 * ClickPay v2 PHP SDK
 * Version: 2.3.0
 */
define('CLICKPAY_SDK_VERSION', '2.3.3');

abstract class ClickpayHelper
{
    static function paymentType($key)
    {
        return ClickpayApi::PAYMENT_TYPES[$key]['name'];
    }

    static function paymentAllowed($code, $currencyCode)
    {
        $row = null;
        foreach (ClickpayApi::PAYMENT_TYPES as $key => $value) {
            if ($value['name'] === $code) {
                $row = $value;
                break;
            }
        }
        if (!$row) {
            return false;
        }
        $list = $row['currencies'];
        if ($list == null) {
            return true;
        }

        $currencyCode = strtoupper($currencyCode);

        return in_array($currencyCode, $list);
    }

    static function isClickPayPayment($code)
    {
        foreach (ClickpayApi::PAYMENT_TYPES as $key => $value) {
            if ($value['name'] === $code) {
                return true;
            }
        }
        return false;
    }

     static function isCardPayment($code, $is_international = false)
    {
        $group = $is_international ? ClickpayApi::GROUP_CARDS_INTERNATIONAL : ClickpayApi::GROUP_CARDS;

        foreach (ClickpayApi::PAYMENT_TYPES as $key => $value) {
            if ($value['name'] === $code) {
                return in_array($group, $value['groups']);
            }
        }
        return false;
    }

    static function getCardPayments($international_only = false , $currency = null)
    {
        $methods = [];

        $group = $international_only ? ClickpayApi::GROUP_CARDS_INTERNATIONAL : ClickpayApi::GROUP_CARDS;

        foreach (ClickpayApi::PAYMENT_TYPES as $key => $value) {
           if ($currency) {
                    if ($value['currencies'] == null || in_array($currency, $value['currencies'])) {
                        $methods[] = $value['name'];
                    }
                } else {
                    $methods[] = $value['name'];
                }
        }
        return $methods;
    }

     static function supportAuthCapture($code)
    {
        foreach (ClickpayApi::PAYMENT_TYPES as $key => $value) {
            if ($value['name'] === $code) {
                return in_array(ClickpayApi::GROUP_AUTH_CAPTURE, $value['groups']);
            }
        }
        return false;
    }

    static function supportTokenization($code)
    {
        foreach (ClickpayApi::PAYMENT_TYPES as $key => $value) {
            if ($value['name'] === $code) {
                return in_array(ClickpayApi::GROUP_TOKENIZE, $value['groups']);
            }
        }
        return false;
    }


    /**
     * @return the first non-empty var from the vars list
     * @return null if all params are empty
     */
    public static function getNonEmpty(...$vars)
    {
        foreach ($vars as $var) {
            if (!empty($var)) return $var;
        }
        return null;
    }

    /**
     * convert non-english digits to English
     * used for fileds that accepts only English digits like: "postal_code"
     */
    public static function convertAr2En($string)
    {
        $nonEnglish = [
            // Arabic
            [
                '٠',
                '١',
                '٢',
                '٣',
                '٤',
                '٥',
                '٦',
                '٧',
                '٨',
                '٩'
            ],
            // Persian
            [
                '۰',
                '۱',
                '۲',
                '۳',
                '۴',
                '۵',
                '۶',
                '۷',
                '۸',
                '۹'
            ]
        ];

        $num = range(0, 9);

        $englishNumbersOnly = $string;
        foreach ($nonEnglish as $oldNum) {
            $englishNumbersOnly = str_replace($oldNum, $num, $englishNumbersOnly);
        }

        return $englishNumbersOnly;
    }

    /**
     * check Strings that require to be a valid Word, not [. (dot) or digits ...]
     * if the parameter is not a valid "Word", convert it to "NA"
     */
    public static function pt_fillIfEmpty(&$string)
    {
        if (empty(preg_replace('/[\W]/', '', $string))) {
            $string .= 'NA';
        }
    }

    static function pt_fillIP(&$string)
    {
        $string = $_SERVER['REMOTE_ADDR'];
    }

    public static function log($msg, $severity = 1)
    {
        try {
            clickpay_error_log($msg, $severity);
        } catch (\Throwable $th) {
            try {
                $_prefix = date('c') . ' ClickPay: ';
                $_msg = ($_prefix . $msg . PHP_EOL);
                file_put_contents('debug_clickpay.log', $_msg, FILE_APPEND);
            } catch (\Throwable $th) {
                // var_export($th);
            }
        }
    }

    static function getTokenInfo($return_values)
    {
        $fields = [
            'pt_token',
            'pt_customer_email',
            'pt_customer_password'
        ];

        $tokenInfo = [];

        foreach ($fields as $field) {
            if (!isset($return_values[$field])) return false;
            $tokenInfo[$field] = $return_values[$field];
        }

        return $tokenInfo;
    }
}


/**
 * @abstract class: Enum for static values of ClickPay requests
 */
abstract class ClickpayEnum
{
    const TRAN_TYPE_AUTH    = 'auth';
    const TRAN_TYPE_CAPTURE = 'capture';
    const TRAN_TYPE_SALE    = 'sale';
    const TRAN_TYPE_REGISTER = 'register';

    const TRAN_TYPE_VOID    = 'void';
    const TRAN_TYPE_REFUND  = 'refund';

    //

    const TRAN_CLASS_ECOM = 'ecom';
    const TRAN_CLASS_MOTO = 'moto';
    const TRAN_CLASS_RECURRING = 'recurring';

    const PP_ERR_DUPLICATE = 4;
    //

    static function TranIsAuth($tran_type)
    {
        return strcasecmp($tran_type, ClickpayEnum::TRAN_TYPE_AUTH) == 0;
    }

    static function TranIsSale($tran_type)
    {
        return strcasecmp($tran_type, ClickpayEnum::TRAN_TYPE_SALE) == 0;
    }

    static function TranIsRegister($tran_type)
    {
        return strcasecmp($tran_type, ClickpayEnum::TRAN_TYPE_REGISTER) == 0;
    }

    static function PPIsDuplicate($paypage)
    {
        $err_code = @$paypage->code;
        return $err_code == ClickpayEnum::PP_ERR_DUPLICATE;
    }
}


/**
 * Holder class: Holds & Generates the parameters array that pass to ClickPay' API
 */
class ClickpayHolder
{
    /**
     * tran_type
     * tran_class
     */
    private $transaction;

    /**
     * cart_id
     * cart_currency
     * cart_amount
     * cart_descriptions
     */
    private $cart;

    //


    /**
     * @return array
     */
    public function pt_build()
    {
        $all = array_merge(
            $this->transaction,
            $this->cart
        );

        return $all;
    }

    protected function pt_merges(&$all, ...$arrays)
    {
        foreach ($arrays as $array) {
            if ($array) {
                $all = array_merge($all, $array);
            }
        }
    }

    //

    public function set02Transaction($tran_type, $tran_class = ClickpayEnum::TRAN_CLASS_ECOM)
    {
        $this->transaction = [
            'tran_type' => $tran_type,
            'tran_class' => $tran_class,
        ];

        return $this;
    }

    public function set03Cart($cart_id, $currency, $amount, $cart_description)
    {
        $this->cart = [
            'cart_id'          => "$cart_id",
            'cart_currency'    => "$currency",
            'cart_amount'      => (float) $amount,
            'cart_description' => $cart_description,
        ];

        return $this;
    }
}


/**
 * Holder class, Inherit class ClickpayHolder
 * Holds & Generates the parameters array that pass to Clickpay' API
 */
class ClickpayRequestHolder extends ClickpayHolder
{
    /**
     * payment_type
     */
    private $payment_code;

    /**
     * name
     * email
     * phone
     * street1
     * city
     * state
     * country
     * zip
     * ip
     */
    private $customer_details;

    /**
     * name
     * email
     * phone
     * street1
     * city
     * state
     * country
     * zip
     * ip
     */
    private $shipping_details;

    /**
     * hide_shipping
     */
    private $hide_shipping;

    /**
     * pan
     * expiry_month
     * expiry_year
     * cvv
     */
    private $card_details;

    /**
     * return
     * callback
     */
    private $urls;

    /**
     * paypage_lang
     */
    private $lang;

    /**
     * framed
     */
    private $framed;

    /**
     * tokenise
     * show_save_card
     */
    private $tokenise;

    /**
     * cart_name
     * cart_version
     * plugin_version
     */
    private $plugin_info;


    //

    /**
     * @return array
     */
    public function pt_build()
    {
        $all = parent::pt_build();

        $this->pt_merges(
            $all,
            $this->payment_code,
            $this->urls,
            $this->customer_details,
            $this->shipping_details,
            $this->hide_shipping,
            $this->lang,
            $this->framed,
            $this->tokenise,
            $this->plugin_info
        );

        return $all;
    }


    private function setCustomerDetails($name, $email, $phone, $address, $city, $state, $country, $zip, $ip)
    {
        // ClickpayHelper::pt_fillIfEmpty($name);
        // $this->_fill($address, 'NA');

        // ClickpayHelper::pt_fillIfEmpty($city);

        // $this->_fill($state, $city, 'NA');

        if ($zip) {
            $zip = ClickpayHelper::convertAr2En($zip);
        }

        if (!$ip) {
            ClickpayHelper::pt_fillIP($ip);
        }

        //

        $info =  [
            'name'    => $name,
            'email'   => $email,
            'phone'   => $phone,
            'street1' => $address,
            'city'    => $city,
            'state'   => $state,
            'country' => $country,
            'zip'     => $zip,
            'ip'      => $ip
        ];

        return $info;
    }

    //

    public function set01PaymentCode($code, $allow_associated_methods = true, $currency = null)
    {
        $codes = [$code];

        if (ClickpayHelper::isCardPayment($code)) {
            if ($allow_associated_methods) {
                if (ClickpayHelper::isCardPayment($code, true)) {
                    $other_cards = ClickpayHelper::getCardPayments(false, $currency);
                } else {
                    $other_cards = ClickpayHelper::getCardPayments(true, $currency);
                }
                $codes = array_unique(array_merge($other_cards, $codes));
            }
        }

        // 'creditcard' => ['creditcard', 'mada', 'omannet', 'meeza']

        $this->payment_code = ['payment_methods' => $codes];

        return $this;
    }


    public function set04CustomerDetails($name, $email, $phone, $address, $city, $state, $country, $zip, $ip)
    {
        $infos = $this->setCustomerDetails($name, $email, $phone, $address, $city, $state, $country, $zip, $ip);

        //

        $this->customer_details = [
            'customer_details' => $infos
        ];

        return $this;
    }

    public function set05ShippingDetails($same_as_billing, $name = null, $email = null, $phone = null, $address = null, $city = null, $state = null, $country = null, $zip = null, $ip = null)
    {
        $infos = $same_as_billing
            ? $this->customer_details['customer_details']
            : $this->setCustomerDetails($name, $email, $phone, $address, $city, $state, $country, $zip, $ip);

        //

        $this->shipping_details = [
            'shipping_details' => $infos
        ];

        return $this;
    }

    public function set06HideShipping($on = false)
    {
        $this->hide_shipping = [
            'hide_shipping' => $on,
        ];

        return $this;
    }

    public function set07URLs($return_url, $callback_url)
    {
        $this->urls = [
            'return'   => $return_url,
            'callback' => $callback_url,
        ];

        return $this;
    }

    public function set08Lang($lang_code)
    {
        $this->lang = [
            'paypage_lang' => $lang_code
        ];

        return $this;
    }

    /**
     * @param string $redirect_target "parent" or "top" or "iframe"
     */
    public function set09Framed($on = false, $redirect_target = 'iframe')
    {
        $this->framed = [
            'framed' => $on,
            'framed_return_parent' => $redirect_target == 'parent',
            'framed_return_top' => $redirect_target == 'top'
        ];

        return $this;
    }

    /**
     * @param int $token_format integer between 2 and 6, Set the Token format
     * @param bool $optional Display the save card option on the payment page
     */
    public function set10Tokenise($on = false, $token_format = 2, $optional = false)
    {
        if ($on) {
            $this->tokenise = [
                'tokenise' => $token_format,
                'show_save_card' => $optional
            ];
        }

        return $this;
    }


    public function set99PluginInfo($platform_name, $platform_version, $plugin_version = null)
    {
        if (!$plugin_version) {
            $plugin_version = CLICKPAY_SDK_VERSION;
        }

        $this->plugin_info = [
            'cart_name'    => $platform_name,
            'cart_version' => $platform_version,
            'plugin_version' => $plugin_version,
        ];

        return $this;
    }
}


/**
 * Holder class, Inherit class ClickpayHolder
 * Holds & Generates the parameters array for the Tokenised payments
 */
class ClickpayTokenHolder extends ClickpayHolder
{
    /**
     * token
     * tran_ref
     */
    private $token_info;


    public function set20Token($tran_ref, $token = null)
    {
        $this->token_info = [
            'tran_ref' => $tran_ref
        ];

        if ($token) {
            $this->token_info['token'] = $token;
        }

        return $this;
    }

    public function pt_build()
    {
        $all = parent::pt_build();

        $all = array_merge($all, $this->token_info);

        return $all;
    }
}


/**
 * Holder class, Inherit class ClickpayHolder
 * Holder & Generates the parameters array for the Followup requests
 * Followup requests:
 * - Capture (follows Auth)
 * - Void    (follows Auth)
 * - Refund  (follows Capture or Sale)
 */
class ClickpayFollowupHolder extends ClickpayHolder
{
    /**
     * transaction_id
     */
    private $transaction_id;

    //

    /**
     * @return array
     */
    public function pt_build()
    {
        $all = parent::pt_build();

        $all = array_merge($all, $this->transaction_id);

        return $all;
    }

    //

    public function set30TransactionInfo($transaction_id)
    {
        $this->transaction_id = [
            'tran_ref' => $transaction_id,
        ];

        return $this;
    }
}


/**
 * API class which contacts ClickPay server's API
 */
class ClickpayApi
{

    const GROUP_CARDS = 'cards';
    const GROUP_CARDS_INTERNATIONAL = 'cards_international';
    const GROUP_TOKENIZE = 'tokenise';
    const GROUP_AUTH_CAPTURE = 'auth_capture';

    const PAYMENT_TYPES = [
        '0'  => ['name' => 'all', 'title' => 'ClickPay - All', 'currencies' => null, 'groups' => [ClickpayApi::GROUP_TOKENIZE, ClickpayApi::GROUP_AUTH_CAPTURE]]],
        '1'  => ['name' => 'stcpay', 'title' => 'ClickPay - StcPay', 'currencies' => ['SAR'], 'groups' => []],
        '2'  => ['name' => 'stcpayqr', 'title' => 'ClickPay - StcPay(QR)', 'currencies' => ['SAR'], 'groups' => []],
        '3'  => ['name' => 'applepay', 'title' => 'ClickPay - ApplePay', 'currencies' => ['AED', 'SAR'], 'groups' => [ClickpayApi::GROUP_TOKENIZE, ClickpayApi::GROUP_AUTH_CAPTURE]],
        '4'  => ['name' => 'omannet', 'title' => 'ClickPay - OmanNet', 'currencies' => ['OMR'], 'groups' => [ClickpayApi::GROUP_TOKENIZE, ClickpayApi::GROUP_CARDS]],
        '5'  => ['name' => 'mada', 'title' => 'ClickPay - Mada', 'currencies' => ['SAR'], 'groups' => [ClickpayApi::GROUP_TOKENIZE, ClickpayApi::GROUP_CARDS, ClickpayApi::GROUP_AUTH_CAPTURE]],
        '6'  => ['name' => 'creditcard', 'title' => 'ClickPay - CreditCard', 'currencies' => null, 'groups' => [ClickpayApi::GROUP_TOKENIZE, ClickpayApi::GROUP_CARDS, ClickpayApi::GROUP_CARDS_INTERNATIONAL, ClickpayApi::GROUP_AUTH_CAPTURE]],
        '7'  => ['name' => 'sadad', 'title' => 'ClickPay - Sadad', 'currencies' => ['SAR'], 'groups' => []],
        '8'  => ['name' => 'fawry', 'title' => 'ClickPay - @Fawry', 'currencies' => ['EGP'], 'groups' => []],
        '9'  => ['name' => 'knet', 'title' => 'ClickPay - KnPay', 'currencies' => ['KWD'], 'groups' => [ClickpayApi::GROUP_CARDS]],
        '10' => ['name' => 'amex', 'title' => 'ClickPay - Amex', 'currencies' => ['AED', 'SAR'], 'groups' => [ClickpayApi::GROUP_CARDS, ClickpayApi::GROUP_CARDS_INTERNATIONAL, ClickpayApi::GROUP_AUTH_CAPTURE]],
        '11' => ['name' => 'valu', 'title' => 'ClickPay - valU', 'currencies' => ['EGP'], 'groups' => []],
        '12' => ['name' => 'meeza', 'title' => 'ClickPay - Meeza', 'currencies' => ['EGP'], 'groups' => [ClickpayApi::GROUP_CARDS, ClickpayApi::GROUP_AUTH_CAPTURE]],
        '13' => ['name' => 'meezaqr', 'title' => 'ClickPay - Meeza (QR)', 'currencies' => ['EGP'], 'groups' => []],
        '14' => ['name' => 'unionpay', 'title' => 'ClickPay - UnionPay', 'currencies' => ['AED'], 'groups' => [], ClickpayApi::GROUP_AUTH_CAPTURE],
        '15' => ['name' => 'samsungpay', 'title' => 'ClickPay - SamsungPay', 'currencies' => ['AED', 'SAR'], 'groups' => []],
    ];
    const BASE_URLS = [
        'SAU' => [
            'title' => 'Saudi Arabia',
            'endpoint' => 'https://secure.clickpay.com.sa/'
        ]
    ];


    const URL_REQUEST = 'payment/request';
    const URL_QUERY   = 'payment/query';

    const URL_TOKEN_QUERY  = 'payment/token';
    const URL_TOKEN_DELETE = 'payment/token/delete';

    //

    private $base_url;
    private $profile_id;
    private $server_key;

    //

    private static $instance = null;

    //

    public static function getEndpoints()
    {
        $endpoints = [];
        foreach (ClickpayApi::BASE_URLS as $key => $value) {
            $endpoints[$key] = $value['title'];
        }
        return $endpoints;
    }

    public static function getInstance($region, $merchant_id, $key)
    {
        if (self::$instance == null) {
            self::$instance = new ClickpayApi($region, $merchant_id, $key);
        }

        // self::$instance->setAuth($merchant_email, $secret_key);

        return self::$instance;
    }

    private function __construct($region, $profile_id, $server_key)
    {
        $this->base_url = self::BASE_URLS[$region]['endpoint'];
        $this->setAuth($profile_id, $server_key);
    }

    private function setAuth($profile_id, $server_key)
    {
        $this->profile_id = $profile_id;
        $this->server_key = $server_key;
    }


    /** start: API calls */

    function create_pay_page($values)
    {
        // $serverIP = getHostByName(getHostName());
        // $values['ip_merchant'] = ClickpayHelper::getNonEmpty($serverIP, $_SERVER['SERVER_ADDR'], 'NA');

         $isTokenize = $values['tran_class'] == PaytabsEnum::TRAN_CLASS_RECURRING;

        $response = $this->sendRequest(self::URL_REQUEST, $values);

        $res = json_decode($response);
        $paypage = $isTokenize ? $this->enhanceTokenization($res) : $this->enhance($res);

        return $paypage;
    }

    function verify_payment($tran_reference)
    {
        $values['tran_ref'] = $tran_reference;
        $verify = json_decode($this->sendRequest(self::URL_QUERY, $values));

        $verify = $this->enhanceVerify($verify);

        return $verify;
    }

    function request_followup($values)
    {
        $res = json_decode($this->sendRequest(self::URL_REQUEST, $values));
        $refund = $this->enhanceRefund($res);

        return $refund;
    }

    function token_query($token)
    {
        $values = ['token' => $token];
        $res = json_decode($this->sendRequest(self::URL_TOKEN_QUERY, $values));

        return $res;
    }

    function token_delete($token)
    {
        $values = ['token' => $token];
        $res = json_decode($this->sendRequest(self::URL_TOKEN_DELETE, $values));

        return $res;
    }

    //

    function is_valid_redirect($post_values)
    {
        $serverKey = $this->server_key;

        // Request body include a signature post Form URL encoded field
        // 'signature' (hexadecimal encoding for hmac of sorted post form fields)
        $requestSignature = $post_values["signature"];
        unset($post_values["signature"]);
        $fields = array_filter($post_values);

        // Sort form fields
        ksort($fields);

        // Generate URL-encoded query string of Post fields except signature field.
        $query = http_build_query($fields);

        $signature = hash_hmac('sha256', $query, $serverKey);
        if (hash_equals($signature, $requestSignature) === TRUE) {
            // VALID Redirect
            return true;
        } else {
            // INVALID Redirect
            return false;
        }
    }

    function is_valid_ipn($data, $signature, $serverKey = false)
    {
        $server_key = $serverKey ?? $this->server_key;

        return $this->is_genuine($data, $signature, $server_key);
    }



    /** end: API calls */


    /** start: Local calls */

    /**
     *
     */
    private function enhance($paypage)
    {
        $_paypage = $paypage;

        if (!$paypage) {
            $_paypage = new stdClass();
            $_paypage->success = false;
            $_paypage->message = 'Create ClickPay payment failed';
        } else {
            $_paypage->success = isset($paypage->tran_ref, $paypage->redirect_url) && !empty($paypage->redirect_url);

            $_paypage->payment_url = @$paypage->redirect_url;
        }

        return $_paypage;
    }

    private function enhanceVerify($verify)
    {
        $_verify = $verify;

        if (!$verify) {
            $_verify = new stdClass();
            $_verify->success = false;
            $_verify->message = 'Verifying ClickPay payment failed';
        } else if (isset($verify->code, $verify->message)) {
            $_verify->success = false;
        } else {
            if (isset($verify->payment_result)) {
                $_verify->success = $verify->payment_result->response_status == "A";
            } else {
                $_verify->success = false;
            }
            $_verify->message = $verify->payment_result->response_message;
        }

        $_verify->reference_no = @$verify->cart_id;
        $_verify->transaction_id = @$verify->tran_ref;

        return $_verify;
    }

    private function enhanceRefund($refund)
    {
        $_refund = $refund;

        if (!$refund) {
            $_refund = new stdClass();
            $_refund->success = false;
            $_refund->message = 'Verifying ClickPay Refund failed';
        } else {
            if (isset($refund->payment_result)) {
                $_refund->success = $refund->payment_result->response_status == "A";
                $_refund->message = $refund->payment_result->response_message;
            } else {
                $_refund->success = false;
            }
            $_refund->pending_success = false;
        }

        return $_refund;
    }

    private function enhanceTokenization($paypage)
    {
        $_paypage = $paypage;

        if (!$paypage) {
            $_paypage = new stdClass();
            $_paypage->success = false;
            $_paypage->message = 'Create ClickPay tokenization payment failed';
        } else {
            $is_redirect = isset($paypage->tran_ref, $paypage->redirect_url) && !empty($paypage->redirect_url);
            $is_completed = isset($paypage->payment_result);

            if ($is_redirect) {
                $_paypage->success = true;
                $_paypage->payment_url = $paypage->redirect_url;
            } else if ($is_completed) {
                $_paypage = $this->enhanceVerify($paypage);
            } else {
                $_paypage = $this->enhance($paypage);
            }

            $_paypage->is_redirect = $is_redirect;
            $_paypage->is_completed = $is_completed;
        }

        return $_paypage;
    }

    /** end: Local calls */

    private function sendRequest($request_url, $values)
    {
        $auth_key = $this->server_key;
        $gateway_url = $this->base_url . $request_url;

        $headers = [
            'Content-Type: application/json',
            "Authorization: {$auth_key}"
        ];

        $values['profile_id'] = (int) $this->profile_id;
        $post_params = json_encode($values);

        $ch = @curl_init();
        @curl_setopt($ch, CURLOPT_URL, $gateway_url);
        @curl_setopt($ch, CURLOPT_POST, true);
        @curl_setopt($ch, CURLOPT_POSTFIELDS, $post_params);
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        @curl_setopt($ch, CURLOPT_HEADER, false);
        @curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        @curl_setopt($ch, CURLOPT_VERBOSE, true);
        // @curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $result = @curl_exec($ch);
        if (!$result) {
            die(curl_error($ch));
        }
        @curl_close($ch);

        return $result;
    }
}
