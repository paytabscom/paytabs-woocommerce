<?php

$_client_key = $this->client_key;

$_js_path = 'payment/js/paylib.js';
$_js_url = $this->get_endpoint_url() . $_js_path;
?>

<script src="<?= $_js_url ?>"></script>

<div>
    <span id="paymentErrors"></span>
    <div class="row">
        <label>Card Number</label>
        <input type="text" data-paylib="number" size="20" value="4111111111111111">
    </div>
    <div class="row">
        <label>Expiry Date (MM/YYYY)</label>
        <input type="text" data-paylib="expmonth" size="2">
        <input type="text" data-paylib="expyear" size="4">
    </div>
    <div class="row">
        <label>Security Code</label>
        <input type="text" data-paylib="cvv" size="4">
        <input type="text" name="token" id="pt_token" value="token1">
    </div>

</div>


<script type="text/javascript">
    var myform = document.getElementsByName('checkout')[0];

    var checkout_form = jQuery('form.checkout');

    var mf_confirmed = false;

    paylib.inlineForm({
        'key': '<?= $_client_key ?>',
        'form': myform,
        'autoSubmit': false,
        'callback': function(response) {
            console.log(response);
            document.getElementById('paymentErrors').innerHTML = '';
            if (response.error) {
                paylib.handleError(document.getElementById('paymentErrors'), response);
            } else {
                if (!mf_confirmed) {
                    jQuery('#pt_token').val(response.token);
                    mf_confirmed = true;

                    checkout_form.submit();
                }
            }
        }
    });

    checkout_form.on('checkout_place_order', function(event, params) {
        // console.log('on_place_order', event);
        return mf_confirmed;
    });
</script>