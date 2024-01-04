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

function clickpay_error_log($message,  $severity)
{
    $severity_str = $severity == 1 ? 'Info' : ($severity == 2 ? 'Warning' : 'Error');

    $_prefix = date('c') . " ClickPay.{$severity_str}: ";
    error_log($_prefix . $message . PHP_EOL, 3, CLICKPAY_DEBUG_FILE);
}



function woocommerce_clickpay_htaccess_notice()
{
    echo '<div class="notice notice-warning is-dismissible"><p>Ensure that you allow "<strong>override all</strong>" in your server\'s configurations to enable the proper functionality of the .htaccess file.</p></div>';
}


function woocommerce_clickpay_check_log_permission()
{
    // Print message to the merchant to make sure allow the webserver setting.
    // add_action('admin_notices', 'woocommerce_clickpay_htaccess_notice');

    $permission =
        PHP_EOL .
        "<Files " . CLICKPAY_DEBUG_FILE_NAME . ">" . PHP_EOL .
        "  Order Allow,Deny" . PHP_EOL .
        "  Deny from all" . PHP_EOL .
        "</Files>";

    // prevent debug file from opening inside the browser
    if (!file_exists(CLICKPAY_HTACCESS_FILE)) {
        $myhtaccessfile = fopen(CLICKPAY_HTACCESS_FILE, "w");
        $res = fwrite($myhtaccessfile, $permission);
        fclose($myhtaccessfile);

        if ($res) {
            ClickpayHelper::log("Debug file secured.", 1);
        } else {
            ClickpayHelper::log("Could not write to .htaccess file.", 3);
        }
    } else {
        // Try to read the content online
        $url = CLICKPAY_DEBUG_FILE_URL;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, true);    // we want headers
        curl_setopt($ch, CURLOPT_NOBODY, true);    // we don't need body
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $output = curl_exec($ch);
        $error_num = curl_errno($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode === 403) {
            ClickpayHelper::log("Debug file already secured.", 1);
        } elseif ($error_num) {
            $output_err = curl_error($ch);
            ClickpayHelper::log("Checking .htaccess error: [{$output_err}].", 2);
        }  else {
            $htaccess_file_content = file_get_contents(CLICKPAY_HTACCESS_FILE);

            if (strpos($htaccess_file_content, CLICKPAY_DEBUG_FILE_NAME) !== false) {
                ClickpayHelper::log("Allow 'override all' into your webserver to enable the proper functioning of the .htaccess file.", 2);
            } else {
                $htaccessFile = CLICKPAY_HTACCESS_FILE;
                file_put_contents($htaccessFile, $permission, FILE_APPEND);

                ClickpayHelper::log("Debug file appended to htaccess.", 1);
            }
        }
    }
}