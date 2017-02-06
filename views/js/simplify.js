/**
 * Copyright (c) 2017, MasterCard International Incorporated
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are
 * permitted provided that the following conditions are met:
 *
 * Redistributions of source code must retain the above copyright notice, this list of
 * conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright notice, this list of
 * conditions and the following disclaimer in the documentation and/or other materials
 * provided with the distribution.
 * Neither the name of the MasterCard International Incorporated nor the names of its
 * contributors may be used to endorse or promote products derived from this software
 * without specific prior written permission.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT
 * SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
 * TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS;
 * OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER
 * IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING
 * IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
 * SUCH DAMAGE.
 */

(function(){
//Cached nodes so we can use it across the module
var $simplifyPaymentForm, $simplifyPaymentErrors, $simplifySubmitButton, $simplifySpinner;

/**
 * Function to handle the form submission
 */
$(document).ready(function () {

    $simplifyPaymentForm = $('#simplify-payment-form'), $simplifyPaymentErrors = $('.simplify-payment-errors'),
        $simplifySubmitButton = $('#payment-confirmation button'), $simplifySpinner = $('#simplify-ajax-loader');

    if ($simplifyPaymentErrors.text().length > 0) {
        $simplifyPaymentErrors.show();
    }

    // Check that the Simplify API Keys are set
    if (window.simplifyPublicKey == undefined || window.simplifyPublicKey.length == 0) {
        $('#simplify-no-keys-msg').show();
        setPrestaSubmitButtonEnabled(false);
        return;
    }

    // Display warning message that this is a test payment as test api keys are being used.
    if (window.simplifyPublicKey.indexOf('sbpb_') !== -1) {
        $('#simplify-test-mode-msg').show();
    }

    $(".simplify-card-cvc").restrictNumeric();
    $('.simplify-card-number').formatCardNumber();

    /**
     *  Function to watch the form of payment being used and
     *  to show and hide the relevant form components.
     */
    $("input[name='cc-type']").change(function () {
        var ccDetails = $("#simplify-cc-details");
        $('.card-type-container').removeClass('selected');
        $(this).parents('.card-type-container').addClass('selected');

        if ($("input[name='cc-type']:checked").val() == 'new') {
            var show = !! $("#cc-deletion-msg").is(':visible');
            showSaveCardDetailsLabel(show);
            ccDetails.fadeIn();
        } else {
            ccDetails.fadeOut();
        }
    });

    /**
     *    Function to show the confirm deletion container when the
     *  trash icon is clicked.
     */
    $('#trash-icon').click(function () {
        $('#cc-confirm-deletion').slideDown();
    });

    /**
     *    Function to hide the credit card details option,
     *  select the 'new card' option and provide the user
     *  a control to undo the deletion.
     */
    $('#confirm-cc-deletion').click(function () {
        $("#old-card-container").fadeOut('fast', function () {
            $("#new-card-container input[name='cc-type']").click();
            $("#cc-deletion-msg").slideDown(function () {
                showSaveCardDetailsLabel(true);
            });
        });
        $simplifyPaymentForm.append('<input id="deleteCustomerCard" type="hidden" name="deleteCustomerCard" value="true" />');
    });

    /**
     *    Function to hide the confirm deletion container.
     */
    $('#cancel-cc-deletion').click(function () {
        $('#cc-confirm-deletion').slideUp();
    });

    /**
     *    Function to restore the save card details
     *  form option.
     */
    $('#cc-undo-deletion-lnk').click(function () {
        $("#cc-deletion-msg").hide();
        $('#cc-confirm-deletion').hide();
        $("#old-card-container").fadeIn('fast');
        $('#deleteCustomerCard').remove();
        showSaveCardDetailsLabel(false);
    });


    var lastSubmitTime = 0;
    // prevent double submit bug in versions <= 1.7.0.4
    function preventDoubleSubmit(){
        var now = new Date().getTime();
        if(now - lastSubmitTime < 100){
            return true;
        }
        lastSubmitTime = now;
        return false;
    }

    function haveToken(){
        var  t = !! getUrlToken();
        var inputToken = !! $("input[name=simplifyToken]",  $simplifyPaymentForm).val();
        var ret = t || inputToken;
        return ret;
    }

    function isTakingNewCard(){
        var val = !! $("input[name='cc-type'][value='new']", $simplifyPaymentForm).is(":checked");
        val = val || !hasExistingCard(); // if don't have existing card then force charging new card.
        return val;
    }

    function hasExistingCard(){
        var val = (typeof simplifyHasSavedCard != "undefined") && simplifyHasSavedCard;
        return val;
    }
    /**
     *  Function to handle the form submission and either
     *  generate a new card token for new cards or
     *  charge an existing user's card.
     */
    $simplifyPaymentForm[0].onsubmit = function () {

        if(preventDoubleSubmit()){
            return false;
        }

        if (isHostedPaymentsEnabled() && ! haveToken() && isTakingNewCard()) {
            $("#simplify-hosted-payment-button").click();
            setPrestaSubmitButtonEnabled(false);
            return false;
        }

        $simplifySpinner.show();
        $('.simplify-payment-errors').hide();
        setPrestaSubmitButtonEnabled(false);
        /* Disable the submit button to prevent repeated clicks */

        if (simplifyPublicKey.length == 0) {
            console.error("Simplify API key is not setup properly!");
            return false;
        }

        // Fetch a card token for new card details otherwise submit form with existing card details
        if (isTakingNewCard()) {
            if (isHostedPaymentsEnabled()) {
                //we already created a card token, so continue processing
                return true;
            }
            else {
                SimplifyCommerce.generateToken({
                    key: simplifyPublicKey,
                    card: {
                        number: $(".simplify-card-number").val().trim().replace(/\s+/g, ''),
                        cvc: $(".simplify-card-cvc").val(),
                        expMonth: $("#simplify-cc-details select[name='Date_Month']").val(),
                        expYear: $("#simplify-cc-details select[name='Date_Year']").val().substring(2),
                        name: simplifyFirstname + ' ' + simplifyLastname,
                        addressCity: simplifyCity,
                        addressLine1: simplifyAddress1,
                        addressLine2: simplifyAddress2,
                        addressState: simplifyState,
                        addressZip: simplifyPostcode
                    }
                }, simplifyResponseHandler);
            }
            return false;
            /* Prevent the form from submitting with the default action */
        } else if(hasExistingCard()) {
            $simplifyPaymentForm.append('<input type="hidden" name="chargeCustomerCard" value="true" />');
            return true;
        } else {
            return true;
        }
    };

    /**
     * Function to handle the response from Simplify Commerce's tokenization call.
     */
    function simplifyResponseHandler(data) {
        if (data.error) {
            console.error(data.error);

            var errorMessages = {
                'card.number': 'The credit card number you entered is invalid.',
                'card.expYear': 'The expiry year on the credit card is invalid.'
            };

            // Show any validation errors
            if (data.error.code == "validation") {
                var fieldErrors = data.error.fieldErrors,
                    fieldErrorsLength = fieldErrors.length,
                    errorList = "";

                for (var i = 0; i < fieldErrorsLength; i++) {
                    errorList += "<div>" + errorMessages[fieldErrors[i].field] +
                        " " + fieldErrors[i].message + ".</div>";
                }
                // Display the errors
                $('.simplify-payment-errors')
                    .html(errorList)
                    .show();
            }
            else {
                $('.simplify-payment-errors')
                    .html("Error occurred while processing payment, please contact support!")
                    .show();
            }
            // Re-enable the submit button
            setPrestaSubmitButtonEnabled(true);
            $simplifyPaymentForm.show();
            $simplifySpinner.hide();
        } else {
            // Insert the token into the form so it gets submitted to the server
            $simplifyPaymentForm
                .append('<input type="hidden" name="simplifyToken" value="' + data['id'] + '" />')
                .append('<input type="hidden" name="chargeCustomerCard" value="false" />')
                .get(0).submit();
        }
    }

    /**
     * Function checking if hosted payments is enabled
     * @returns {boolean}
     */
    function isHostedPaymentsEnabled() {
        return !! $("[name='hostedPayments']", $simplifyPaymentForm).val();
    }

    initSimplify();
});

/**
 * Function to retrieve a cardholder detail or empty string if it doesn't exist.
 */
function getCardHolderDetail(detail) {
    return (typeof cardholderDetails[detail] !== 'undefined') ? cardholderDetails[detail] : '';
}

/**
 * Function to toggle the visibility of the the 'save card details' label
 */
function showSaveCardDetailsLabel(isSaveCardeDetailsLabelVisible) {
    var $saveCustomerLabel = $('#saveCustomerLabel'),
        $updateCustomerLabel = $('#updateCustomerLabel');

    if (isSaveCardeDetailsLabelVisible) {
        $saveCustomerLabel.show();
        $updateCustomerLabel.hide();
    } else {
        $updateCustomerLabel.show();
        $saveCustomerLabel.hide();
    }
}

function setPrestaSubmitButtonEnabled(enabled){
    if(enabled){
        $simplifySubmitButton.removeAttr('disabled');
    } else {
        $simplifySubmitButton.attr('disabled', 'disabled');
    }
}

/**
 * Function to get url get parameter from window's location
 * @param name
 * @param url
 * @returns {*}
 */

function urlParam(name, url) {
    if (!url) {
        url = window.location.href;
    }
    var results = new RegExp('[\\?&]' + name + '=([^&#]*)').exec(url);
    var res = (results && results[1]) || undefined;
    return res;
}

/**
 * Show payment progress
 */
function showPaymentProgress() {
    $simplifySpinner.show();
    if ($simplifySpinner[0]) {
        //let's use the html's default scrollIntoView method.
        //If its not good, we can be fancy
        $simplifySpinner[0].scrollIntoView();
    }
}

function processHostedPaymentForm(response, url) {
    $simplifyPaymentErrors.hide();
    if (response && response.cardToken) {
        setPrestaSubmitButtonEnabled(false);
        showPaymentProgress();
        $simplifyPaymentForm.append('<input type="hidden" name="simplifyToken" value="' + response.cardToken + '"/>');
        if (url && url.indexOf('saveCustomer') > -1) {
            $('#saveCustomer').click();
            $simplifyPaymentForm.append('<input type="hidden" name="saveCustomer" value="on"/>');
        }
        if (url && url.indexOf('deleteCustomerCard') > -1) {
            $simplifyPaymentForm.append('<input id="deleteCustomerCard" type="hidden" name="deleteCustomerCard" value="true" />');
        }
        $simplifyPaymentForm.submit();
    }
    else {
        if (response.error) {
            console.error(response.error);
        }
        setPrestaSubmitButtonEnabled(true);
    }
}

function initHostedPayments(options) {
    var hostedPayments = SimplifyCommerce.hostedPayments(function (response) {
        if (response && response.length > 0 && response[0].error) {
            return;
        }
        hostedPayments.closeOnCompletion();
        processHostedPaymentForm(response);
    }, options);
}

function getUrlToken(){
    var url = window.location.href;
    return urlParam('cardToken', url);
}

function appendToRedirectUrl(current, append){
    if(current){
        var c =  current.indexOf("?") == -1 ? "?" : "&";
        current += c + append;
    }
    return current;
}

function clickSimplifyPaymentOption(){
    var id = $simplifyPaymentForm.parents(".js-payment-option-form").attr("id");
    var match = id && id.match("pay-with-payment-option-([0-9]+)-form");
    var number = match && match[1];
    $("#payment-option-" + number).click();
}

function initSimplify(){
    //Hosted payments options
    var options = {};

    // if we have an old card then show update instead of save in the label
    showSaveCardDetailsLabel(! $("#old-card-container").is(":visible"));

//if its non-HTTPS set the redirectUrl back to this page
    if (!document.location.href.match(/^https:\/\//)) {
        //redirect back to payment step
        if (!window.location.origin) { //IE don't have window.location.origin :(
            window.location.origin = window.location.protocol + "//" + window.location.hostname + (window.location.port ? ':' + window.location.port : '');
        }
        options.redirectUrl = window.location.origin + window.location.pathname;
    }


    $(document).ready(function () {
        $('input[name="payment-option"]:checked').change(); // bug where presta shop doesn't show the form that is selected when we come back to this page
        $("input[name='cc-type']:checked").change();

        var url = window.location.href;
        var cardToken = getUrlToken();

        if(cardToken || urlParam("simplify_error") ){
            // On our way back from the hosted payment form, or payment error.
            // There's a bug where our form doesn't show, so show it.
            clickSimplifyPaymentOption();
        }

        if (cardToken) {

            // on our way back from hosted payments
            var response = {
                cardToken: cardToken
            };
            processHostedPaymentForm(response, url);
        } else if(simplifyPaymentMode == "hosted_payments") {
            initHostedPayments(options);

            $('#simplify-hosted-payment-button').click(function () {

                if (options.redirectUrl) {
                    if ($('#saveCustomer').is(':checked')) {
                        options.redirectUrl = appendToRedirectUrl(options.redirectUrl, "saveCustomer=true");
                    }
                    if ($("#cc-deletion-msg").is(':visible')) {
                        options.redirectUrl = appendToRedirectUrl(options.redirectUrl, "deleteCustomerCard=true");
                    }
                }
                initHostedPayments(options);
            });
        } else {
        }
    });

}})();

