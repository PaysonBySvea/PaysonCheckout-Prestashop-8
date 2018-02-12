{extends "$layout"}

{block name="content"}
  <section>
      {if isset($pco2Snippet)}
        <div id="iframepayson">
            {$pco2Snippet nofilter}
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
