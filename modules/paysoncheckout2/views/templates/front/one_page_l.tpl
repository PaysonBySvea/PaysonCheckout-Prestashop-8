{*
* 2018 Payson AB
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
*
*  @author    Payson AB <integration@payson.se>
*  @copyright 2018 Payson AB
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
{extends $layout}

{block name='content'}

{capture name=path}{l s='Checkout' mod='paysoncheckout2'}{/capture}

{if isset($payson_errors)}
<div class="payson-infobox">
    {$payson_errors|escape:'html':'UTF-8'}
</div>
{/if}

{if isset($vouchererrors) && $vouchererrors!=''}
<div class="alert alert-warning">
	{$vouchererrors|escape:'html':'UTF-8'}
</div>
{/if}
	
<script type="text/javascript">
    // <![CDATA[
    var pcourl = '{$pcoUrl|escape:'javascript':'UTF-8'}';
    var pco_checkout_id = '{$pco_checkout_id|escape:'javascript':'UTF-8'}';
    var id_cart = '{$id_cart|intval}';
    var validateurl = '{$validateUrl|escape:'javascript':'UTF-8'}';
    var currencyBlank = '{$currencyBlank|intval}';
    var currencySign = '{$currencySign|escape:'javascript':'UTF-8'}';
    var currencyRate = '{$currencyRate|floatval}';
    var currencyFormat = '{$currencyFormat|intval}';
    var txtProduct = '{l s='product' js=1 mod='paysoncheckout2'}';
    var txtProducts = '{l s='products' js=1 mod='paysoncheckout2'}';
    var freeShippingTranslation = '{l s='Free Shipping!' js=1 mod='paysoncheckout2'}';
    // ]]>
</script>
<div class="payson-cf payson-main">
    <div id="payson_cart_summary_wrapp">
        <div class="cart-grid-body col-xs-12 col-lg-8 left-col">
           
                <div class="card cart-container">
                    <div class="card-block">
                        <h1 class="h1">{l s='Shopping Cart' mod='paysoncheckout2'}</h1>
                    </div>
                    <hr class="separator">
                    {include file='checkout/_partials/cart-detailed.tpl' cart=$cart}
                </div>
            
                {if isset($left_to_get_free_shipping) AND $left_to_get_free_shipping>0}
                    <div class="card cart-container">
                        <div class="payson-infobox">
                                {l s='Shop for' mod='paysoncheckout2'}&nbsp;<strong>{Tools::displayPrice($left_to_get_free_shipping)}</strong>&nbsp;{l s='more, and you will qualify for free shipping.' mod='paysoncheckout2'}
                        </div>
                    </div>
                {/if}

                <div class="card card-payson-pay">
                    <div class="card-block">
                        <h1 class="h1">
                            {l s='Payment' mod='paysoncheckout2'}
                            <span>
                                {if isset($PAYSONCHECKOUT2_SHOW_OTHER_PAYMENTS) && $PAYSONCHECKOUT2_SHOW_OTHER_PAYMENTS}
                                    <a
                                        href="{$link->getPageLink('order', true, null, 'step=1')|escape:'html':'UTF-8'}"
                                        class="alternative_methods"
                                        title="{l s='Other payment methods' mod='paysoncheckout2'}">
                                        <span>{l s='Other payment methods' mod='paysoncheckout2'}<i class="icon-chevron-right right"></i></span>
                                    </a>
                                {/if}
                            </span>
                        </h1>
                    </div>
                    <hr class="separator">
                    
                    <div id="paysonpaymentwindow">{$payson_checkout nofilter}{* HTML comment, no escaping necessary *}</div>
                </div>  
        </div>
        
        <div class="cart-grid-body col-xs-12 col-lg-4 right-col">
            {block name='cart_summary'}
                <div class="card cart-summary">

                    {block name='hook_shopping_cart'}
                        {hook h='displayShoppingCart'}
                    {/block}

                    {block name='cart_totals'}
                        {include file='checkout/_partials/cart-detailed-totals.tpl' cart=$cart}
                    {/block}
                </div>
            {/block}

            <div class="card">
                <div class="card-block">
                    <h1 class="h1">
                        {l s='Carrier' mod='paysoncheckout2'}
                    </h1>
                </div>
                <hr class="separator">
                    <div class="card-block">
                        <form action="{$link->getModuleLink('paysoncheckout2', $controllername, [], true)|escape:'html':'UTF-8'}" method="post" id="pcocarrier">
                        <ul class="payson-select-list has-tooltips">
                            {foreach from=$delivery_options item=carrier key=carrier_id}
                            <li class="payson-select-list__item {if $delivery_option == $carrier_id}selected{/if}">
                                <input type="radio" class="hidden_pco_radio" name="delivery_option[{$id_address}]" id="delivery_option_{$carrier.id}" value="{$carrier_id}"{if $delivery_option == $carrier_id} checked{/if}>
                                <label for="delivery_option_{$carrier.id}" class="payson-select-list__item__label">
                                    {*<span class="payson-select-list__item__status">
                                        <i class="icon-ok"></i>
                                    </span>*}
                                    <span class="payson-select-list__item__title">
                                        {$carrier.name|escape:'html':'UTF-8'}
                                    </span>
                                    <span class="payson-select-list__item__nbr">
                                        {if $carrier.price && !$free_shipping}
                                            {Tools::displayPrice($carrier.price_with_tax)}
                                        {else}
                                            {l s='Free!' mod='paysoncheckout2'}
                                        {/if}
                                    </span>

                                </label>
                            </li>
                            {/foreach}
                        </ul>
                        </form>
                    </div>
            </div>

            <div class="card">
                <form action="{$link->getModuleLink('paysoncheckout2', $controllername, [], true)|escape:'html':'UTF-8'}" method="post" id="pcomessage">
                    <div class="card-block">
                        <h1 class="h1 payson-click-trigger {if !$message.message}payson-click-trigger--inactive{/if}">
                            {l s='Message' mod='paysoncheckout2'}
                        </h1>
                    </div>
                    <hr class="separator">
                    <div class="pco-target" {if !$message.message}style="display: none;"{/if}>
                        <div class="card-block">
                            <p id="messagearea">
                                <textarea id="message" name="message" class="payson-input payson-input--area payson-input--full" placeholder="{l s='Add additional information to your order (optional)' mod='paysoncheckout2'}">{$message.message|escape:'htmlall':'UTF-8'}</textarea>
                                <button type="button" name="savemessagebutton" id="savemessagebutton" class="btn btn-primary">
                                    <span>{l s='Save' mod='paysoncheckout2'}</span>
                                </button>
                            </p>
                        </div>
                    </div>
                </form>
            </div>
                
            {if $giftAllowed==1}
                <div class="card">
                    <form action="{$link->getModuleLink('paysoncheckout2', $controllername, [], true)|escape:'html':'UTF-8'}" method="post" id="pcogift">
                        <div class="card-block">
                            <h1 class="h1 payson-click-trigger {if !$message.message}payson-click-trigger--inactive{/if}">
                                {l s='Gift-wrapping' mod='paysoncheckout2'}
                            </h1>
                        </div>
                        <hr class="separator">
                        <div class="pco-target" {if $gift_message == '' && (!isset($gift) || $gift==0)}style="display: none;"{/if}>
                            <div class="card-block">
                                <p id="giftmessagearea_long">
                                    <textarea id="gift_message" name="gift_message" class="payson-input payson-input--area payson-input--full" placeholder="{l s='Gift message (optional)' mod='paysoncheckout2'}">{$gift_message|escape:'htmlall':'UTF-8'}</textarea>
                                    <input type="hidden" name="savegift" id="savegift" value="1" />

                                    <span class="payson-check-group fl-r full-width">
                                        <input type="checkbox" class="giftwrapping_radio" id="gift" name="gift" value="1"{if isset($gift) AND $gift==1} checked="checked"{/if} />
                                        <span id="giftwrappingextracost">{l s='Wrapping cost:' mod='paysoncheckout2'} {Tools::displayPrice($gift_wrapping_price)}</span>
                                    </span>
                                    <button type="button" name="savegiftbutton" id="savegiftbutton" class="btn btn-primary">
                                        <span>{l s='Save' mod='paysoncheckout2'}</span>
                                    </button>
                                </p>
                            </div>
                        </div>
                    </form>
                </div>
            {/if}
        </div>
    </div>
</div>
{/block}