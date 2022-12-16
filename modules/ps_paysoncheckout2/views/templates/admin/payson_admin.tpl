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
<script type="text/javascript" src="{$module_dir|escape:'htmlall':'UTF-8'}views/js/admin.js"></script>
<div class="row">
    <div class="col-xs-12">
            <div class="panel">
                    <div class="panel-heading"><i class="icon-home"></i> {l s='Payson Checkout' mod='ps_paysoncheckout2'}</div>
                    <div class="row">
                            <p>{l s='Offer secure payments with Payson. Customers can pay by invoice, partial payments, card or internet bank' mod='ps_paysoncheckout2'}</p>
                    </div>
            </div>
    </div>
    <div class="col-xs-4">
        <div class="panel">
                <div class="panel-heading"><i class="icon-question"></i> {l s='Documentation' mod='ps_paysoncheckout2'}</div>
                <div class="row">
                        <p>
                                <a href="{$module_dir|escape:'htmlall':'UTF-8'}doc/readme_sv.pdf" target="_blank" id="documentation-en" class="btn btn-default" title="{l s='Documentation' mod='ps_paysoncheckout2'}">
                                        <i class="icon-file-text"></i> {l s='Documentation' mod='ps_paysoncheckout2'} SV
                                </a>
                        
                                <a href="{$module_dir|escape:'htmlall':'UTF-8'}doc/readme_en.pdf" target="_blank" id="documentation-en" class="btn btn-default" title="{l s='Documentation' mod='ps_paysoncheckout2'}">
                                        <i class="icon-file-text"></i> {l s='Documentation' mod='ps_paysoncheckout2'} EN
                                </a>
                        </p>
                        
                </div>
        </div>
    </div>
    <div class="col-xs-4">
            <div class="panel">
                    <div class="panel-heading"><i class="icon-info"></i> {l s='Payson TestAgent' mod='ps_paysoncheckout2'}</div>
                    <div class="row">
                            <p>
                                    <a href="http://test-www.payson.se/testaccount/create/" target="_blank" id="test_agent" class="btn btn-default" title="{l s='Create TestAgent' mod='ps_paysoncheckout2'}">
                                            <i class="icon-user"></i> {l s='Create TestAgent' mod='ps_paysoncheckout2'}
                                    </a>
                            </p>
                    </div>
            </div>
    </div>
    <div class="col-xs-4">
            <div class="panel">
                    <div class="panel-heading"><i class="icon-info"></i> {l s='PaysonAccount' mod='ps_paysoncheckout2'}</div>
                    <div class="row">
                            <p>
                                    <a href="https://account.payson.se/account/create/?type=b" target="_blank" id="payson_account" class="btn btn-default" title="{l s='Open PaysonAccount' mod='ps_paysoncheckout2'}">
                                            <i class="icon-user"></i> {l s='Open PaysonAccount' mod='ps_paysoncheckout2'}
                                    </a>
                            </p>
                    </div>
            </div>
    </div>
</div>
{if $isSaved}	
	<div class="alert alert-success">
		{l s='Settings updated' mod='ps_paysoncheckout2'}
	</div>
{/if}
{if $errorMSG!=''}	
	<div class="alert alert-danger">
		 {$errorMSG|escape:'htmlall':'UTF-8'}
	</div>
{/if}
<div class="payson tabbable">
	<ul class="nav nav-tabs">
		<li class="active"><a href="#pane1" data-toggle="tab"><i class="icon-cogs"></i> {l s='Settings' mod='ps_paysoncheckout2'}</a></li>
                <li><a href="#pane2" data-toggle="tab"><i class="icon-cogs"></i> {l s='Custom CSS' mod='ps_paysoncheckout2'}</a></li>
	</ul>
	<div class="panel">
                <div class="tab-content">

                        <div id="pane1" class="tab-pane active">
                                <div class="tabbable row payson-admin">
                                        <div class="col-lg-12 tab-content">
                                                <div id="payson-admin" class="col-lg-12">
                                                        {html_entity_decode($commonform|escape:'htmlall':'UTF-8')}
                                                </div>
                                        </div>
                                </div>
                        </div>

                </div>
                     
                <div class="tab-content">
                        <div id="pane2" class="tab-pane">
                                <div class="tabbable row payson-admin">
                                        <div class="col-lg-12 tab-content">
                                                <div id="custom-css" class="col-lg-12">
                                                    {html_entity_decode($cssform|escape:'htmlall':'UTF-8')}
                                                </div>
                                        </div>
                                </div>
                        </div>
                </div>
        </div>
</div>