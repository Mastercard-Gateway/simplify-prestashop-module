$(function() {
    if (typeof getEmbeddedConfig === 'function') {
        var hostedPayments = SimplifyCommerce.hostedPayments(
            paymentCallback,
            getEmbeddedConfig()
        ).closeOnCompletion();

        var $simplifyEmbeddedPaymentForm = $('#simplify-embedded-payment-form');

        function paymentCallback(response) {
            if (response && response.length > 0 && response[0].error) {
                return;
            }

            if (response && response.cardToken) {

                $simplifyEmbeddedPaymentForm
                    .append('<input type="hidden" name="simplifyToken" value="' + response.cardToken + '"/>');
                $simplifyEmbeddedPaymentForm.submit();
            }
        }
    }
})



