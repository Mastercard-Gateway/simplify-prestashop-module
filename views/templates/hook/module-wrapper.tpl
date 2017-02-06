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
<link href="{$module_dir|escape}views/css/style.css" rel="stylesheet" type="text/css" media="all"/>
<link href="{$module_dir|escape}views/css/spectrum.css" rel="stylesheet" type="text/css" media="all"/>
<link href="//fonts.googleapis.com/css?family=Lato:100,300,400,700,900" rel="stylesheet"/>
<script type="text/javascript" src="{$module_dir|escape}views/js/spectrum.js"></script>

<div class="simplify-module-wrapper">
    <div class="simplify-module-header">
        <a href="https://www.simplify.com/" target="_blank" class="left">
            <img class="logo" src="//www.simplify.com/commerce/static/images/app-logo-pos.png"
                 alt="Simplify Commerce Logo" width="150" height="64"></a>

        <div class="header-title left">
            <h1>{l s='Start accepting payments now.' mod='simplifycommerce'}</h1>

            <h2>{l s='It’s that simple.' mod='simplifycommerce'}</h2>
        </div>
        <a href="https://www.simplify.com/commerce/partners/prestashop#/" target="_blank" class="btn right"><span>{l s='Sign up for free' mod='simplifycommerce'}</span></a>
    </div>
    <div class="section">
        <div class="clearfix">
            <div class="marketing left">
                <div class="w-container features item">
                    <img class="features item icon" src="//www.simplify.com/commerce/static/images/feature_signup.jpg"
                         alt="feature_signup.jpg">

                    <h1 class="features item h1">{l s='Easy sign up' mod='simplifycommerce'}</h1>

                    <p>{l s='Click the "Sign up for free" button and become a Simplify merchant for free.' mod='simplifycommerce'}</p>
                </div>
            </div>
            <div class="marketing left">
                <div class="w-container features item">
                    <img class="features item icon" src="{$module_dir|escape}views/img/feature_price.jpg"
                         alt="feature_signup.jpg">

                    <h1 class="features item h1">{l s='Simple pricing' mod='simplifycommerce'}</h1>

                    <p>
                        No setup fees.
                        <br>No refund costs.
                        <br>No monthly service fees.*
                        <br>
                        <br>
                        <span class="simplify-features-footnote">* Subject to $10 minimum monthly processing fee.</span>

                </div>
            </div>
            <div class="marketing left">
                <div class="w-container features item">
                    <img class="features item icon" src="//www.simplify.com/commerce/static/images/feature_funding.jpg"
                         alt="feature_signup.jpg">

                    <h1 class="features item h1">{l s='Two-day funding' mod='simplifycommerce'}</h1>

                    <p>{l s='Deposits are made into your account in two business days for most transactions.' mod='simplifycommerce'}</p>
                </div>
            </div>
        </div>
        <div class="clearfix">
            <div class="w-container admin-description-block">
                <b>Simplify Commerce</b>, built my MasterCard, a global leader in the payment industry, makes it easy for small businesses to accept online payments.
                From our hosted ‘Pay Now’ solution that allows merchants to share links socially to our mobile point of sale to recurring payment solutions, we include must-have features key to businesses.
                <ul>
                    <li>{l s='Omni-channel payment solution for website, mobile and eCommerce store' mod='simplifycommerce'}</li>
                    <li>{l s='Accepting major card brands' mod='simplifycommerce'}</li>
                    <li>{l s='Quick two-day funding' mod='simplifycommerce'}</li>
                    <li>{l s='Highest Level 1 PCI certification' mod='simplifycommerce'}</li>
                    <li>{l s='Simple eInvoicing' mod='simplifycommerce'}</li>
                    <li>{l s='Recurring billing for monthly subscriptions' mod='simplifycommerce'}</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="formContainer">
        <section class="technical-checks">
            {if $requirements['result']}
            <div class="conf">
                <h3>{l s='Good news! Everything looks to be in order, start accepting credit card payments now.' mod='simplifycommerce'}</h3>
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
                    <p>If you have not already done so, you can create an account by clicking the 'Sign up for free'
                        button in the top right corner.<br/>
                        Obtain both your private and public API Keys from: Account Settings -> API Keys and supply them
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
                            <div class="left api-key-key api-key ng-binding"><input type="password"
                                                                                    name="simplify_private_key_test"
                                                                                    value="{$private_key_test|escape:'htmlall':'UTF-8'}"/>
                            </div>
                            <div class="left api-key-key api-key ng-binding"><input type="text"
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
                            <div class="left api-key-key api-key ng-binding"><input type="password"
                                                                                    name="simplify_private_key_live"
                                                                                    value="{$private_key_live|escape:'htmlall':'UTF-8'}"/>
                            </div>
                            <div class="left api-key-key api-key ng-binding"><input type="text"
                                                                                    name="simplify_public_key_live"
                                                                                    value="{$public_key_live|escape:'htmlall':'UTF-8'}"/>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="clearfix">
                    <div class="left half">
                        <h2>{l s='Save Customer Details' mod='simplifycommerce'}</h2>

                        <div class="account-mode container">
                            <p>Enable customers to save their card details securely on Simplify's servers for future
                                transactions.</p>

                            <div class="saveCustomerDetailsContainer">
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
                    </div>
                    <div class="half container left">
                        {foreach $statuses_options as $status_options}
                            <h2>{$status_options['label']|escape:'htmlall':'UTF-8'}</h2>
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
                        {/foreach}
                        <div>
                        </div>
                    </div>
                </div>
                <div class="clearfix">
                    <div class="left">
                        <h2>{l s='Payment Mode' mod='simplifycommerce'}</h2>

                        <div class="container">
                            <table>
                                <tr>
                                    <td>
                                        <select id="simplify_payment_mode" name="simplify_payment_mode">
                                            <option value="hosted_payments"
                                                    {if $payment_mode == 'hosted_payment'}selected="selected"{/if}>
                                                Hosted Payments
                                            </option>
                                            <option value="standard"
                                                    {if $payment_mode == 'standard'}selected="selected"{/if}>Standard
                                            </option>
                                        </select>
                                    </td>
                                    <td id="modal-overlay-config">
                                        <label for="modal-overlay-color" class="modal-overlay">
                                            {l s='Hosted payments button color' mod='simplifycommerce'}:</label>
                                                <input
                                                        name="simplify_overlay_color"
                                                        type="text"
                                                        id="modal-overlay-color"
                                                        size="8"
                                                        value="{$overlay_color|escape:'htmlall':'UTF-8'}"/>
                                                <input
                                                        id="colorSelector" type="text"
                                                        value="{$overlay_color|escape:'htmlall':'UTF-8'}"/>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="hp-notes" colspan="2">
                                        To use hosted payments you must create a new API Key pair with the <b>'Enable hosted payments'</b> option selected.<br/>
                                        For more information, please visit this <a target="_new" href="https://www.simplify.com/commerce/docs/tools/hosted-payments">link</a>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="clearfix"><input type="submit" class="settings-btn btn right" name="SubmitSimplify"
                                             value="Save Settings"/></div>
    </div>
    </section>
    </form>
</div>
<script type="text/javascript">
    $(document).ready(function () {
        var $modalOverlayColor = $('#modal-overlay-color');
        var $colorSelector = $("#colorSelector");
        var $modalOverlayConfig = $('#modal-overlay-config');
        var $simplifyPaymentMode = $('#simplify_payment_mode');

        function updateSimplifySettings() {
            enableOrDisableOverlaySetting();
        }

        function enableOrDisableOverlaySetting() {
            var disable = $simplifyPaymentMode.val() === 'standard';
            $modalOverlayConfig.css('opacity', disable ? 0.6 : 1.0);
            $colorSelector.spectrum(disable ? 'disable' : 'enable');
            if (disable) {
                $modalOverlayColor.attr('disabled', true);
            }
            else {
                $modalOverlayColor.removeAttr('disabled');
            }
        }

        $simplifyPaymentMode.change(enableOrDisableOverlaySetting);

        $('input:radio[name=simplify_mode]').click(updateSimplifySettings);

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

        updateSimplifySettings();
    });
</script>
