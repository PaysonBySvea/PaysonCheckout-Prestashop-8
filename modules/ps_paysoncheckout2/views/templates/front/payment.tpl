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
{extends "$layout"}
{block name="content"}
    
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
        var txtProduct = '{l s='product' js=1 mod='ps_paysoncheckout2'}';
        var txtProducts = '{l s='products' js=1 mod='ps_paysoncheckout2'}';
        var freeShippingTranslation = '{l s='Free Shipping!' js=1 mod='ps_paysoncheckout2'}';
        // ]]>
    </script>
 {/if}
 
    <section class="payson-iframe-section">
        {if isset($payson_errors)}
            <div class="alert alert-warning">
                <p>
                    {$payson_errors|escape:'html':'UTF-8'}
                </p>
            </div>
        {/if}
        <div id="paysonpaymentwindow">
            {$payson_checkout nofilter}{* IFRAME, no escaping possible *}
        </div>
    </section>
{/block}
