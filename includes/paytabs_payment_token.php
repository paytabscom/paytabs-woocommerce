<?php

defined('PAYTABS_PAYPAGE_VERSION') or die;


class WC_Payment_Token_PayTabs extends WC_Payment_Token
{
    /** @protected string Token Type String */
    protected $type = 'PayTabs';


    protected $extra_data = array(
        'tran_ref' => '',
        'last4' => '',
        'gateway_id' => '',
        'expiry_year' => '',
        'expiry_month' => '',
        'payment_method_type' => '',
    );


    public function validate()
    {
        if (false === parent::validate()) {
            return false;
        }

        if (!$this->get_tran_ref()) { // || !$this->get_token()) {
            return false;
        }

        return true;
    }

    public function get_display_name($deprecated = '')
    {
        $str = substr($this->get_tran_ref(), -3);
        return "Pay using existing card (...$str)";
    }


    public function get_tran_ref($context = 'view')
    {
        return $this->get_prop('tran_ref', $context);
    }

    public function set_tran_ref($tran_ref)
    {
        $this->set_prop('tran_ref', $tran_ref);
    }

    /**
     * Set the last four digits.
     *
     * @since 4.0.0
     * @version 4.0.0
     * @param string $last4
     */
    public function set_last4($last4)
    {
        $this->set_prop('last4', $last4);
    }

    public function get_last4($context = 'view')
    {
        return $this->get_prop('last4', $context);
    }

    /**
     * Set Stripe payment method type.
     *
     * @param string $type Payment method type.
     */
    public function set_payment_method_type($type)
    {
        $this->set_prop('payment_method_type', $type);
    }

    /**
     * Returns Stripe payment method type.
     *
     * @param string $context What the value is for. Valid values are view and edit.
     * @return string $payment_method_type
     */
    public function get_payment_method_type($context = 'view')
    {
        return $this->get_prop('payment_method_type', $context);
    }


    /**
     * Set the card type (mastercard, visa, ...).
     *
     * @since 2.6.0
     * @param string $type Credit card type (mastercard, visa, ...).
     */
    public function set_card_type($type)
    {
        $this->set_prop('card_type', $type);
    }
    public function get_card_type($context = 'view')
    {
        return $this->get_prop('card_type', $context);
    }

    /**
     * Set the expiration year for the card (YYYY format).
     *
     * @since 2.6.0
     * @param string $year Credit card expiration year.
     */
    public function set_expiry_year($year)
    {
        $this->set_prop('expiry_year', $year);
    }
    public function get_expiry_year($context = 'view')
    {
        return $this->get_prop('expiry_year', $context);
    }

    /**
     * Set the expiration month for the card (formats into MM format).
     *
     * @since 2.6.0
     * @param string $month Credit card expiration month.
     */
    public function set_expiry_month($month)
    {
        $this->set_prop('expiry_month', str_pad($month, 2, '0', STR_PAD_LEFT));
    }
    public function get_expiry_month($context = 'view')
    {
        return $this->get_prop('expiry_month', $context);
    }

    public function get_gateway_id($context = 'view')
    {
        return $this->get_prop('gateway_id', $context);
    }
    public function set_gateway_id($gateway_id)
    {
        $this->set_prop('gateway_id', $gateway_id);
    }
}


class WC_Paytabs_Payment_Tokens extends WC_Payment_Token_PayTabs
{
    public function __construct()
    {
        add_filter('woocommerce_payment_methods_list_item', [$this, 'get_account_saved_payment_methods_list_item_paytabs'], 10, 2);
    }

    public function get_account_saved_payment_methods_list_item_paytabs($item, $payment_token)
    {
        if ('paytabs' === strtolower($payment_token->get_type())) {
            $item['method']['last4'] = $payment_token->get_last4();
            $item['method']['expire'] = $this->get_expiry_year(); //
            $item['method']['brand'] = isset($item['method']['gateway']) ? $item['method']['gateway'] : 'Paytabs,' . $this->get_display_name();
        }
        return $item;
    }
}

new WC_Paytabs_Payment_Tokens();
