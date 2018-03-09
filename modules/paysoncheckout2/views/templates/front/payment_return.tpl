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

{block name="content"}
  <section>
      {if isset($payson_checkout)}
        <div id="iframepayson">
            {$payson_checkout nofilter}{* IFRAME, no escaping possible *}
        </div>
      
      {else}
<p>
    {if isset($payson_error)}
        <div class="alert alert-warning">
            {$payson_error|escape:'html':'UTF-8'}
        </div>
    {/if}
</p>
    {/if}
  </section>
{/block}
