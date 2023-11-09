{**
* Copyright (c) 2017-2023 Mastercard
*
* Licensed under the Apache License, Version 2.0 (the "License");
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
*
*    http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
*}
<div>
    <script>
        var simplifyPublicKey = "{$simplify_public_key|escape:'htmlall':'UTF-8'}",
                simplifyCustomerName = "{$customer_name|escape:'htmlall':'UTF-8'}",
                simplifyFirstname = "{$firstname|escape:'htmlall':'UTF-8'}",
                simplifyLastname = "{$lastname|escape:'htmlall':'UTF-8'}",
                simplifyCity = "{$city|escape:'htmlall':'UTF-8'}",
                simplifyAddress1 = "{$address1|escape:'htmlall':'UTF-8'}",
                simplifyAddress2 = "{$address2|escape:'htmlall':'UTF-8'}",
                simplifyState = "{$state|escape:'htmlall':'UTF-8'}",
                simplifyPostcode = "{$postcode|escape:'htmlall':'UTF-8'}",
                enabledEmbedded = "{$enabled_embedded|escape:'htmlall':'UTF-8'}",
                simplifyHasSavedCard = false;
    </script>

    <div class="simplify-form-container simplifyFormContainer box {if !isset($show_saved_card_details)} no-saved {/if} additional-information"
         id="simplify_embedded_payment_container">

        <div class="simplify-embedded-ajax-loader hidden"
             id="simplify_embedded_ajax_loader">
            <span>{l s='Your payment is being processed...' mod='simplifycommerce'}</span>
            <img src="{$module_dir|escape}views/img/ajax-loader.gif"
                 alt="{l s='Loading...' mod='simplifycommerce'}"/>
        </div>

        <div class="simplify-payment-errors hidden" id="simplify_embedded_no_keys_msg">
            <span class="msg-container">
                {l s='Payment Form not configured correctly. Please contact support.' mod='simplifycommerce'}
            </span>
        </div>

        <div class="simplify-payment-errors hidden"
             id="simplify_embedded_errors_container">
            <span class="msg-container">
                {if isset($smarty.get.simplify_error)}{$smarty.get.simplify_error|escape:'htmlall':'UTF-8'}{/if}
            </span>
        </div>

        <form action="{$module_dir|escape}payment.php" method="POST" id="simplify_embedded_payment_form">
            {if isset($show_saved_card_details)}
                <script>simplifyHasSavedCard = true;</script>
                <div class="simplify-saved-card-deletion-confirmation hidden"
                     id="simplify_embedded_saved_card_deleted_confirmation">
                    <span>
                        {l s='Your card has been deleted.' mod='simplifycommerce'}
                        <a href="#"
                           title="{l s='Undo Cart Deletion' mod='simplifycommerce'}"
                           id="simplify_embedded_saved_card_deletion_undo_action">
                            {l s='Undo' mod='simplifycommerce'}
                            <img class="undo-icon"
                                 alt="{l s='Undo' mod='simplifycommerce'}"
                                 src="{$module_dir|escape}views/img/undo.png"/>
                        </a>
                    </span>
                </div>
                <div class="simplify-saved-cards">
                    <div class="simplify-card-selector selected"
                         id="simplify_embedded_saved_card_selector">
                        <div class="card-radio-button">
                            <input class="left"
                                   id="simplify_embedded_saved_card_radio"
                                   type="radio"
                                   name="cc-type"
                                   value="old"
                                   {if !isset($smarty.get.simplify_error)}checked='checked'{/if}/>
                        </div>

                        <div class="card-detail">
                            <div class="card-detail-label">{l s='Card Type' mod='simplifycommerce'}</div>
                            <div class="card-detail-value">{$customer_details->card->type|escape:'htmlall':'UTF-8'}</div>
                        </div>

                        <div class="card-detail">
                            <div class="card-detail-label">{l s='Card Ending' mod='simplifycommerce'}</div>
                            <div class="card-detail-value">
                                x{$customer_details->card->last4|escape:'htmlall':'UTF-8'}</div>
                        </div>

                        <div class="card-detail">
                            <div class="card-detail-label">{l s='Expiry Date' mod='simplifycommerce'}</div>
                            <div class="card-detail-value">
                            <span>
                                {$customer_details->card->expMonth|escape:'htmlall':'UTF-8'} /
                                {$customer_details->card->expYear|escape:'htmlall':'UTF-8'}
                            </span>
                            </div>
                        </div>

                        <div class="card-actions">
                            <div class="card-delete-action">
                                <a href="#" id="simplify_embedded_delete_saved_card_action">
                                    <img src="{$module_dir|escape}views/img/trash.png"
                                         alt="{l s='Delete' mod='simplifycommerce'}"
                                         title="{l s='Delete Card' mod='simplifycommerce'}"/>
                                </a>
                            </div>
                        </div>

                        <div class="card-deletion-confirmation hidden"
                             id="simplify_embedded_card_deletion_alert">
                            <span class="small">
                                {l s='Do you want to delete the Saved Card?' mod='simplifycommerce'}
                            </span>
                            <div class="card-deletion-actions">
                                <a class="card-deletion-action"
                                   id="simplify_embedded_saved_card_confirm_deletion_action"
                                   href="#">
                                    {l s='Yes' mod='simplifycommerce'}
                                </a>
                                <a class="card-deletion-action"
                                   id="simplify_embedded_saved_card_cancel_deletion_action"
                                   href="#">
                                    {l s='No' mod='simplifycommerce'}
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="simplify-card-selector" id="simplify_embedded_new_card_selector">
                        <div class="card-radio-button">
                            <input id="simplify_embedded_new_card_radio" class="left" type="radio" name="cc-type"
                                   value="new"
                                   {if isset($smarty.get.simplify_error)}checked='checked'{/if} />

                            <label for="simplify_embedded_new_card_radio" class="card-detail-value">
                                {l s='Use another Credit Card' mod='simplifycommerce'}
                            </label>
                        </div>
                    </div>
                </div>
            {/if}

            <div class="simplify-new-card-container clearfix {if isset($show_saved_card_details)} hidden{/if}"
                 id="simplify_embedded_new_card_form">

                {if isset($show_save_customer_details_checkbox)}
                    <div class="save-card-container">
                        <input type="checkbox" id="simplify_embedded_save_card" name="saveCustomer">
                        <label class="save-label" for="simplify_embedded_save_card"
                               id="simplify_embedded_save_customer_label">
                            {l s='Save your card details for next time?' mod='simplifycommerce'}
                        </label>
                        <label class="save-label" for="simplify_embedded_save_card"
                               id="simplify_embedded_update_customer_label">
                            {l s='Update your saved card details?' mod='simplifycommerce'}
                        </label>
                    </div>
                {/if}

                <div id="simplify_embedded_cc_details">
                    <iframe width="100%"
                            height="450px"
                            name="{$hosted_payment_name|escape:'htmlall':'UTF-8'}"
                            data-role="embedded_pay"
                            data-sc-key="{$simplify_public_key|escape:'htmlall':'UTF-8'}"
                            data-name="{$hosted_payment_name|escape:'htmlall':'UTF-8'}"
                            data-description="{$hosted_payment_description|escape:'htmlall':'UTF-8'}"
                            data-reference="{$hosted_payment_reference|escape:'htmlall':'UTF-8'}"
                            data-amount="{$hosted_payment_amount}"
                            data-customer-name="{$customer_name|escape:'htmlall':'UTF-8'}"
                            data-color="{$overlay_color|escape:'htmlall':'UTF-8'}"
                            data-currency="{$currency_iso}"
                            data-operation="create.token">
                    </iframe>
                    <script>
                        function getEmbeddedConfig() {
                            return {
                                scKey: "{$simplify_public_key|escape:'htmlall':'UTF-8'}",
                                amount: "{$hosted_payment_amount}",
                                currency: "{$currency_iso}",
                                reference: "{$hosted_payment_reference|escape:'htmlall':'UTF-8'}",
                                operation: 'create.token',
                                selector: '[data-role=embedded_pay]',
                            }
                        }
                    </script>
                </div>
            </div>

            <div class="ps-shown-by-js hidden" id="simplify_embedded_payment_button_container">
                <button type="submit" class="btn btn-primary center-block" id="simplify_embedded_payment_submit_action">
                    {l s='Pay Now' mod='simplifycommerce'}
                </button>
            </div>

            <div class="test-msg hidden" id="simplify_embedded_test_mode_msg">
                <span>
                     {l s='This is a Test Payment' mod='simplifycommerce'}
                </span>
            </div>
            <input type="hidden" name="hostedPayments" value="true"/>
        </form>
    </div>
    <div id="simplify_embedded_payment_terms_of_service_notice" class="additional-information">
        <p>{l s='Please, agree to the terms of service to continue with this payment method.' mod='simplifycommerce'}</p>
    </div>
</div>
