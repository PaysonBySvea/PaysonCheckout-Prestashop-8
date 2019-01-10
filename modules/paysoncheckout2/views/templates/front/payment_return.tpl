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
{extends "$layout"}

</div>
{block name="content"}
    <section class="payson-iframe-section">
        {if isset($payson_checkout)}
            {$HOOK_DISPLAY_ORDER_CONFIRMATION nofilter}{* no escaping possible *}
              <div id="paysonpaymentwindow">
                  {$payson_checkout nofilter}{* IFRAME, no escaping possible *}
              </div>
        {else}

            {if isset($payson_errors)}
                <div class="alert alert-warning">
                    <p>
                        {$payson_errors|escape:'html':'UTF-8'}
                    </p>
                </div>
            {/if}

        {/if}
    </section>
{/block}