{**
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
*}
<div>
<script>
    var simplifyPublicKey = "{$simplify_public_key|escape:'htmlall':'UTF-8'}",
        simplifyFirstname = "{$firstname|escape:'htmlall':'UTF-8'}",
        simplifyLastname = "{$lastname|escape:'htmlall':'UTF-8'}",
        simplifyCity = "{$city|escape:'htmlall':'UTF-8'}",
        simplifyAddress1 = "{$address1|escape:'htmlall':'UTF-8'}",
        simplifyAddress2 = "{$address2|escape:'htmlall':'UTF-8'}",
        simplifyState = "{$state|escape:'htmlall':'UTF-8'}",
        simplifyPostcode = "{$postcode|escape:'htmlall':'UTF-8'}";
    {if $payment_mode == 'hosted_payments'}
    var simplifyPaymentMode = "hosted_payments";
    {else}
    var simplifyPaymentMode = "standard";
    {/if}
</script>
<div class="simplifyFormContainer box {if !isset($show_saved_card_details)} no-saved {/if}">
<div class="clearfix">
    <div class="error-msg">
        <span id="simplify-no-keys-msg" class="msg-container hidden">Payment Form not configured correctly. Please contact support.</span>
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
            <input id="simp-saved-cc-radio" class="left" type="radio" name='cc-type' value='old' checked='checked'/>
        </div>
        <div class="card-detail left">
            <div class='card-detail-label'>Card Type</div>
            <label for="simp-saved-cc-radio" class='card-detail-text'>{$customer_details->card->type|escape:'htmlall':'UTF-8'}</label>
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
                             title="Delete Credit Card"/>
                    </div>
                    <div id="cc-confirm-deletion">
                        <div class='small pad-botom'>Delete Credit Card?</div>
                        <div>
                            <span id="confirm-cc-deletion">Yes</span>
                            <span id="cancel-cc-deletion">No</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <div id="cc-deletion-msg">Your credit card has been deleted: <span id="cc-undo-deletion-lnk"
                                                                       class='underline'>Undo <img
                    alt="Secure Icon" class="secure-icon" src="{$module_dir|escape}views/img/undo.png"/></span></div>
{/if}
<div id="new-card-container" class='card-type-container clearfix {if !isset($show_saved_card_details)} no-saved {/if}'>
    {if isset($show_saved_card_details)}
        <script>var simplifyHasSavedCard = true;</script>
        <div class="clearfix">
            <div class="first card-detail left">
                <input id="simp-new-cc-radio" class="left" type="radio" name='cc-type' value='new'
                       {if isset($smarty.get.simplify_error)}checked='checked'{/if} />
            </div>
            <div class="card-detail left">
                <label for="simp-new-cc-radio" class='card-detail-text'>New Credit Card</label>
            </div>
        </div>
    {/if}
    <div
            id="simplify-cc-details"
            {if isset($show_saved_card_details)}
                style="display: {if isset($smarty.get.simplify_error)}block;{else}none;{/if}"
            {/if}
    >
        <a name="simplify_error" class="hidden"></a>
        {if $payment_mode == 'hosted_payments'}
            <div style="display:none">{* the order button clicks this hidden button *}
                <button id="simplify-hosted-payment-button"
                        data-sc-key="{$simplify_public_key|escape:'htmlall':'UTF-8'}"
                        data-name="{$hosted_payment_name|escape:'htmlall':'UTF-8'}"
                        data-description="{$hosted_payment_description|escape:'htmlall':'UTF-8'}"
                        data-reference="{$hosted_payment_reference|escape:'htmlall':'UTF-8'}"
                        data-amount="{$hosted_payment_amount}"
                        data-operation="create.token"
                        data-customer-name="{$firstname|escape:'htmlall':'UTF-8'} {$lastname|escape:'htmlall':'UTF-8'}"
                        data-color="{$overlay_color|escape:'htmlall':'UTF-8'}"
                        data-currency="{$currency_iso}"
                >
                    Pay Now
                </button>
            </div>
        {else}
            <label>Card Number</label>
            <br/>
            <input type="text" size="20" autocomplete="off" class="simplify-card-number" autofocus/>
            <div>
                <div class="block-left">
                    <div class="clear"></div>
                    <label>Expiration (MM YYYY)</label>
                    <br/>

                    <div>{html_select_date display_days=false end_year='+20'|escape:'htmlall':'UTF-8'}</div>
                </div>
                <div>
                    <label>CVC</label><br/>
                    <input type="text" size="4" autocomplete="off" class="simplify-card-cvc" maxlength="4"/>
                    <a href="javascript:void(0)" class="simplify-card-cvc-info no-border">
                        What's this?
                        <div class="cvc-info">
                            The CVC (Card Validation Code) is a 3 or 4 digit code on the reverse side of Visa,
                            MasterCard and Discover cards and on the front of American Express cards.
                        </div>
                    </a>
                </div>
            </div>
        {/if}
        {if isset($show_save_customer_details_checkbox)}
            <div class="clear">
                <input type="checkbox" id="saveCustomer" name="saveCustomer">
                <label class="save" for="saveCustomer" id="saveCustomerLabel">Save your credit card details for next time?</label>
                <label class="save" for="saveCustomer" id="updateCustomerLabel">Update your saved card details?</label>
            </div>
        {/if}

        {if $payment_mode != 'hosted_payments'}
            <div>
                <img alt="Secure Icon" class="payment-cards" src="{$module_dir|escape}views/img/credit-cards.png"/>
            </div>
        {/if}
    </div>
</div>
<div id="simplify-test-mode-msg" class="test-msg">( TEST PAYMENT )</div>
{if $payment_mode == 'hosted_payments'}
    <input type="hidden" name="hostedPayments" value="true"/>
{/if}
<div>
</div>
</form>
</div>
</div>