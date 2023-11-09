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
<link href="{$module_dir|escape}views/css/style.css" rel="stylesheet" type="text/css" media="all"/>
<link href="{$module_dir|escape}views/css/spectrum.css" rel="stylesheet" type="text/css" media="all"/>
<link href="//fonts.googleapis.com/css?family=Lato:100,300,400,700,900" rel="stylesheet"/>
<script type="text/javascript" src="{$module_dir|escape}views/js/spectrum.js"></script>

<div class="simplify-module-wrapper">
    <div class="formContainer">
        <section class="technical-checks">
            {if $requirements['result']}
                <div class="conf">
                    <h3>{l s='Good news! Everything looks to be in order, start accepting card payments now.' mod='simplifycommerce'}</h3>
                </div>
            {else}
                <h3>{l s='Unfortunately, at least one issue is preventing you from using Simplify Commerce. Please fix the issue and reload this page.' mod='simplifycommerce'}</h3>
                <h2>{l s='Technical Checks' mod='simplifycommerce'}</h2>
                <table cellspacing="0" cellpadding="0" class="simplify-technical">
                    {foreach from=$requirements key=k item=requirement}
                        {if $k != 'result'}
                            <tr>
                                <td>
                                    {if $requirement['result']}
                                        <img src="{$ok_icon_link}" alt="ok"/>
                                    {else}
                                        <img src="{$nok_icon_link}" alt="not ok"/>
                                    {/if}
                                </td>
                                <td class="simplify-require-text">
                                    {$requirement['name']|escape:'htmlall':'UTF-8'}<br/>
                                    {if !$requirement['result'] && isset($requirement['resolution'])}
                                        {Tools::safeOutput($requirement['resolution']|escape:'htmlall':'UTF-8',true)}
                                        <br/>
                                    {/if}
                                </td>
                            </tr>
                        {/if}
                    {/foreach}
                </table>
            {/if}
        </section>
        <br/>
        <form action="{$request_uri|escape:'UTF-8'}" method="post">
            <section class="simplify-settings">
                <h2>API Key Mode</h2>

                <div class="half container">
                    <div class="keyModeContainer">
                        <input class="radioInput" type="radio" name="simplify_mode" value="0"
                                {if !$simplify_mode}
                                    checked="checked"
                                {/if}
                        /><span>Test Mode</span>
                        <input class="radioInput" type="radio" name="simplify_mode" value="1"
                                {if $simplify_mode}
                                    checked="checked"
                                {/if}
                        /><span>Live Mode</span>
                    </div>
                    <p>

                    <div class="bold">{l s='Test Mode' mod='simplifycommerce'}</div>
                    All transactions in test mode are test payments. You can test your installation using card numbers
                    from our
                    <a href="https://www.simplify.com/commerce/docs/tutorial/index#testing" target="_blank">list of test
                        card numbers</a>.
                    You cannot process real payments in test mode, so all other card numbers will be declined.</p>
                    <p>

                    <div class="bold">{l s='Live Mode' mod='simplifycommerce'}</div>
                    All transactions made in live mode are real payments and will be processed accordingly.</p>
                </div>
                <h2>{l s='Set Your API Keys' mod='simplifycommerce'}</h2>

                <div class="account-mode container">
                    <p>Obtain both your private and public API Keys from: Account Settings -> API Keys and supply them
                        below.</p>
                </div>
                <div class="clearfix api-key-container">
                    <div class="clearfix api-key-title">
                        <div class="left"><h4 class="ng-binding">{l s='Test' mod='simplifycommerce'}</h4></div>
                    </div>
                    <div class="api-keys">
                        <div class="api-key-header clearfix">
                            <div class="left api-key-key">{l s='Private Key' mod='simplifycommerce'}</div>
                            <div class="left api-key-key">{l s='Public Key' mod='simplifycommerce'}</div>
                        </div>
                        <div class="api-key-box clearfix">
                            <div class="left api-key-key api-key ng-binding">
                                <input type="password"
                                       name="simplify_private_key_test"
                                       value="{$private_key_test|escape:'htmlall':'UTF-8'}"/>
                            </div>
                            <div class="left api-key-key api-key ng-binding">
                                <input type="text"
                                       name="simplify_public_key_test"
                                       value="{$public_key_test|escape:'htmlall':'UTF-8'}"/>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="clearfix api-key-container">
                    <div class="clearfix api-key-title">
                        <div class="left"><h4 class="ng-binding">{l s='Live' mod='simplifycommerce'}</h4></div>
                    </div>
                    <div class="api-keys">
                        <div class="api-key-header clearfix">
                            <div class="left api-key-key">{l s='Private Key' mod='simplifycommerce'}</div>
                            <div class="left api-key-key">{l s='Public Key' mod='simplifycommerce'}</div>
                        </div>
                        <div class="api-key-box clearfix">
                            <div class="left api-key-key api-key ng-binding">
                                <input type="password"
                                       name="simplify_private_key_live"
                                       value="{$private_key_live|escape:'htmlall':'UTF-8'}"/>
                            </div>
                            <div class="left api-key-key api-key ng-binding">
                                <input type="text"
                                       name="simplify_public_key_live"
                                       value="{$public_key_live|escape:'htmlall':'UTF-8'}"/>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="payment-configuration clearfix">
                    <div class="left">
                        <h2>{l s='Payments Configuration' mod='simplifycommerce'}</h2>
                    </div>

                    <div class="clearfix"></div>

                    <div class="left half option">
                        <h3>{l s='Enable Payment Method' mod='simplifycommerce'}</h3>

                        <p>
                            {l s='Choose Yes to activate the Payment Method on checkout. ' mod='simplifycommerce'}
                        </p>

                        <div class="account-mode container">
                            <div class="yes-no-container">
                                <input class="radioInput" type="radio" name="simplify_enabled_payment_window" value="1"
                                        {if $enabled_payment_window == 1}
                                            checked="checked"
                                        {/if}
                                /><span>Yes</span>
                                <input class="radioInput" type="radio" name="simplify_enabled_payment_window" value="0"
                                        {if $enabled_payment_window == 0}
                                            checked="checked"
                                        {/if}
                                /><span>No</span>
                            </div>
                        </div>
                    </div>

                    <div class="left half option">
                        <h3>{l s='Save Customer Details' mod='simplifycommerce'}</h3>

                        <p>
                            {l s='Enable customers to save their card details securely on Simplify servers for future transactions.' mod='simplifycommerce'}
                        </p>

                        <div class="yes-no-container">
                            <input class="radioInput" type="radio" name="simplify_save_customer_details" value="1"
                                    {if $save_customer_details == 1}
                                        checked="checked"
                                    {/if}
                            /><span>Yes</span>
                            <input class="radioInput" type="radio" name="simplify_save_customer_details" value="0"
                                    {if $save_customer_details == 0}
                                        checked="checked"
                                    {/if}
                            /><span>No</span>
                        </div>
                    </div>

                    {foreach $statuses_options as $status_options}
                        <div class="left half option">
                            <h3>{$status_options['label']|escape:'htmlall':'UTF-8'}</h3>
                            <p>Choose the status for an order once the payment has been successfully processed by
                                Simplify.</p>
                            <div>
                                <select name="{$status_options['name']|escape:'htmlall':'UTF-8'}">
                                    {foreach $statuses as $status}
                                        <option value="{$status['id_order_state']|escape:'htmlall':'UTF-8'}"
                                                {if $status['id_order_state'] == $status_options['current_value']}
                                                    selected="selected"
                                                {/if}
                                        >{$status['name']|escape:'htmlall':'UTF-8'}</option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                    {/foreach}

                    <div class="left half option">
                        <h3>{l s='Transaction Mode' mod='simplifycommerce'}</h3>
                        <p>{l s='In “Payment” mode, the customer is charged immediately. In “Authorize” mode, the transaction is only authorized and the capturing of funds is a manual process that you do using the Prestashop admin panel.' mod='simplifycommerce'}</p>
                        <div>
                            <select name="simplify_txn_mode">
                                {foreach $txn_mode_options as $_txn_mode}
                                    <option value="{$_txn_mode['value']|escape:'htmlall':'UTF-8'}"
                                            {if $txn_mode === $_txn_mode['value']}
                                                selected="selected"
                                            {/if}
                                    >{$_txn_mode['label']|escape:'htmlall':'UTF-8'}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>

                    <div class="left half option">
                        <div>
                            <h3>{l s='Payment Method Title' mod='simplifycommerce'}</h3>
                            <div class="container">
                                <p>{l s='Change the payment method title displayed in the frontend.' mod='simplifycommerce'}</p>
                                <input name="simplify_payment_title" type="text" class="table_grid"
                                       value="{$payment_title|escape:'htmlall':'UTF-8'}"/>
                            </div>
                        </div>
                    </div>
                    <div class="left half option">
                        <h3>{l s='Hosted Payment Method' mod='simplifycommerce'}</h3>
                        <p>
                            {l s='This option defines how the Customer will enter new credit card details.' mod='simplifycommerce'}
                        </p>
                        <select name="simplify_payment_option">
                            {foreach $payment_options as $_payment_option}
                                <option value="{$_payment_option['value']|escape:'htmlall':'UTF-8'}"
                                        {if $payment_option === $_payment_option['value']}
                                            selected="selected"
                                        {/if}
                                >{$_payment_option['label']|escape:'htmlall':'UTF-8'}</option>
                            {/foreach}
                        </select>
                    </div>

                    <div class="left half option">
                        <h3 for="modal-overlay-color" class="modal-overlay">
                            {l s='Button color' mod='simplifycommerce'}:</h3>
                        <input name="simplify_overlay_color"
                               type="text"
                               id="modal-overlay-color"
                               size="8"
                               value="{$overlay_color|escape:'htmlall':'UTF-8'}"/>
                        <input id="colorSelector" type="text"
                               value="{$overlay_color|escape:'htmlall':'UTF-8'}"/>
                    </div>

                </div>
                <div class="clearfix">
                    <input type="submit"
                           class="settings-btn btn right"
                           name="SubmitSimplify"
                           value="{l s='Save Settings' mod='simplifycommerce'}"/>
                </div>
            </section>
        </form>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function () {
        var $modalOverlayColor = $('#modal-overlay-color');
        var $colorSelector = $("#colorSelector");

        function changeColor(color) {
            $modalOverlayColor.val(color.toHexString());
        }

        $colorSelector.spectrum({
            preferredFormat: "hex",
            showInput: true,
            move: changeColor,
            change: changeColor
        });

        $modalOverlayColor.change(function () {
            $colorSelector.spectrum('set', $(this).val());
        });
    });
</script>
