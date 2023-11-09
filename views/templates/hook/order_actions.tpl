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
<div class="panel">
	<div class="panel-heading">
		<i class="icon-cogs"></i>
        {l s='Online Payment Actions' mod='simplifycommerce'}
	</div>
	<div>
		<h4>Gateway Order ID: {$simplify_order_ref}</h4>
	</div>
    {if $can_action}
		<div class="well hidden-print">
            {if $can_capture}
				<a id="desc-order-capture_payment" class="btn btn-default" href="{$link->getAdminLink('AdminSimplify')|escape:'html':'UTF-8'}&amp;action=capture&amp;id_order={$order->id|intval}">
					<i class="icon-exchange"></i>
                    {l s='Capture Payment' mod='simplifycommerce'}
				</a>
            {/if}

            {if $can_void}
				<a id="desc-order-void_payment" class="btn btn-default" href="{$link->getAdminLink('AdminSimplify')|escape:'html':'UTF-8'}&amp;action=void&amp;id_order={$order->id|intval}">
					<i class="icon-remove"></i>
                    {l s='Reverse Authorization' mod='simplifycommerce'}
				</a>
            {/if}
		</div>
    {/if}
</div>
