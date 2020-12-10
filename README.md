# PayTabs - WooCommerce

Official WooCommerce plugin for PayTabs

- - -

## Installation

*Note:* **WooCommerce** must be installed and activated for PayTabs plugin to work.

### Install using FTP method

1. Download the latest release of the plugin [2.3.7.1]
2. Upload the folder `paytabs-woocommerce` to the wordpress installation directory: `wp-content/plugins/`

*Note: Delete any previous PayTabs plugin.*

### Install using WordPress Admin panel

1. Download the latest release of the plugin [2.3.7.1]
2. Go to `"WordPress admin panel" >> Plugins >> Add New`
3. Select `Upload Plugin`
4. Click `Browse` and select the downloaded zip file (`paytabs-woocommerce.zip`)
5. Click `Install Now`
6. If a previous version exists, select `Replace current with uploaded`

- - -

## Activating the Plugin

1. Go to `"Wordpress admin panel" >> Plugins >> Installed Plugins`
2. Look for `PayTabs - WooCommerce Payment Gateway` and click `Activate`

- - -

## Configure the Plugin

1. Go to `"WordPress admin panel" >> WooCommerce >> Settings`
2. Select `Payments` tab
3. Select the preferred payment method from the available list of PayTabs payment methods
4. Check the `Enable Payment Gateway`
5. Enter the primary credentials:
   - **Profile ID**: Enter the Profile ID of your PayTabs account
   - **Server Key**: `Merchant’s Dashboard >> Developers >> Key management >> Server Key`
6. Click `Save changes`

- - -

## Log Access

### PayTabs custome log

1. Access `debug_paytabs.log` file found at: `/wp-content/debug_paytabs.log`

- - -

Done
