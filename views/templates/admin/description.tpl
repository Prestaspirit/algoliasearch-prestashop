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

<div class="panel">
	<div class="row algolia-header">
		<div class="col-xs-6 col-md-4 text-center">
			<img src="{$module_dir|escape:'html':'UTF-8'}img/algolia.png" id="algolia-logo" />
		</div>
		<div class="col-xs-6 col-md-5 text-center">
			<h4>{l s='Build Realtime Search' mod='algolia'}</h4>
			<h4>{l s='A powerful API built for developers' mod='algolia'}</h4>
		</div>
		<div class="col-xs-12 col-md-3 text-center">
			<a href="https://www.algolia.com/users/sign_up" target="_blank" class="btn btn-primary" id="create-account-btn">{l s='Create an account now!' mod='algolia'}</a><br />
			{l s='Already have an account?' mod='algolia'}<a href="https://www.algolia.com/users/sign_in" target="_blank"> {l s='Log in' mod='algolia'}</a>
		</div>
	</div>

	<hr />

	<div class="algolia-content">
		<div class="row">
			<div class="col-md-6">
				<h5>{l s='Search your database in realtime' mod='algolia'}</h5>
				<dl>
					<dt>&middot; {l s='Search as a service' mod='algolia'}</dt>
					<dd>{l s='Algolia is a fully hosted search service, available as a REST API. API clients are also available for all major frameworks, platforms and languages.' mod='algolia'}</dd>

					<dt>&middot; {l s='High-Performance' mod='algolia'}</dt>
					<dd>{l s='We built our instant search engine from the ground-up with performance in mind, with response times up to 200 times faster than Elasticsearch, and up to 20,000 times faster than SQLite FTS4.' mod='algolia'}</dd>

					<dt>&middot; {l s='Dashboard, all statistics in one location' mod='algolia'}</dt>
					<dd>{l s='One graphical interface for all operations.' mod='algolia'}</dd>

					<dt>&middot; {l s='Database search' mod='algolia'}</dt>
					<dd>{l s='A perfect solution for SQL and NoSQL databases, with a transparent ranking algorithm optimized for semi-structured data.' mod='algolia'}</dd>
				</dl>
				<a class="text-muted small" href="https://www.algolia.com/features" target="_blank">
					<em>And many more features!</em>
				</a>
			</div>

			<div class="col-md-6">
				<div class="row">
					<h5 class="text-center">{l s='Dashboard, all statistics in one location' mod='algolia'}</h5>
					<img src="https://d3ibatyzauff7b.cloudfront.net/assets/flat/capture_dashboard-ee27604719e0162598f67192a5edfbcb.png" class="col-md-6 col-md-offset-3 img-thumbnail" />
				</div>
			</div>
		</div>
	</div>
</div>
