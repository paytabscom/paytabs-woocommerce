<?php

defined('PAYTABS_PAYPAGE_VERSION') or die;

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


    public function init_form_fields()
    {
        parent::init_form_fields();

        $this->form_fields['valu_product_id'] = array(
            'title'       => __('valU product ID', 'PayTabs'),
            'type'        => 'text',
            'description' => __('Please enter the product ID of your valU account.', 'PayTabs'),
            'default'     => '',
            'required'    => true
        );
    }
}
