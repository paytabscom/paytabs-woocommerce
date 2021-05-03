<?php

defined('CLICKPAY_PAYPAGE_VERSION') or die;

function woocommerce_clickpay_missing_wc_notice()
{
    echo '<div class="error"><p><strong>ClickPay requires WooCommerce to be installed and active.</strong></p></div>';
}

function woocommerce_clickpay_version_check($version = '3.0')
{
    global $woocommerce;
    if (version_compare($woocommerce->version, $version, ">=")) {
        return true;
    }

    return false;
}

function clickpay_error_log($message)
{
    $_prefix = date('c') . ' ClickPay: ';
    error_log($_prefix . $message . PHP_EOL, 3, CLICKPAY_DEBUG_FILE);
}
