<?php

defined('PAYTABS_PAYPAGE_VERSION') or die;

function woocommerce_paytabs_missing_wc_notice()
{
    echo '<div class="error"><p><strong>PayTabs requires WooCommerce to be installed and active.</strong></p></div>';
}

function woocommerce_paytabs_version_check($version = '3.0')
{
    global $woocommerce;
    if (version_compare($woocommerce->version, $version, ">=")) {
        return true;
    }

    return false;
}

function paytabs_error_log($message, $severity)
{
    $severity_str = $severity == 1 ? 'Info' : ($severity == 2 ? 'Warning' : 'Error');

    $_prefix = date('c') . " PayTabs.{$severity_str}: ";
    error_log($_prefix . $message . PHP_EOL, 3, PAYTABS_DEBUG_FILE);
}


