# PayTabs - WooCommerce

Official WooCommerce plugin for PayTabs

---

## Installation

*Note:* **WooCommerce** must be installed and activated for PayTabs plugin to work.

### Install using FTP method

1. Download the latest release of the plugin
2. Upload the folder `paytabs-woocommerce` to the wordpress installation directory: `wp-content/plugins/`

*Note: Delete any previous PayTabs plugin.*

### Install using WordPress Admin panel

1. Download the latest release of the plugin
2. Go to `"WordPress admin panel" >> Plugins >> Add New`
3. Select `Upload Plugin`
4. Click `Browse` and select the downloaded zip file (`paytabs-woocommerce.zip`)
5. Click `Install Now`
6. If a previous version exists, select `Replace current with uploaded`

---

## Activating the Plugin

1. Go to `"Wordpress admin panel" >> Plugins >> Installed Plugins`
2. Look for `PayTabs - WooCommerce Payment Gateway` and click `Activate`

---

## Configure the Plugin

1. Go to `"WordPress admin panel" >> WooCommerce >> Settings`
2. Select `Payments` tab
3. Select the preferred payment method from the available list of PayTabs payment methods
4. Check the `Enable Payment Gateway`
5. Enter the primary credentials:
   - **Profile ID**: Enter the Profile ID of your PayTabs account
   - **Server Key**: `Merchant’s Dashboard >> Developers >> Key management >> Server Key`
6. Click `Save changes`

## Use Auth - Capture - Void

1. In the configuration page select transaction type: **Auth**.
2. The default order-status for **Auth** Orders is **on-hold** unless you change it from the configuration page.
3. To **Capture** an **Auth** order you need to go to the order edit view >> change the order status to **Completed** then Save, the **Capture** will be done.
4. To **Void** the **Auth** order, you need to go to the order edit view >> change the order status to **Cancelled** then Save, the **Void** will be done.


## Use Iframe
---
1. In the configuration page select Payment form type: **Iframe**.
2. Enter The Plugin Folder you will find directory woocomemerce >> templates >> checkout ,
copy the file named form-checkout.php to the same directory inside the WooCommerce plugin Folder
3. save the configuration.

## Log Access

### PayTabs custome log

1. Access `debug_paytabs.log` file found at: `/wp-content/debug_paytabs.log`

---

Done
