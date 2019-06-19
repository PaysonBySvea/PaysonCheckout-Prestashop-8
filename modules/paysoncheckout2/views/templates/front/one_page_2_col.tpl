{*
* 2019 Payson AB
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
*
*  @author    Payson AB <integration@payson.se>
*  @copyright 2019 Payson AB
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
{extends $layout}

{block name='content'}

{capture name=path}{l s='Checkout' mod='paysoncheckout2'}{/capture}

{if isset($custom_css) && $custom_css != ''}
<style>
    {$custom_css|escape:'html':'UTF-8'}
</style>
{/if}

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

{if isset($pcoUrl)}
    <script type="text/javascript">
        // <![CDATA[
        var pcourl = '{Tools::htmlentitiesDecodeUTF8($pcoUrl) nofilter}';
        var pco_checkout_id = '{$pco_checkout_id|escape:'javascript':'UTF-8'}';
        var id_cart = '{$id_cart|intval}';
        var validateurl = '{Tools::htmlentitiesDecodeUTF8($validateUrl) nofilter}';
        var currencyBlank = '{$currencyBlank|intval}';
        var currencySign = '{$currencySign|escape:'javascript':'UTF-8'}';
        var currencyRate = '{$currencyRate|floatval}';
        var currencyFormat = '{$currencyFormat|intval}';
        var txtProduct = '{l s='product' js=1 mod='paysoncheckout2'}';
        var txtProducts = '{l s='products' js=1 mod='paysoncheckout2'}';
        var freeShippingTranslation = '{l s='Free Shipping!' js=1 mod='paysoncheckout2'}';
        // ]]>
    </script>
{/if}

<div class="payson-cf payson-main op-2-col">
    <div id="payson_cart_summary_wrapp">
        <div class="cart-grid-body col-xs-12 col-lg-7">
           
            <div class="card cart-container">
                <div class="card-block">
                    <h1 class="h1">{l s='Shopping Cart' mod='paysoncheckout2'}</h1>
                </div>
                <hr class="separator">
                {include file='checkout/_partials/cart-detailed.tpl' cart=$cart}
            </div>
            
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
                
            <!-- terms and newsletter-->
            {if ((isset($conditions_to_approve) && $conditions_to_approve|count && isset($PAYSONCHECKOUT2_SHOW_TERMS) && $PAYSONCHECKOUT2_SHOW_TERMS) || (isset($PAYSONCHECKOUT2_NEWSLETTER) && $PAYSONCHECKOUT2_NEWSLETTER))}
                <div class="card cart-container terms-card terms-and-options">
                    <div class="card-block">
                        {if (isset($conditions_to_approve) && $conditions_to_approve|count && isset($PAYSONCHECKOUT2_SHOW_TERMS) && $PAYSONCHECKOUT2_SHOW_TERMS)}
                            <form id="conditions-to-approve" method="GET">
                              <ul>
                                {foreach from=$conditions_to_approve item="condition" key="condition_name"}
                                  <li>
                                    <div class="float-xs-left">
                                      <span class="custom-checkbox">
                                        <input  id    = "conditions_to_approve[{$condition_name}]"
                                                name  = "conditions_to_approve[{$condition_name}]"
                                                required
                                                type  = "checkbox"
                                                value = "1"
                                                class = "conditions_to_approve_checkbox ps-shown-by-js"
                                        >
                                        <span><i class="material-icons rtl-no-flip checkbox-checked">&#xE5CA;</i></span>
                                      </span>
                                    </div>
                                    <div class="condition-label">
                                      <label class="js-terms" for="conditions_to_approve[{$condition_name}]">
                                        {$condition nofilter}{* no escaping possible *}
                                      </label>
                                    </div>
                                  </li>
                                {/foreach}
                              </ul>
                            </form>
                        {/if}
                        {if isset($PAYSONCHECKOUT2_NEWSLETTER) && $PAYSONCHECKOUT2_NEWSLETTER}
                            <ul>
                                <li>
                                  <div class="float-xs-left">
                                    <span class="custom-checkbox">
                                      <input  id    = "newsletter_optin"
                                              name  = "newsletter_optin"
                                              type  = "checkbox"
                                              value = "1"
                                              class = "newsletter_optin_checkbox ps-shown-by-js"
                                      >
                                      <span><i class="material-icons rtl-no-flip checkbox-checked">&#xE5CA;</i></span>
                                    </span>
                                  </div>
                                  <div class="condition-label">
                                    <label for="newsletter_optin">
                                      {$newsletter_optin_text|escape:'html':'UTF-8'}
                                    </label>
                                  </div>
                                </li>
                            </ul>
                        {/if}
                    </div>
                </div>
            {/if}
                
            {if isset($free_shipping_price_amount) AND $free_shipping_price_amount>0}
                <div class="card cart-container">
                    <div class="card-block free-shipping">
                            {l s='Free shipping when you shop products for more than' mod='paysoncheckout2'}&nbsp;<strong>{Tools::displayPrice($free_shipping_price_amount)}</strong>&nbsp;{l s='excl. shipping.' mod='paysoncheckout2'}
                    </div>
                </div>
            {/if}
            
            {if isset($controllername) && (isset($delivery_options) && $delivery_options|@count != 0) || isset($hookDisplayBeforeCarrier) || isset($hookDisplayAfterCarrier)}
                <div class="card payson-carrier-card">
                    <div class="card-block">
                        <h1 class="h1">
                            {l s='Carrier' mod='paysoncheckout2'}
                        </h1>
                    </div>
                    <hr class="separator">
                    
                    {if isset($hookDisplayBeforeCarrier) && $hookDisplayBeforeCarrier != ''} 
                        <div id="hook-display-before-carrier" class="card-block">
                            {$hookDisplayBeforeCarrier nofilter}
                        </div>
                    {/if}
                    
                    {if (isset($delivery_options) && $delivery_options|@count != 0)}
                        <div class="card-block payson-carrier-card-block">
                            <form action="{$link->getModuleLink('paysoncheckout2', $controllername, [], true)|escape:'html':'UTF-8'}" method="post" id="pcocarrier">
                            <ul class="payson-select-list has-tooltips">
                                {foreach from=$delivery_options item=carrier key=carrier_id}
                                <li class="payson-select-list__item li-delivery-option-{$carrier.id} {if $delivery_option == $carrier_id}selected{/if}">
                                    <input type="radio" class="hidden_pco_radio" name="delivery_option[{$id_address}]" id="delivery_option_{$carrier.id}" value="{$carrier_id}"{if $delivery_option == $carrier_id} checked{/if}>
                                    <label for="delivery_option_{$carrier.id}" class="payson-select-list__item__label">
                                        <div class="row payson-carrier-info">
                                            <div class="col-sm-5 col-xs-12">
                                                <div class="row">
                                                    {if $carrier.logo}
                                                        <div class="payson carrier-logo col-xs-3">
                                                            <img src="{$carrier.logo}" alt="{$carrier.name}" />
                                                        </div>
                                                    {/if}
                                                    <div class="payson carrier-name {if $carrier.logo}col-xs-9{else}col-xs-12{/if}">
                                                        {$carrier.name}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="payson carrier-delay col-sm-4 col-xs-12">
                                                {$carrier.delay}
                                            </div>
                                            <div class="payson carrier-price col-sm-3 col-xs-12">
                                                {if $carrier.price && !$free_shipping}
                                                    {Tools::displayPrice($carrier.price_with_tax)}
                                                {else}
                                                    {l s='Free!' mod='paysoncheckout2'}
                                                {/if}
                                            </div>
                                        </div>
                                    </label>
                                </li>
                                {/foreach}
                            </ul>
                            </form>
                        </div>
                    {/if}
                    
                    {if isset($hookDisplayAfterCarrier) && $hookDisplayAfterCarrier != ''} 
                        <div id="hook-display-after-carrier" class="card-block">
                            {$hookDisplayAfterCarrier nofilter}
                        </div>
                    {/if}
                </div>
            {/if}
            
            {if isset($controllername)}
                <div class="card payson-message-card">
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
            {/if}
            
            {if isset($controllername) && $giftAllowed==1}
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
        
        <div class="cart-grid-body col-xs-12 col-lg-5">
            <div class="card card-payson-pay">
                <div class="card-block">
                    <h1 class="h1">
                        {l s='Payment' mod='paysoncheckout2'}
                    </h1>
                    {if isset($PAYSONCHECKOUT2_SHOW_OTHER_PAYMENTS) && $PAYSONCHECKOUT2_SHOW_OTHER_PAYMENTS}
                        <span class="wrap-alternative-methods">
                            <a href="{$link->getPageLink('order', true, null, 'step=1')|escape:'html':'UTF-8'}" class="alternative-methods" title="{l s='Other payment methods' mod='paysoncheckout2'}">
                                <span>{l s='Other payment methods' mod='paysoncheckout2'}</span>
                            </a>
                        </span>
                    {/if}   
                </div>
                <hr class="separator">

                <div id="paysonpaymentwindow"></div>
            </div> 
        </div>
    </div>
</div>
<div class="modal fade" id="modal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="{l s='Close' d='Shop.Theme.Global'}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body"></div>
        </div>
    </div>
</div>
{/block}