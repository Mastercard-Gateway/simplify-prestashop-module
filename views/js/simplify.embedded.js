$(function() {
    if (typeof getEmbeddedConfig === 'function') {
        var hostedPayments = SimplifyCommerce.hostedPayments(
            paymentCallback,
            getEmbeddedConfig()
        );

        if (window.simplifyPublicKey.indexOf('sbpb_') !== -1) {
            $('#simplify-embedded-test-mode-msg').show();
        }

        var $conditionApprove = $('[id="conditions_to_approve[terms-and-conditions]"]');
        var $simplifyEmbeddedForm = $('#simplify-embedded-payment-form');

        if ($conditionApprove.length > 0) {
            $simplifyEmbeddedForm.toggle(!!$conditionApprove.prop('checked'));
            $conditionApprove.change(function () {
                $simplifyEmbeddedForm.toggle(!!$conditionApprove.prop('checked'));
                if (currentMethod === PAYMENT_CODE) {
                    $('#payment-confirmation button').hide();
                }
            })
        }

        var PAYMENT_CODE = 'simplifycommerce_embedded';
        var currentMethod;
        var prevMethod = currentMethod = $('[name=payment-option]:checked').data('module-name');

        $('[name=payment-option]').change(function () {
            currentMethod = $('[name=payment-option]:checked').data('module-name');
            if (prevMethod === PAYMENT_CODE && currentMethod !== PAYMENT_CODE) {
                $('#payment-confirmation button').show();
            }
            if (currentMethod === PAYMENT_CODE) {
                $('#payment-confirmation button').hide();
            }

            prevMethod = currentMethod;
        })

        hostedPayments.closeOnCompletion();
        hostedPayments.enablePayBtn()

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



