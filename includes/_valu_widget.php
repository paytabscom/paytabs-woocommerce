<?php

// Get the dynamic URL of a paytabs plugin's directory.
$plugin_directory_url = plugins_url('paytabs-woocommerce');
$asset_url = $plugin_directory_url . '/icons/valu.png';

?>

<div class="paytabs_valu_widget" style="float: right; width: 48%; clear: none;">
    <img src="<?= esc_url($asset_url) ?>" alt="Valu Logo">
    <p style="display: inline-block; margin-left: 2%;">Pay 3 interest-free payments of EGP <?= $plan['emi'] ?>.</p>
</div>