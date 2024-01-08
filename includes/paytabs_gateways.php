<?php

defined('PAYTABS_PAYPAGE_VERSION') or die;

class WC_Gateway_Paytabs_All extends WC_Gateway_Paytabs
{
    protected $_code = 'all';
    protected $_title = 'Online payments powered by PayTabs';
    protected $_description = 'PayTabs - All supported payment methods';

    protected $_icon = "paytabs.png";
}

class WC_Gateway_Paytabs_Creditcard extends WC_Gateway_Paytabs
{
    protected $_code = 'creditcard';
    protected $_title = 'PayTabs - CreditCard';
    protected $_description = 'PayTabs - CreditCard payment method';

    protected $_icon = "creditcard.svg";
}

class WC_Gateway_Paytabs_Mada extends WC_Gateway_Paytabs
{
    protected $_code = 'mada';
    protected $_title = 'PayTabs - mada';
    protected $_description = 'PayTabs - mada payment method';

    protected $_icon = "mada.svg";
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

    protected $_icon = "applepay.svg";
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

class WC_Gateway_Paytabs_Fawry extends WC_Gateway_Paytabs
{
    protected $_code = 'fawry';
    protected $_title = 'PayTabs - @Fawry';
    protected $_description = 'PayTabs - @Fawry payment method';
}

class WC_Gateway_Paytabs_Knpay extends WC_Gateway_Paytabs
{
    protected $_code = 'knet';
    protected $_title = 'PayTabs - KnPay';
    protected $_description = 'PayTabs - KnPay payment method';

    protected $_icon = "knet.svg";
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

        $this->form_fields['valu_widget_enable'] = [
            'title' => __('ValU widget', 'PayTabs'),
            'label' => __('Enable ValU widget.', 'PayTabs'),
            'description' => __('Show valU widget in product\'s details page.', 'PayTabs'),
            'type' => 'checkbox',
            'default' => 'no'
        ];
        $this->form_fields['valu_widget_static_content'] = [
            'title' => __('ValU widget, Static content', 'PayTabs'),
            'type' => 'checkbox',
            'label' => __('ValU widget, Static content.', 'PayTabs'),
            'description' => __('Display the static content in the widget, Otherwise call the API to fetch live content based on the price.', 'PayTabs'),
            'default' => 'no'
        ];
        $this->form_fields['valu_widget_phone_number'] = [
            'title' => __('ValU phone number', 'PayTabs'),
            'type' => 'text',
            'description' => __('Registered valU phone number.', 'PayTabs'),
            'default' => '',
            'desc_tip' => true,
        ];
        $this->form_fields['valu_widget_price_threshold'] = [
            'title' => __('ValU price threshold', 'PayTabs'),
            'type' => 'text',
            'description' => __('Display The widget if the product price higher than the current thershold.', 'PayTabs'),
            'default' => '1000',
            'desc_tip' => true,
        ];
    }

    public function getIconWidget()
    {
        $icon_name = 'valu_long.png';

        $iconPath = PAYTABS_PAYPAGE_DIR . "icons/{$icon_name}";
        $icon = '';
        if (file_exists($iconPath)) {
            $icon = PAYTABS_PAYPAGE_ICONS_URL . "{$icon_name}";
        }

        return $icon;
    }
}

class WC_Gateway_Paytabs_Meeza extends WC_Gateway_Paytabs
{
    protected $_code = 'meeza';
    protected $_title = 'PayTabs - Meeza';
    protected $_description = 'PayTabs - Meeza payment method';
}

class WC_Gateway_Paytabs_Meezaqr extends WC_Gateway_Paytabs
{
    protected $_code = 'meezaqr';
    protected $_title = 'PayTabs - Meeza QR';
    protected $_description = 'PayTabs - Meeza QR payment method';
}

class WC_Gateway_Paytabs_Unionpay extends WC_Gateway_Paytabs
{
    protected $_code = 'unionpay';
    protected $_title = 'PayTabs - UnionPay';
    protected $_description = 'PayTabs - UnionPay payment method';
}

class WC_Gateway_Paytabs_Samsungpay extends WC_Gateway_Paytabs
{
    protected $_code = 'samsungpay';
    protected $_title = 'PayTabs - SamsungPay';
    protected $_description = 'PayTabs - SamsungPay payment method';
}

class WC_Gateway_Paytabs_Aman extends WC_Gateway_Paytabs
{
    protected $_code = 'aman';
    protected $_title = 'PayTabs - Aman';
    protected $_description = 'PayTabs - Aman payment method';

    protected $_icon = "aman.svg";
}


class WC_Gateway_Paytabs_Urpay extends WC_Gateway_Paytabs
{
    protected $_code = 'urpay';
    protected $_title = 'PayTabs - UrPay';
    protected $_description = 'PayTabs - UrPay payment method';

    protected $_icon = "urpay.svg";
}


class WC_Gateway_Paytabs_Paypal extends WC_Gateway_Paytabs
{
    protected $_code = 'paypal';
    protected $_title = 'PayTabs - PayPal';
    protected $_description = 'PayTabs - PayPal payment method';

    protected $_icon = "paypal.svg";
}


class WC_Gateway_Paytabs_Installment extends WC_Gateway_Paytabs
{
    protected $_code = 'installment';
    protected $_title = 'PayTabs - Installment';
    protected $_description = 'PayTabs - Installment payment method';

    protected $_icon = "nbe-installment.png";
}


class WC_Gateway_Paytabs_Touchpoints extends WC_Gateway_Paytabs
{
    protected $_code = 'touchpoints';
    protected $_title = 'PayTabs - Touchpoints';
    protected $_description = 'PayTabs - ADCB Touchpoints payment method';

    protected $_icon = "touchpoints_adcb.svg";
}

class WC_Gateway_Paytabs_Forsa extends WC_Gateway_Paytabs
{
    protected $_code = 'forsa';
    protected $_title = 'PayTabs - Forsa';
    protected $_description = 'PayTabs - Forsa payment method';

    protected $_icon = "forsa.png";
}

class WC_Gateway_Paytabs_Tabby extends WC_Gateway_Paytabs
{
    protected $_code = 'tabby';
    protected $_title = 'PayTabs - Tabby';
    protected $_description = 'PayTabs - Tabby payment method';

    protected $_icon = "tabby.svg";
}

class WC_Gateway_Paytabs_Souhoola extends WC_Gateway_Paytabs
{
    protected $_code = 'souhoola';
    protected $_title = 'PayTabs - Souhoola';
    protected $_description = 'PayTabs - Souhoola payment method';

    protected $_icon = "souhoola.png";
}

class WC_Gateway_Paytabs_AmanInstallments extends WC_Gateway_Paytabs
{
    protected $_code = 'amaninstallments';
    protected $_title = 'PayTabs - Aman installments';
    protected $_description = 'PayTabs - Aman installments payment method';

    protected $_icon = "amaninstallments.svg";
}