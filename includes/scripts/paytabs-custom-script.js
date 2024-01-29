jQuery(document).ready(function ($) {
  $('#paytabs_capture_btn').on('click', function () {
    var orderId = this.getAttribute('data-order-id');
  //   var pt_nonce = this.getAttribute('data-nonce');
    var payment_method = this.getAttribute('data-payment-method');
  $.ajax({
      url: ajaxurl,
      type: 'POST',
      data: {
          action: 'paytabs_capture',
          order_id: orderId,
          payment_method: payment_method,
          // pt_nonce: pt_nonce,
      },
      success: function (response) {
          // console.log(response)
          location.reload();
      },
      error: function (err) {
        console.log("There was an error: " + err.error);
      }
    });
  });
});