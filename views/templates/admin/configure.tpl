{*
* 2007-2014 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2014 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

{if ($warnings|count > 0)}
	<div class="alert alert-warning" id="algolia-alerts">
		<ul class="list-unstyled">
			{foreach from=$warnings item=message}
				<li>{$message}</li>
			{/foreach}
		</ul>
	</div>
{/if}

{if isset($sync_error)}
	<div class="alert alert-danger" id="algolia-alerts">
		<ul class="list-unstyled">
			{foreach from=$sync_error item=message}
				<li>{$message}</li>
			{/foreach}
		</ul>
	</div>
{/if}

{if isset($success)}
	<div class="alert alert-success" id="algolia-alerts">
		<ul class="list-unstyled">
			{foreach from=$success item=message}
				<li>{$message}</li>
			{/foreach}
		</ul>
	</div>
{/if}

<!-- Nav tabs -->
<ul class="nav nav-tabs" role="tablist">
	<li class="active"><a href="#template_1" role="tab" data-toggle="tab">Description</a></li>
	<li><a href="#settings_template" role="tab" data-toggle="tab">Settings</a></li>
	<li><a href="#sync_template" role="tab" data-toggle="tab">Sync</a></li>
</ul>

<!-- Tab panes -->
<div class="tab-content">
	<div class="tab-pane active" id="template_1">{include file='./description.tpl'}</div>
	<div class="tab-pane" id="settings_template">{$settings_form}</div>
	<div class="tab-pane" id="sync_template">{$sync_form}</div>
</div>
