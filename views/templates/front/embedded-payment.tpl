{**
* Copyright (c) 2017-2019 Mastercard
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
            enabledEmbedded = "{$enabled_embedded|escape:'htmlall':'UTF-8'}";
    </script>
    <div class="simplifyFormContainer box {if !isset($show_saved_card_details)} no-saved {/if}">
        <div class="clearfix">
            <div class="error-msg">
                <span id="simplify-embedded-no-keys-msg" class="msg-container hidden">Payment Form not configured correctly. Please contact support.</span>
            </div>
        </div>

        <div class="simplify-payment-errors">{if isset($smarty.get.simplify_error)}{$smarty.get.simplify_error|escape:'htmlall':'UTF-8'}{/if}</div>
        <form action="{$module_dir|escape}payment.php" method="POST" id="simplify-embedded-payment-form">
            <div id="new-embedded-card-container"
                 class='card-type-container clearfix {if !isset($show_saved_card_details)} no-saved {/if}'>
                <div id="simplify-embedded-cc-details">

                    <iframe width="100%"
                            height="450px"
                            name="{$hosted_payment_name|escape:'htmlall':'UTF-8'}"
                            data-role="embedded_pay"
                            data-sc-key="{$simplify_public_key|escape:'htmlall':'UTF-8'}"
                            data-name="{$hosted_payment_name|escape:'htmlall':'UTF-8'}"
                            data-description="{$hosted_payment_description|escape:'htmlall':'UTF-8'}"
                            data-reference="{$hosted_payment_reference|escape:'htmlall':'UTF-8'}"
                            data-amount="{$hosted_payment_amount}"
                            {if isset($customer_name)}data-customer-name="{$customer_name|escape:'htmlall':'UTF-8'}"{/if}
                            data-color="{$overlay_color|escape:'htmlall':'UTF-8'}"
                            data-currency="{$currency_iso}"
                            data-operation="create.token"
                    >
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
            <div id="simplify-embedded-test-mode-msg" class="test-msg">( TEST PAYMENT )</div>
            <input type="hidden" name="hostedPayments" value="true"/>
        </form>
    </div>
</div>
