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
            simplifyCity = "{$city|escape:'htmlall':'UTF-8'}",
            simplifyAddress1 = "{$address1|escape:'htmlall':'UTF-8'}",
            simplifyAddress2 = "{$address2|escape:'htmlall':'UTF-8'}",
            simplifyState = "{$state|escape:'htmlall':'UTF-8'}",
            simplifyPostcode = "{$postcode|escape:'htmlall':'UTF-8'}";
            enabledPaymentWindow = "{$enabled_payment_window|escape:'htmlall':'UTF-8'}";
    </script>
    <div class="simplifyFormContainer box {if !isset($show_saved_card_details)} no-saved {/if}">
        <div class="clearfix">
            <div class="error-msg hidden" id="simplify-no-keys-msg" >
                <span class="msg-container">Payment Form not configured correctly. Please contact support.</span>
            </div>
        </div>

        <div id="simplify-ajax-loader">
            <span>Your payment is being processed...</span>
            <img src="{$module_dir|escape}views/img/ajax-loader.gif" alt="Loader Icon"/>
        </div>

        <div class="simplify-payment-errors">{if isset($smarty.get.simplify_error)}{$smarty.get.simplify_error|escape:'htmlall':'UTF-8'}{/if}</div>
        <form action="{$module_dir|escape}payment.php" method="POST" id="simplify-payment-form">
            {if isset($show_saved_card_details)}
                <div id="old-card-container" class='card-type-container selected clearfix'>
                    <div class="first card-detail left">
                        <div class='card-detail-label'>&nbsp;</div>
                        <input id="simp-saved-cc-radio" class="left" type="radio" name='cc-type' value='old'
                               checked='checked'/>
                    </div>
                    <div class="card-detail left">
                        <div class='card-detail-label'>Card Type</div>
                        <label for="simp-saved-cc-radio"
                               class='card-detail-text'>{$customer_details->card->type|escape:'htmlall':'UTF-8'}</label>
                    </div>
                    <div class="card-detail left">
                        <div class='card-detail-label'>Card Number</div>
                        <div class='card-detail-text'>xxxx - xxxx - xxxx
                            - {$customer_details->card->last4|escape:'htmlall':'UTF-8'}</div>
                    </div>
                    <div class="card-detail left">
                        <div class='card-detail-label'>Expiry Date</div>
                        <div class='card-detail-text'>
                        <span class='left'>{$customer_details-> card->expMonth|escape:'htmlall':'UTF-8'}
                            / {$customer_details->card->expYear|escape:'htmlall':'UTF-8'}</span>

                            <div id="cc-deletion-container" class="right center">
                                <div>
                                    <img id='trash-icon' src="{$module_dir|escape}views/img/trash.png" alt="trash icon"
                                         title="Delete Card"/>
                                </div>
                                <div id="cc-confirm-deletion">
                                    <div class='small pad-botom'>Delete Card?</div>
                                    <div>
                                        <span id="confirm-cc-deletion">Yes</span>
                                        <span id="cancel-cc-deletion">No</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <div id="cc-deletion-msg">Your card has been deleted: <span id="cc-undo-deletion-lnk"
                                                                                   class='underline'>Undo <img
                                alt="Secure Icon" class="secure-icon"
                                src="{$module_dir|escape}views/img/undo.png"/></span></div>
            {/if}
            <div id="new-card-container"
                 class='card-type-container clearfix {if !isset($show_saved_card_details)} no-saved {/if}'>
                {if isset($show_saved_card_details)}
                    <script>var simplifyHasSavedCard = true;</script>
                    <div class="clearfix">
                        <div class="first card-detail left">
                            <input id="simp-new-cc-radio" class="left" type="radio" name='cc-type' value='new'
                                   {if isset($smarty.get.simplify_error)}checked='checked'{/if} />
                        </div>
                        <div class="card-detail left">
                            <label for="simp-new-cc-radio" class='card-detail-text'>New Card</label>
                        </div>
                    </div>
                {/if}

                <div id="simplify-cc-details"
                    {if isset($show_saved_card_details)}
                        style="display: {if isset($smarty.get.simplify_error)}block;{else}none;{/if}"
                    {/if}>
                    <a name="simplify_error" class="hidden"></a>

                    <div style="display:none">{* the order button clicks this hidden button *}
                        <button id="simplify-hosted-payment-button"
                                data-sc-key="{$simplify_public_key|escape:'htmlall':'UTF-8'}"
                                {if isset($hosted_payment_name)}data-name="{$hosted_payment_name|escape:'htmlall':'UTF-8'}"{/if}
                                data-description="{$hosted_payment_description|escape:'htmlall':'UTF-8'}"
                                data-reference="{$hosted_payment_reference|escape:'htmlall':'UTF-8'}"
                                data-amount="{$hosted_payment_amount}"
                                data-operation="create.token"
                                {if isset($customer_name)}data-customer-name="{$customer_name|escape:'htmlall':'UTF-8'}"{/if}
                                data-color="{$overlay_color|escape:'htmlall':'UTF-8'}"
                                data-currency="{$currency_iso}">
                            Pay Now
                        </button>
                    </div>

                    {if isset($show_save_customer_details_checkbox)}
                        <div class="clear save-card-container">
                            <input type="checkbox" id="saveCustomer" name="saveCustomer">
                            <label class="save save-label" for="saveCustomer" id="saveCustomerLabel">
                                {l s='Save your card details for next time?' mod='simplifycommerce'}
                            </label>
                            <label class="save save-label" for="saveCustomer" id="updateCustomerLabel">
                                {l s='Update your saved card details?' mod='simplifycommerce'}
                            </label>
                        </div>
                    {/if}
                </div>
            </div>
            <div id="simplify-test-mode-msg" class="test-msg">( TEST PAYMENT )</div>
            <input type="hidden" name="hostedPayments" value="true"/>
        </form>
    </div>
</div>
