/*
 * Copyright (c) 2023 Mastercard
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

$(function () {
    if (typeof getEmbeddedConfig !== 'function') {
        return;
    }

    var CARD_TYPE_OLD = 'old';
    var CART_TYPE_NEW = 'new';
    var PAYMENT_CODE = 'simplifycommerce_embedded';

    /**
     * Elements registry
     */
    var elements = {
        conditionApproveElement: $('[id="conditions_to_approve[terms-and-conditions]"]'),
        paymentContainer: $('#simplify_embedded_payment_container'),
        ajaxLoader: $('#simplify_embedded_ajax_loader'),
        noKeysMsg: $('#simplify_embedded_no_keys_msg'),
        errorsContainer: $('#simplify_embedded_errors_container'),
        paymentForm: $('#simplify_embedded_payment_form'),
        savedCardDeletedConfirmation: $('#simplify_embedded_saved_card_deleted_confirmation'),
        savedCardRadio: $('#simplify_embedded_saved_card_radio'),
        savedCardSelector: $('#simplify_embedded_saved_card_selector'),
        newCardRadio: $('#simplify_embedded_new_card_radio'),
        newCardSelector: $('#simplify_embedded_new_card_selector'),
        cardDeletionAlert: $('#simplify_embedded_card_deletion_alert'),
        cardDeletionConfirmation: $('#simplify_embedded_card_deletion_confirmation'),
        deleteSavedCardAction: $('#simplify_embedded_delete_saved_card_action'),
        savedCardDeletionUndoAction: $('#simplify_embedded_saved_card_deletion_undo_action'),
        savedCardConfirmDeletionAction: $('#simplify_embedded_saved_card_confirm_deletion_action'),
        savedCardCancelDeletionAction: $('#simplify_embedded_saved_card_cancel_deletion_action'),
        newCardForm: $('#simplify_embedded_new_card_form'),
        saveCustomerLabel: $('#simplify_embedded_save_customer_label'),
        updateCustomerLabel: $('#simplify_embedded_update_customer_label'),
        ccDetails: $('#simplify_embedded_cc_details'),
        testModeMessage: $('#simplify_embedded_test_mode_msg'),
        paymentTermsOfServiceNotice: $('#simplify_embedded_payment_terms_of_service_notice'),
        globalPaymentButton: $('#payment-confirmation button'),
        paymentOptions: $('[name=payment-option]'),
        paymentButtonContainer: $('#simplify_embedded_payment_button_container'),
        paymentSubmitAction: $('#simplify_embedded_payment_submit_action'),
    };

    /**
     * State Initialization
     */
    var state = {
        hasSavedCard: window.simplifyHasSavedCard,
        paymentError: false,
        chosenCardType: CARD_TYPE_OLD,
        isCardDeletionInProgress: false,
        isCardDeleted: false,
        conditionsApproved: elements.conditionApproveElement.prop('checked'),
        currentMethod: $('[name=payment-option]:checked').data('module-name'),
        prevMethod: $('[name=payment-option]:checked').data('module-name'),
        isRequestSubmitted: false,
        isTestPayment: (window.simplifyPublicKey.indexOf('sbpb_') !== -1),
        showMissedCredentialsErrors: (window.simplifyPublicKey == undefined || window.simplifyPublicKey.length == 0),
    };

    /**
     * Actions
     */
    elements.conditionApproveElement.on('change', function () {
        setState({
            conditionsApproved: $(this).is(":checked")
        })
    });

    elements.paymentOptions.on('click', function () {
        setState({
            currentMethod: $('[name=payment-option]:checked').data('module-name'),
            prevMethod: state.currentMethod,
        });
    });

    elements.savedCardSelector.on('click', function () {
        setState({
            chosenCardType: CARD_TYPE_OLD
        });
    });

    elements.newCardSelector.on('click', function () {
        setState({
            chosenCardType: CART_TYPE_NEW,
            isCardDeletionInProgress: false
        });
    });

    elements.savedCardRadio.on('change', function () {
        setState({
            chosenCardType: $(this).is(":checked") ? CARD_TYPE_OLD : CART_TYPE_NEW
        })
    });

    elements.newCardRadio.on('change', function () {
        setState({
            chosenCardType: $(this).is(":checked") ? CART_TYPE_NEW : CARD_TYPE_OLD
        })
    });

    elements.deleteSavedCardAction.on('click', function (e) {
        e.stopPropagation();
        setState({
            isCardDeletionInProgress: true,
            isCardDeleted: false,
        });
        return false;
    });

    elements.savedCardDeletionUndoAction.on('click', function (e) {
        e.stopPropagation();
        setState({
            isCardDeletionInProgress: false,
            isCardDeleted: false,
        });
        return false;
    });

    elements.savedCardCancelDeletionAction.on('click', function (e) {
        e.stopPropagation();
        setState({
            isCardDeletionInProgress: false,
            isCardDeleted: false,
        });
        return false;
    });

    elements.savedCardConfirmDeletionAction.on('click', function (e) {
        e.stopPropagation();
        setState({
            isCardDeletionInProgress: true,
            isCardDeleted: true,
            chosenCardType: CART_TYPE_NEW,
        });
        return false;
    });

    elements.paymentSubmitAction.on('click', function (e) {
        e.stopPropagation();
        setState({
            isRequestSubmitted: true,
        });

        elements
            .paymentForm
            .append('<input type="hidden" name="chargeCustomerCard" value="true"/>');

        elements.paymentForm.submit();
    })

    /**
     * This function renders the view based on the state variable.
     * If we want to change the view, we need to update the state variable and
     * trigger this function.
     */
    function renderView() {
        /** Handle Missed Credentials Error */
        if (state.showMissedCredentialsErrors) {
            elements.noKeysMsg.removeClass('hidden');
        } else {
            elements.noKeysMsg.addClass('hidden');
        }

        /** Handle Payment Errors */
        if (state.paymentError) {
            if (state.paymentError.code === "gateway") {
                elements.errorsContainer.html(state.paymentError.message);
            } else {
                elements.errorsContainer.html(
                    "An error occurred while processing payment. Please get in touch with support."
                );
            }

            elements.errorsContainer.removeClass('hidden');
        } else {
            elements.errorsContainer.addClass('hidden');
        }

        /** Handle terms of usage checkbox */
        if (state.conditionsApproved) {
            elements.paymentContainer.removeClass('hidden');
            elements.paymentTermsOfServiceNotice.addClass('hidden');
        } else {
            elements.paymentContainer.addClass('hidden');
            elements.paymentTermsOfServiceNotice.removeClass('hidden');
        }

        /** Handle Payment Methods choose */
        if (state.prevMethod === PAYMENT_CODE && state.currentMethod !== PAYMENT_CODE) {
            elements.globalPaymentButton.show();
        } else if (state.currentMethod === PAYMENT_CODE) {
            elements.globalPaymentButton.hide();
        }

        /** Handle Saving Card Label */
        if (state.hasSavedCard && !state.isCardDeleted) {
            elements.saveCustomerLabel.addClass('hidden');
            elements.updateCustomerLabel.removeClass('hidden');
        } else {
            elements.saveCustomerLabel.removeClass('hidden');
            elements.updateCustomerLabel.addClass('hidden');
        }

        /** Handle Payment Option */
        if (state.hasSavedCard && state.chosenCardType === CARD_TYPE_OLD) {
            elements.newCardForm.addClass('hidden');
            elements.savedCardSelector.addClass('selected');
            elements.newCardSelector.removeClass('selected');
            elements.savedCardRadio.prop("checked", true);
            elements.newCardRadio.prop("checked", false);
            elements.paymentButtonContainer.show();
        } else if (state.hasSavedCard && state.chosenCardType === CART_TYPE_NEW) {
            elements.newCardForm.removeClass('hidden');
            elements.newCardSelector.addClass('selected');
            elements.savedCardSelector.removeClass('selected');
            elements.savedCardRadio.prop("checked", false);
            elements.newCardRadio.prop("checked", true);
            elements.paymentButtonContainer.hide();
        } else {
            elements.newCardForm.removeClass('hidden');
            elements.paymentButtonContainer.hide();
        }

        /** Handle Deleted Cards */
        if (state.isCardDeleted) {
            elements.savedCardSelector.addClass('hidden')
            elements.savedCardDeletedConfirmation.removeClass('hidden');
        } else {
            elements.savedCardSelector.removeClass('hidden')
            elements.savedCardDeletedConfirmation.addClass('hidden');
        }

        /** Handle Card Deletion Confirmation Widget */
        if (state.isCardDeletionInProgress) {
            elements.cardDeletionAlert.removeClass('hidden');
        } else {
            elements.cardDeletionAlert.addClass('hidden');
        }

        /** Handle Test Notice */
        if (state.isTestPayment) {
            elements.testModeMessage.removeClass('hidden');
        } else {
            elements.testModeMessage.addClass('hidden');
        }

        /** Handle Loading Message */
        if (state.isRequestSubmitted) {
            elements.ajaxLoader.removeClass('hidden');
            elements.paymentSubmitAction.prop('disabled', true);
        } else {
            elements.ajaxLoader.addClass('hidden');
            elements.paymentSubmitAction.prop('disabled', false);
        }
    }

    /**
     * This function updates some properties of the state and triggers
     * the renderView function to rebuild the view using an updated version
     * of the state.
     *
     * @param newState
     */
    function setState(newState) {
        state = $.extend(state, newState);
        renderView();
    }

    /**
     * Payment initialization
     */
    var hostedPaymentsObject = SimplifyCommerce.hostedPayments(
        paymentCallback,
        window.getEmbeddedConfig()
    );
    hostedPaymentsObject.closeOnCompletion();

    function paymentCallback(response) {
        if (response && response.cardToken) {
            elements
                .paymentForm
                .append('<input type="hidden" name="simplifyToken" value="' + response.cardToken + '"/>');

            if (state.isCardDeleted) {
                elements
                    .paymentForm
                    .append('<input type="hidden" name="deleteCustomerCard" value="true"/>');
            }

            elements.paymentForm.submit();
        }
    }

    /**
     * Function to get url get parameter from window's location
     * @returns {*}
     * @param name
     * @param url
     */
    function getUrlParam(name, url) {
        var results, res;

        if (!url) {
            url = window.location.href;
        }
        results = new RegExp('[\\?&]' + name + '=([^&#]*)').exec(url);
        res = (results && results[1]) || undefined;
        return res;
    }

    /**
     * This function should be used in the case of error happened with Payment on Backend
     */
    function showSimplifyPaymentForm() {

        var id = elements.paymentForm.parents(".js-payment-option-form").attr("id");
        var match = id && id.match("pay-with-payment-option-([0-9]+)-form");
        var number = match && match[1];
        $("#payment-option-" + number).click();

        elements.conditionApproveElement.prop('checked', true);
        setState({
            conditionsApproved: elements.conditionApproveElement.is(":checked")
        })
    }

    var simplifyError = getUrlParam('simplify_error');
    if (simplifyError) {
        showSimplifyPaymentForm();
        setState({
            paymentError: {
                code: "gateway",
                message: decodeURI(simplifyError)
            }
        });
    }

    /**
     * View initialization
     */
    renderView();
})
