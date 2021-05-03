# ClickPay - WooCommerce

Official WooCommerce plugin for ClickPay

- - -

## Installation

*Note:* **WooCommerce** must be installed and activated for ClickPay plugin to work.

### Install using FTP method

1. Download the latest release of the plugin
2. Upload the folder `ClickPay-woocommerce` to the wordpress installation directory: `wp-content/plugins/`

*Note: Delete any previous ClickPay plugin.*

### Install using WordPress Admin panel

1. Download the latest release of the plugin
2. Go to `"WordPress admin panel" >> Plugins >> Add New`
3. Select `Upload Plugin`
4. Click `Browse` and select the downloaded zip file (`ClickPay-woocommerce.zip`)
5. Click `Install Now`
6. If a previous version exists, select `Replace current with uploaded`

- - -

## Activating the Plugin

1. Go to `"Wordpress admin panel" >> Plugins >> Installed Plugins`
2. Look for `ClickPay - WooCommerce Payment Gateway` and click `Activate`

- - -

## Configure the Plugin

1. Go to `"WordPress admin panel" >> WooCommerce >> Settings`
2. Select `Payments` tab
3. Select the preferred payment method from the available list of ClickPay payment methods
4. Check the `Enable Payment Gateway`
5. Enter the primary credentials:
   - **Profile ID**: Enter the Profile ID of your ClickPay account
   - **Server Key**: `Merchant’s Dashboard >> Developers >> Key management >> Server Key`
6. Click `Save changes`

- - -

## Log Access

### ClickPay custome log

1. Access `debug_ClickPay.log` file found at: `/wp-content/debug_ClickPay.log`

- - -

Done
