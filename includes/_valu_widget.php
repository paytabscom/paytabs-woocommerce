<?php

$_url_logo = $valu_payment->getIconWidget();

?>

<div style="border: 1px solid orange; border-radius: 12px; margin: 10px 0px; padding: 3px;">
    <table style="margin: 0;">
        <tr>
            <td style="width: 1%; white-space: nowrap;">
                <img src="<?= esc_url($_url_logo) ?>" alt="valU logo" style="min-height: 32px; max-width: 128pt;">
            </td>
            <td style="vertical-align: middle;">
                <?= $plan ?>
            </td>
        </tr>
    </table>
</div>