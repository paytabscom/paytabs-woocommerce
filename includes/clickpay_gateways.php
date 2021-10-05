<?php

defined('CLICKPAY_PAYPAGE_VERSION') or die;

class WC_Gateway_Clickpay_All extends WC_Gateway_Clickpay
{
    protected $_code = 'all';
    protected $_title = 'Online payments powered by ClickPay';
    protected $_description = 'ClickPay - All supported payment methods';

    protected $_icon = "clickpay.png";
}

class WC_Gateway_Clickpay_Creditcard extends WC_Gateway_Clickpay
{
    protected $_code = 'creditcard';
    protected $_title = 'ClickPay - CreditCard';
    protected $_description = 'ClickPay - CreditCard payment method';

    protected $_icon = "creditcard.svg";
}

class WC_Gateway_Clickpay_Mada extends WC_Gateway_Clickpay
{
    protected $_code = 'mada';
    protected $_title = 'ClickPay - Mada';
    protected $_description = 'ClickPay - Mada payment method';

    protected $_icon = "mada.svg";
}

class WC_Gateway_Clickpay_Stcpay extends WC_Gateway_Clickpay
{
    protected $_code = 'stcpay';
    protected $_title = 'ClickPay - StcPay';
    protected $_description = 'ClickPay - StcPay payment method';
}

class WC_Gateway_Clickpay_Stcpayqr extends WC_Gateway_Clickpay
{
    protected $_code = 'stcpayqr';
    protected $_title = 'ClickPay - StcPayQR';
    protected $_description = 'ClickPay - StcPayQR payment method';
}

class WC_Gateway_Clickpay_Applepay extends WC_Gateway_Clickpay
{
    protected $_code = 'applepay';
    protected $_title = 'ClickPay - ApplePay';
    protected $_description = 'ClickPay - ApplePay payment method';
}

class WC_Gateway_Clickpay_Sadad extends WC_Gateway_Clickpay
{
    protected $_code = 'sadad';
    protected $_title = 'ClickPay - Sadad';
    protected $_description = 'ClickPay - Sadad payment method';
}

class WC_Gateway_Clickpay_Amex extends WC_Gateway_Clickpay
{
    protected $_code = 'amex';
    protected $_title = 'ClickPay - Amex';
    protected $_description = 'ClickPay - Amex payment method';
}

