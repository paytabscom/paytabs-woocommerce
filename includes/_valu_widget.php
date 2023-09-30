<?php

// Get the dynamic URL of a paytabs plugin's directory.
$plugin_directory_url = plugins_url('paytabs-woocommerce');
$asset_url = $plugin_directory_url . '/icons/valu.png';

?>

<div style="border: 1px solid orange; border-radius: 12px; margin: 10px 0px;">
    <table>
        <tr>
            <td style="width: 1%; white-space: nowrap;">
                <img src="<?= esc_url($asset_url) ?>" alt="valU logo" style="min-height: 64px; max-width: 128px;">
            </td>
            <td style="vertical-align: middle;">
                Pay 3 interest-free payments of EGP <?= $plan['emi'] ?>
            </td>
        </tr>
    </table>
</div>