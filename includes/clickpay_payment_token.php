<?php

defined('CLICKPAY_PAYPAGE_VERSION') or die;


class WC_Payment_Token_ClickPay extends WC_Payment_Token
{
    /** @protected string Token Type String */
    protected $type = 'ClickPay';


    protected $extra_data = array(
        'tran_ref' => '',
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
        return parent::get_display_name($deprecated);
    }


    public function get_tran_ref($context = 'view')
    {
        return $this->get_prop('tran_ref', $context);
    }

    public function set_tran_ref($tran_ref)
    {
        $this->set_prop('tran_ref', $tran_ref);
    }
}
