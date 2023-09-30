<?php

class ValuWidget
{

    function init($valu_payment)
    {
        if ($valu_payment->valu_widget_enable) {

            $product_price = $this->get_product_price();
            if ($product_price) {
                if ($product_price >= $valu_payment->valu_widget_price_threshold) {
                    $plan = $this->call_valu_api($valu_payment, $product_price);

                    if ($plan) {
                        include(PAYTABS_PAYPAGE_DIR . 'includes/_valu_widget.php');
                    }
                }
            }
        }
    }

    function get_product_price()
    {
        // Get the current product's ID.
        $product_id = get_the_ID();
        $product = wc_get_product($product_id);
        $product_price = 0;

        if ($product) {
            // Get the product price.
            $product_price = $product->get_price();
        }

        return $product_price;
    }

    function call_valu_api($valu_payment, $product_price)
    {
        $_paytabsApi = PaytabsApi::getInstance($valu_payment->paytabs_endpoint, $valu_payment->merchant_id, $valu_payment->merchant_key);
        $phone_number = $valu_payment->valu_widget_phone_number;

        $data = [
            'cart_amount' => $product_price,
            'cart_currency' => "EGP",
            'customer_details' => [
                "phone" => $phone_number
            ],
        ];

        PaytabsHelper::log("valU inqiry, {$product_price}", 1);
        $details = $_paytabsApi->inqiry_valu($data);

        if (!$details || !$details->success) {
            $_err_msg = json_encode($details);
            PaytabsHelper::log("valU Details error: [{$_err_msg}]", 3);
            return false;
        }

        $installments_count = 3;
        $valu_plan = $this->getValUPlan($details, $installments_count);

        if (!$valu_plan) {
            return false;
        }

        try {
            $installment_amount = $valu_plan->emi;

            $calculated_installment = round($product_price / $installments_count, 2);
            $is_free_interest = $calculated_installment >= $installment_amount;

            $txt_free = $is_free_interest ? "interest-free" : "";

            $msg = "Pay {$installments_count} {$txt_free} payments of EGP $installment_amount.";

            return $msg;
        } catch (\Throwable $th) {
            PaytabsHelper::log("valU widget error: " . $th->getMessage(), 3);
        }

        return false;
    }

    function getValUPlan($details, $installments_count)
    {
        try {
            $plansList = $details->valuResponse->productList[0]->tenureList;
            foreach ($plansList as $plan) {
                if ($plan->tenorMonth == $installments_count) {
                    return $plan;
                }
            }
        } catch (\Throwable $th) {
            PaytabsHelper::log("valU Plan error: " . $th->getMessage(), 3);
        }

        $_log = json_encode($plansList);
        PaytabsHelper::log("valU Plan error: No Plan selected, [{$_log}]", 2);

        return false;
    }
}
