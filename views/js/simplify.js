/*
 * Copyright (c) 2017-2023 Mastercard
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */

(function () {
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

        if ($simplifyPaymentForm.length === 0) {
            return;
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
                var show = !!$("#cc-deletion-msg").is(':visible');
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
         *    Function to hide the card details option,
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
        function preventDoubleSubmit() {
            var now = new Date().getTime();
            if (now - lastSubmitTime < 100) {
                return true;
            }
            lastSubmitTime = now;
            return false;
        }

        function haveToken() {
            var t = !!getUrlToken();
            var inputToken = !!$("input[name=simplifyToken]", $simplifyPaymentForm).val();
            var ret = t || inputToken;
            return ret;
        }

        function isTakingNewCard() {
            var val = !!$("input[name='cc-type'][value='new']", $simplifyPaymentForm).is(":checked");
            val = val || !hasExistingCard(); // if don't have existing card then force charging new card.
            return val;
        }

        function hasExistingCard() {
            var val = (typeof simplifyHasSavedCard != "undefined") && simplifyHasSavedCard;
            return val;
        }

        function releasePaymentForm() {
            $simplifyPaymentForm.data('disabled', false);
        }

        /**
         *  Function to handle the form submission and either
         *  generate a new card token for new cards or
         *  charge an existing user's card.
         */
        $simplifyPaymentForm.submit(function () {
            if (preventDoubleSubmit()) {
                releasePaymentForm();
                return false;
            }

            if (isHostedPaymentsEnabled() && !haveToken() && isTakingNewCard()) {
                $("#simplify-hosted-payment-button").click();
                setPrestaSubmitButtonEnabled(false);
                releasePaymentForm();
                return false;
            }

            $simplifySpinner.show();
            $('.simplify-payment-errors').hide();
            setPrestaSubmitButtonEnabled(false);
            /* Disable the submit button to prevent repeated clicks */

            if (simplifyPublicKey.length == 0) {
                console.error("Simplify API key is not setup properly!");
                releasePaymentForm();
                return false;
            }

            // Fetch a card token for new card details otherwise submit form with existing card details
            if (isTakingNewCard()) {
                if (isHostedPaymentsEnabled()) {
                    //we already created a card token, so continue processing
                    return true;
                } else {
                    SimplifyCommerce.generateToken(
                        {
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
                        },
                        simplifyResponseHandler
                    );
                }

                releasePaymentForm();
                return false;
                /* Prevent the form from submitting with the default action */
            } else if (hasExistingCard()) {
                $simplifyPaymentForm.append('<input type="hidden" name="chargeCustomerCard" value="true" />');
                return true;
            } else {
                return true;
            }
        });

        /**
         * Function to handle the response from Simplify Commerce's tokenization call.
         */
        function simplifyResponseHandler(data) {
            if (data.error) {
                console.error(data.error);

                var errorMessages = {
                    'card.number': 'The card number you entered is invalid.',
                    'card.expYear': 'The expiry year on the card is invalid.'
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
                } else {
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
            return !!$("[name='hostedPayments']", $simplifyPaymentForm).val();
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

    function setPrestaSubmitButtonEnabled(enabled) {
        if (enabled) {
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

            $simplifyPaymentForm
                .append('<input type="hidden" name="simplifyToken" value="' + response.cardToken + '"/>');

            if (url && url.indexOf('saveCustomer') > -1) {
                $('#saveCustomer').click();
                $simplifyPaymentForm.append('<input type="hidden" name="saveCustomer" value="on"/>');
            }

            if (url && url.indexOf('deleteCustomerCard') > -1) {
                $simplifyPaymentForm.append('<input id="deleteCustomerCard" type="hidden" name="deleteCustomerCard" value="true" />');
            }

            $simplifyPaymentForm.submit();
        } else {
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

    function getUrlToken() {
        var url = window.location.href;
        return urlParam('cardToken', url);
    }

    function appendToRedirectUrl(current, append) {
        if (current) {
            var c = current.indexOf("?") == -1 ? "?" : "&";
            current += c + append;
        }
        return current;
    }

    function clickSimplifyPaymentOption() {
        var id = $simplifyPaymentForm.parents(".js-payment-option-form").attr("id");
        var match = id && id.match("pay-with-payment-option-([0-9]+)-form");
        var number = match && match[1];
        $("#payment-option-" + number).click();
    }

    function initSimplify() {
        //Hosted payments options
        var options = {};

        // if we have an old card then show update instead of save in the label
        showSaveCardDetailsLabel(!$("#old-card-container").is(":visible"));

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

            if (cardToken || urlParam("simplify_error")) {
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
            } else {
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
            }
        });
        
    }
})();
