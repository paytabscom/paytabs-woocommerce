<?php

defined('PAYTABS_PAYPAGE_VERSION') or die;


class WC_Payment_Token_PayTabs extends WC_Payment_Token
{
    /** @protected string Token Type String */
    protected $type = 'PayTabs';


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
        $str = mb_strimwidth($this->get_tran_ref(), -3, 3, '');
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
}
