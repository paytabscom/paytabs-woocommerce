<?php

$_client_key = $this->client_key;

$_js_path = 'payment/js/paylib.js';
$_js_url = $this->get_endpoint_url() . $_js_path;
?>

<script src="<?= $_js_url ?>"></script>

<div id="pt_managed_form" class="form-row woocommerce-SavedPaymentMethods-saveNew">

    <div>
        <span style="color: red;" id="paymentErrors"></span>
		<div>
		<label class="card-number-label">Card Number</label>
		</div>
        <div class="row">
            
            <input type="text" data-paylib="number" size="20" placeholder="Card Number">
        </div>
		<div>
		<label class="card-details-label">Card Details</label>
		</div>
        <div class="row-mm">
            
            <input type="text" id="expmonth" class="form-control" data-paylib="expmonth" name="ccmonth" autocomplete="cc-exp-month" placeholder="MM" autocorrect="off" spellcheck="false" aria-label="expmonth" aria-invalid="false" maxlength="2" pattern="[0-9]*" inputmode="numeric">
            <input type="text" id="expyear" class="form-control-two" data-paylib="expyear" name="ccyear" autocomplete="cc-exp-year" placeholder="YY" autocorrect="off" spellcheck="false" aria-label="expyear" aria-invalid="false" maxlength="2" pattern="[0-9]*" inputmode="numeric">
			<input type="text" data-paylib="cvv" size="4" placeholder="CVV"><input type="hidden" name="token" id="pt_token" value="">

        </div>
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
        var isFormHidden = jQuery('#pt_managed_form').is(':hidden');

        return mf_confirmed || isFormHidden;
    });
</script>
