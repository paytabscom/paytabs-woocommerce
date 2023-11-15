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

function woocommerce_paytabs_htaccess_notice()
{
    echo '<div class="notice notice-warning is-dismissible"><p>Ensure that you allow "<strong>override all</strong>" in your server\'s configurations to enable the proper functionality of the .htaccess file.</p></div>';
}


function woocommerce_paytabs_check_log_permission()
{
    // Print message to the merchant to make sure allow the webserver setting.
    add_action('admin_notices', 'woocommerce_paytabs_htaccess_notice');

    $permission = "<Files " . PAYTABS_DEBUG_FILE_NAME . ">
      Order Allow,Deny
      Deny from all
    </Files>";
    $htaccess_file_content = file_get_contents(PAYTABS_HTACCESS_FILE);

    // prevent debug file from opening inside the browser
    if (!file_exists(PAYTABS_HTACCESS_FILE)) {
        $myhtaccessfile = fopen(PAYTABS_HTACCESS_FILE, "w");
        $res = fwrite($myhtaccessfile, $permission);
        fclose($myhtaccessfile);

        if ($res) {
            PaytabsHelper::log("Debug file secured.", 1);
        } else {
            PaytabsHelper::log("Could not write to .htaccess file.", 3);
        }
    } else {
        // Try to read the content online
        $url = PAYTABS_DEBUG_FILE_URL;
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
            PaytabsHelper::log("Debug file already secured.", 1);
        } elseif ($error_num) {
            $output_err = curl_error($ch);
            PaytabsHelper::log("Checking .htaccess error: [{$output_err}].", 2);
        } elseif (strpos($htaccess_file_content, PAYTABS_DEBUG_FILE_NAME) !== false) {
            PaytabsHelper::log("Allow 'override all' into your webserver to enable the proper functioning of the .htaccess file.", 2);
        } else {
            $htaccessFile = PAYTABS_HTACCESS_FILE;
            file_put_contents($htaccessFile, $permission, FILE_APPEND);

            PaytabsHelper::log("Debug file appended to htaccess.", 1);
        }
    }
}
