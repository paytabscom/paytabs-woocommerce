<?php

$_url_logo = $valu_payment->getIcon();

?>

<div style="border: 1px solid orange; border-radius: 12px; margin: 10px 0px; padding: 3px;">
    <table style="margin: 0;">
        <tr>
            <td style="width: 1%; white-space: nowrap;">
                <img src="<?= esc_url($_url_logo) ?>" alt="valU logo" style="min-height: 64px; max-width: 128px;">
            </td>
            <td style="vertical-align: middle;">
                Pay 3 interest-free payments of EGP <?= $plan['emi'] ?>
            </td>
        </tr>
    </table>
</div>