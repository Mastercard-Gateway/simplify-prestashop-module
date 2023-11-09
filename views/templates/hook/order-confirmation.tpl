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
{if $simplify_order.valid == 1}
    <div class="conf confirmation">{l s='Congratulations, your payment has been approved and your order has been saved under the reference' mod='simplifycommerce'} <b>{$simplify_order.reference|escape:html:'UTF-8'}</b>.</div>
{else}
    <div class="error">{l s='Sorry, unfortunately an error occured during the transaction.' mod='simplifycommerce'}<br /><br />
        {l s='Please double-check your card details and try again or feel free to contact us to resolve this issue.' mod='simplifycommerce'}<br /><br />
        ({l s='Your Order\'s Reference:' mod='simplifycommerce'} <b>{$simplify_order.reference|escape:html:'UTF-8'}</b>)</div>
{/if}
