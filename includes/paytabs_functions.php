<?php

if (!defined('PAYTABS_PAYPAGE_VERSION')) {
    return;
}

function woocommerce_paytabs_missing_wc_notice()
{
    echo '<div class="error"><p><strong>PayTabs requires WooCommerce to be installed and active.</strong></p></div>';
}

function woocommerce_version_check($version = '3.0')
{
    global $woocommerce;
    if (version_compare($woocommerce->version, $version, ">=")) {
        return true;
    }

    return false;
}
