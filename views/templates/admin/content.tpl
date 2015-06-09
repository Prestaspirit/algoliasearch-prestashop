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

<div id="algolia-settings" class="wrap">
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

    {if $algolia_registry->validCredential}
    <h2>
        Algolia Search
        <button data-formurl="index.php?controller=AdminAlgolia&configure=algolia&action=admin_post_reindex&token={$token}" type="button" class="btn btn-primary pull-right" id="algolia_reindex" name="algolia_reindex">
            <i class="icon-refresh"></i>
            Reindex data
        </button>
    </h2>

    <div class="wrapper">
        <div id="results-wrapper" style="display: none;">
            <div class="panel panel-default show-hide">
                <div class="row">
                    <div class="col-lg-6">
                        <h3>Progression</h3>
                        <div id="reindex-percentage"></div>
                    </div>
                    <div class="col-lg-6">
                        <h3>Logs</h3>
                        <table id="reindex-log" class="table"></table>
                    </div>
                </div>
                <button style="display: none;" type="submit" name="submit" id="submit" class="close-results btn btn-default">
                    <i class="icon-times"></i>
                    Close
                </button>
        </div>
    </div>
    {/if}

    <!-- Nav tabs -->
    <ul class="nav nav-tabs" role="tablist">
        {if $algolia_registry->validCredential ne true}
            <li class="active"><a href="#credentials" role="tab" data-toggle="tab">Credentials</a></li>
        {else}
            <li><a href="#credentials" role="tab" data-toggle="tab">Credentials</a></li>
        {/if}

        {if $algolia_registry->validCredential}
            <li class="active"><a href="#ui_template" role="tab" data-toggle="tab">UI</a></li>
            <li><a href="#extra-metas" role="tab" data-toggle="tab">Attributes</a></li>
            <li><a href="#searchable_attributes" role="tab" data-toggle="tab">Searchable Configuration</a></li>
            <li><a href="#custom-ranking" role="tab" data-toggle="tab">Ranking Configuration</a></li>
            <li><a href="#sortable_attributes" role="tab" data-toggle="tab">Sorting Configuration</a></li>
        {/if}
    </ul>

    <!-- Tab panes -->
    <div class="tab-content">
        {if $algolia_registry->validCredential ne true}
        <div class="tab-pane active" id="credentials">
        {else}
        <div class="tab-pane" id="credentials">
        {/if}
            <form id="module_form" class="defaultForm form-horizontal" action="index.php?controller=AdminAlgolia&configure=algolia&action=admin_post_update_account_info&token={$token}" method="post" enctype="multipart/form-data" novalidate="">
                <div class="panel" id="fieldset_0">
                    <div class="form-wrapper">
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                Application ID
                            </label>
                            <div class="col-lg-4">
                                <input type="text" name="APP_ID" id="APP_ID" value="{$algolia_registry->app_id}" class="">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                Search-Only API Key
                            </label>
                            <div class="col-lg-4 ">
                                <input type="text" name="SEARCH_KEY" id="SEARCH_KEY" value="{$algolia_registry->search_key}" class="">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                Admin Key
                            </label>
                            <div class="col-lg-4 ">
                                <input type="text" name="ADMIN_KEY" id="ADMIN_KEY" value="{$algolia_registry->admin_key}" class="">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="algolia_index_name" class="control-label col-lg-3">
                                Index names prefix
                            </label>
                            <div class="col-lg-4 ">
                                <input type="text" value="{$algolia_registry->index_name}" name="INDEX_NAME" id="algolia_index_name" placeholder="prestashop_">
                            </div>
                        </div>
                    </div><!-- /.form-wrapper -->
                    <br>
                    <div class="alert alert-warning">
                        <h4>Reset configuration to default</h4>
                        <span data-form="?controller=AdminAlgolia&configure=algolia&action=admin_post_reset_config_to_default&token={$token}" data-value="admin_post_reset_config_to_default" id="reset-config" class="btn btn-default">
                            <i class="icon-refresh"></i>
                            Reset
                        </span>
                        <span> This will set the config back to default except api keys</span>
                    </div>

                    <div class="panel-footer">
                        <button type="submit" value="1" id="module_form_submit_btn" name="submitAlgoliaSettings" class="btn btn-default pull-right">
                            <i class="process-icon-save"></i> Save changes
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {if $algolia_registry->validCredential}
        <div class="tab-pane active" id="ui_template">
            <form class="defaultForm form-horizontal" action="index.php?controller=AdminAlgolia&configure=algolia&action=admin_post_update_type_of_search&token={$token}" method="post">
                <div class="panel" id="type_of_search">
                    <div class="content">
                        <h3>Search bar</h3>

                        <div class="form-group">
                            <label class="col-lg-3 control-label" for="search-input-selector">DOM selector</label>
                            <div class="col-lg-5">
                                <input type="text" value="{$algolia_registry->search_input_selector}" name="SEARCH_INPUT_SELECTOR" id="search-input-selector">
                                <p class="help-block">The jQuery selector used to select your search bar.</p>
                            </div>
                        </div>

                        <div class="has-extra-content content-item clearfix">
                            <h3>Search experience</h3>
                            <div class="form-group">
                                <label class="col-lg-3 control-label" for="instant_radio_autocomplete">Autocomplete</label>
                                <div class="col-lg-9">
                                    <span class="switch prestashop-switch fixed-width-lg">
                                        <input type="radio" name="instant_radio_autocomplete" id="instant_radio_autocomplete_on" value="autocomplete" checked="checked">
                                        <label for="instant_radio_autocomplete_on">Yes</label>
                                        <input type="radio" name="instant_radio_autocomplete" id="instant_radio_autocomplete_off" value="0">
                                        <label for="instant_radio_autocomplete_off">No</label>
                                        <a class="slide-button btn"></a>
                                    </span>
                                </div>
                            </div>

                            <!-- <div class="form-group">
                                <input type="checkbox"
                                {if in_array('autocomplete', $algolia_registry->type_of_search)}
                                    checked="checked"
                                {/if}
                                class="instant_radio"
                                name="TYPE_OF_SEARCH[]"
                                value="autocomplete"
                                id="instant_radio_autocomplete" />
                                <label for="instant_radio_autocomplete">Autocomplete</label>
                                <p class="help-block">Add an auto-completion menu to your search bar.</p>
                            </div> -->

                            <div class="show-hide" style="display: none;">
                                <div class="form-group">
                                    <label class="col-lg-3 control-label" for="instant_radio_autocomplete_nb_products">Results for product section</label>
                                    <div class="col-lg-9">
                                        <input class="form-control fixed-width-sm" type="number" min="0" value="{$algolia_registry->number_products}" name="NUMBER_PRODUCTS" id="instant_radio_autocomplete_nb_products">
                                        <p class="help-block">The number of results for the product section in the dropdown menu.</p>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-3 control-label" for="instant_radio_autocomplete_nb_categories">Results for categories section</label>
                                    <div class="col-lg-9">
                                        <input class="form-control fixed-width-sm" type="number" min="0" value="{$algolia_registry->number_categories}" name="NUMBER_CATEGORIES" id="instant_radio_autocomplete_nb_categories">
                                        <p class="help-block">The number of results for the categories section in the dropdown menu.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="has-extra-content content-item clearfix">
                            <div class="form-group">
                                <label class="col-lg-3 control-label" for="instant_radio">Instant-search results page</label>
                                <div class="col-lg-9">
                                    <span class="switch prestashop-switch fixed-width-lg">
                                        <input type="radio" name="instant_radio" id="instant_radio_on" value="instant" checked="checked">
                                        <label for="instant_radio_on">Yes</label>
                                        <input type="radio" name="instant_radio" id="instant_radio_off" value="0">
                                        <label for="instant_radio_off">No</label>
                                        <a class="slide-button btn"></a>
                                    </span>
                                </div>
                            </div>
                            <!-- <div class="form-group">
                                <input type="checkbox"
                                {if in_array('instant', $algolia_registry->type_of_search)}
                                    checked="checked"
                                {/if}
                                class="instant_radio"
                                name="TYPE_OF_SEARCH[]"
                                value="instant"
                                id="instant_radio_instant" />
                                <label for="instant_radio_instant">Instant-search results page</label>
                                <p class="description">Refresh the whole results page as you type.</p>
                            </div> -->
                            <div class="show-hide" style="display: none;">
                                <div class="form-group" >
                                    <label class="control-label col-lg-3" for="instant_radio_instant_jquery_selector">DOM selector</label>
                                    <div class="col-lg-5">
                                        <input type="text"
                                            id="instant_radio_instant_jquery_selector"
                                            value="{$algolia_registry->instant_jquery_selector}"
                                            placeholder="#content"
                                            name="JQUERY_SELECTOR"
                                            value="" />
                                        <p class="help-block">The jQuery selector used to inject the search results.</p>
                                    </div>
                                </div>
                                <div class="form-group" >
                                    <label class="control-label col-lg-3" for="instant_radio_instant_nb_results">Number of results by page</label>
                                    <div class="col-lg-9">
                                        <input class="fixed-width-sm" type="number" min="0" value="{$algolia_registry->number_by_page}" name="NUMBER_BY_PAGE" id="instant_radio_instant_nb_results">
                                        <p class="help-block">The number of results to display on a results page.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h3>Theme</h3>
                        <p class="alert alert-info">Configure here the theme of your search results.</p>
                        <div class="content-item">
                            <div class="theme-browser">
                                <div class="themes">
                                    {foreach from=$theme_helper->available_themes() item=theme}
                                    {if $theme->dir eq $algolia_registry->theme}
                                    <div class="theme active">
                                    {else}
                                    <div class="theme">
                                    {/if}
                                        <label for="{$theme->dir}">
                                            <div class="theme-screenshot">
                                                {if $theme->screenshot}
                                                <img src="{$theme->screenshot}">
                                                {else}
                                                <div class="no-screenshot">No screenshot</div>
                                                {/if}
                                                {if $theme->screenshot_autocomplete}
                                                <img class="screenshot autocomplete" src="{$theme->screenshot_autocomplete}">
                                                {else}
                                                <div class="no-screenshot autocomplete instant">No screenshot</div>
                                                {/if}
                                            </div>
                                            <div class="theme-name">
                                                {$theme->name}
                                                <input type="radio"
                                                    id="{$theme->dir}"
                                                {if $theme->dir eq $algolia_registry->theme}
                                                checked="checked"
                                                {/if}
                                                name='THEME'
                                                value="{$theme->dir}"/>
                                            </div>
                                            <div>{$theme->description}</div>
                                        </label>
                                    </div>
                                    {/foreach}
                                </div>
                                <div class="clearfix"></div>
                            </div>
                        </div>
                        <div class="panel-footer">
                            <button type="submit" value="1" id="module_form_submit_btn" name="submitAlgoliaSettings" class="btn btn-default pull-right">
                                <i class="process-icon-save"></i> Save changes
                            </button>
                            <div class="clearfix"></div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="tab-pane" id="extra-metas">
            <form id="extra-metas-form" action="index.php?controller=AdminAlgolia&configure=algolia&action=admin_post_update_extra_meta&token={$token}" method="post">
                <div class="panel" id="customization">
                    <div class="content">
                        <p class="alert alert-info">
                            Configure here the additional attributes you want to include in your Algolia records.
                        </p>

                        <table class="table" id="extra-meta-and-taxonomies">
                            <tr data-order="-1">
                                <th class="table-col-enabled">Enabled</th>
                                <th>Name</th>
                                <th>Retrievable</th>
                                <th>
                                    {if in_array('instant', $algolia_registry->type_of_search)}
                                        Facetable
                                    {/if}
                                </th>
                                <th>{if in_array('instant', $algolia_registry->type_of_search)}
                                        Facet type
                                    {/if}
                                </th>
                                <th>Ordering</th>
                            </tr>
                        </table>

                        <div class="sub-tab-content" id="extra-metas-attributes">
                            <table class="table">
                                <tr data-order="-1">
                                    <th class="table-col-enabled">Enabled</th>
                                    <th>Name</th>
                                    <th>Retrievable</th>
                                    <th>Facetable</th>
                                    <th>Facet type</th>
                                    <th>Facet label &amp; ordering</th>
                                </tr>

                                {assign var='i' value=0}

                                {foreach from=$attributes key=metakey item=attribute}
                                    {assign var='order' value=$attribute->order}

                                    {if $order ne -1}
                                    <tr data-type="extra-meta" data-order="{$order}">
                                    {else}
                                    <tr data-type="extra-meta" data-order="{(1000 + $i)}">
                                    {/if}
                                        {assign var='i' value=$i++}
                                        <td class="table-col-enabled">
                                            {if $attribute->id eq 0}
                                            <i class="dashicons dashicons-yes">-</i>
                                            <input type="hidden" name="ATTRIBUTE[{$metakey}][INDEXABLE]" value="{$metakey}">
                                            {else}
                                            <input type="checkbox"
                                                   name="ATTRIBUTE[{$metakey}][INDEXABLE]"
                                                   value="{$metakey}"
                                                   {if $attribute->checked}
                                                   checked="checked"
                                                   {/if}
                                            >
                                            {/if}
                                        </td>
                                        <td>{$attribute->name}</td>
                                        <td>
                                            <input type="checkbox"
                                                   name="ATTRIBUTE[{$metakey}][RETRIEVABLE]"
                                                   value="1"
                                                    {if $attribute->retrievable}
                                                        checked="checked"
                                                    {/if}
                                                    >
                                        </td>
                                        <td>
                                            {if in_array('instant', $algolia_registry->type_of_search)}
                                            <input type="checkbox"
                                                   name="ATTRIBUTE[{$metakey}][FACETABLE]"
                                                   value="1"
                                                   {if $attribute->facetable}
                                                   checked="checked"
                                                   {/if}
                                            >
                                            {/if}
                                        </td>
                                        <td>
                                            {if in_array('instant', $algolia_registry->type_of_search)}
                                            <select name="ATTRIBUTE[{$metakey}][TYPE]">
                                                {foreach from=$facet_types key=key item=value}
                                                    {if $attribute->facet_type eq $key}
                                                        <option selected="selected" value="{$key}">{$value}</option>
                                                    {else}
                                                        <option value="{$key}">{$value}</option>
                                                    {/if}
                                                {/foreach}
                                            </select>
                                            {/if}
                                        </td>
                                        <td>
                                            <img width="10" src="{$path}img/move.png">
                                        </td>

                                        <!-- PREVENT FROM ERASING CUSTOM RANKING -->
                                        {foreach from=$customs key=custom_key item=custom_value}
                                            {if isset($algolia_registry->metas[$metakey])}
                                            <input type="hidden"
                                                   name="ATTRIBUTE[{$metakey}][{$custom_value}]"
                                                   value="{$algolia_registry->metas[$metakey][$custom_key]}">
                                            {/if}
                                        {/foreach}
                                        <!-- /////// PREVENT FROM ERASING CUSTOM RANKING -->

                                        <input type="hidden" name="ATTRIBUTE[{$metakey}][ORDER]" class="order" />
                                    </tr>
                                {/foreach}
                            </table>
                        </div>
                        <div class="panel-footer">
                            <button type="submit" value="1" id="module_form_submit_btn" name="submitAlgoliaSettings" class="btn btn-default pull-right">
                                <i class="process-icon-save"></i> Save changes
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="tab-pane" id="searchable_attributes">
            <form action="index.php?controller=AdminAlgolia&configure=algolia&action=admin_post_update_searchable_attributes&token={$token}" method="post">
                <div class="panel" id="customization">
                    <div class="content">
                        <p class="alert alert-info">
                            Configure here the attributes you want to be able to search in. The order of this setting matters as those at the top of the list are considered more important.
                        </p>
                        <table class="table">
                            <tr data-order="-1">
                                <th class="table-col-enabled">Enabled</th>
                                <th>Name</th>
                                <th>Attribute ordering</th>
                                <th></th>
                            </tr>

                            {assign var=i value=0}

                            {foreach from=$searchable_attributes key=key item=searchItem}
                                {assign var=order value=-1}

                                {if isset($algolia_registry->searchable[$key])}
                                    {assign var=order value=$algolia_registry->searchable[$key]['order']}
                                {/if}

                                {if ($order != -1)}
                                <tr data-order="{$order}">
                                {else}
                                <tr data-order="{(10000 + $i)}">
                                    {assign var=i value=($i + 1)}
                                {/if}
                                    <td class="table-col-enabled">
                                        {if (isset($algolia_registry->searchable[$key]))}
                                            <input checked="checked" type="checkbox" name="ATTRIBUTES[{$key}][SEARCHABLE]">
                                        {else}
                                            <input type="checkbox" name="ATTRIBUTES[{$key}][SEARCHABLE]">
                                        {/if}
                                    </td>
                                    <td>
                                        {$searchItem}
                                    </td>
                                    <td style="white-space: nowrap;">
                                        <select name="ATTRIBUTES[{$key}][ORDERED]">
                                            {foreach from=$ordered_tab key=key2 item=value2}
                                                {if isset($algolia_registry->searchable[$key]) && $algolia_registry->searchable[$key]['ordered'] == $key2}
                                                <option selected value="{$key2}">{$value2}</option>
                                                {else}
                                                <option value="{$key2}">{$value2}</option>
                                                {/if}
                                            {/foreach}
                                        </select>
                                    </td>
                                    <td>
                                        <img width="10" src="{$path}img/move.png">
                                    </td>
                                </tr>
                            {/foreach}
                        </table>
                        <div class="panel-footer">
                            <button type="submit" value="1" id="module_form_submit_btn" name="submitAlgoliaSettings" class="btn btn-default pull-right">
                                <i class="process-icon-save"></i> Save changes
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="tab-pane" id="custom-ranking">
            <form action="index.php?controller=AdminAlgolia&configure=algolia&action=admin_post_custom_ranking&token={$token}" method="post">
                <div class="panel" id="customization">
                    <div class="content">
                        <p class="alert alert-info">
                            Configure here the attributes used to reflect the popularity of your records (number of likes, number of views, number of sales...).
                        </p>
                        <table class="table">
                            <tr data-order="-1">
                                <th class="table-col-enabled">Enabled</th>
                                <th>Meta key</th>
                                <th>Sort order</th>
                                <th></th>
                            </tr>

                            {assign var=i value=0}
                            {assign var=n value=0}

                            {foreach from=$searchable_attributes key=key item=item}
                                {assign var=order value=(-1)}
                                {assign var=n value=($n+1)}

                                {if isset($algolia_registry->metas[$key]['custom_ranking'])}
                                    {assign var=order value=$algolia_registry->metas[$key]['custom_ranking_sort']}
                                {/if}

                                {if $order ne -1}
                                <tr data-order="{$order}">
                                {else}
                                <tr data-order="{(10000 + $i)}">
                                    {assign var=i value=($i + 1)}
                                {/if}
                                    <td>
                                        {if isset($algolia_registry->metas[$key]) && $algolia_registry->metas[$key]['custom_ranking']}
                                            <input checked="checked" type="checkbox" name="ATTRIBUTES[{$key}][CUSTOM_RANKING]"/>
                                        {else}
                                            <input type="checkbox" name="ATTRIBUTES[{$key}][CUSTOM_RANKING]"/>
                                        {/if}
                                    </td>
                                    <td>{$item}</td>
                                    <td style="white-space: nowrap;">
                                        <select name="ATTRIBUTES[{$key}][CUSTOM_RANKING_ORDER]">
                                            {foreach from=$ascending_tab key=key2 item=value2}
                                                {if isset($algolia_registry->metas[$key]) && $algolia_registry->metas[$key]['custom_ranking_order'] eq $key2}
                                                    <option selected value="{$key2}">{$value2}</option>
                                                {else}
                                                    <option value="{$key2}">{$value2}</option>
                                                {/if}
                                            {/foreach}
                                        </select>
                                    </td>
                                    <td>
                                        <img width="10" src="{$path}img/move.png">
                                    </td>
                                </tr>
                            {/foreach}
                        </table>
                        <div class="panel-footer">
                            <button type="submit" value="1" id="module_form_submit_btn" name="submitAlgoliaSettings" class="btn btn-default pull-right">
                                <i class="process-icon-save"></i> Save changes
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="tab-pane" id="sortable_attributes">
            <form id="sortable-form" action="index.php?controller=AdminAlgolia&configure=algolia&action=admin_post_update_sortable_attributes&token={$token}" method="post">
                <div class="panel" id="customization">
                    <div class="content">
                        <p class="alert alert-info">
                            By default results are sorted by text relevance &amp; your ranking criteria. Configure here the attributes you want to use for the additional sorts (by price, by date, etc...).
                        </p>
                        <table class="table">
                            <tr data-order="-1">
                                <th class="table-col-enabled">Enabled</th>
                                <th>Name</th>
                                <th>Sort</th>
                                <th></th>
                            </tr>

                            {assign var=i value=0}
                            {foreach from=$searchable_attributes key=key item=sortItem}
                                {foreach from=$sorts item=sort}
                                    {assign var=order value=(-1)}
                                    {if isset($algolia_registry->sortable[{"`$key`_`$sort`"}])}
                                        {assign var=order value=$algolia_registry->sortable[{"`$key`_`$sort`"}]['order']}
                                    {/if}
                                    {if $order ne -1}
                                    <tr data-order="{$order}">
                                    {else}
                                    <tr data-order="{(10000 + $i)}">
                                        {assign var=i value=($i + 1)}
                                    {/if}
                                    <td class="table-col-enabled">
                                        {if isset($algolia_registry->sortable[{"`$key`_`$sort`"}])}
                                            <input checked="checked" type="checkbox" name="ATTRIBUTES[{$key}][{$sort}]">
                                        {else}
                                            <input type="checkbox" name="ATTRIBUTES[{$key}][{$sort}]">
                                        {/if}
                                    </td>
                                    <td>
                                        {$sortItem}
                                    </td>
                                    <td>
                                        {if $sort eq 'asc'}
                                        <span class="dashicons dashicons-arrow-up-alt"></span>
                                        {else}
                                        <span class="dashicons dashicons-arrow-down-alt"></span>
                                        {/if}

                                        {if $sort eq 'asc'}
                                            Ascending
                                        {else}
                                            Descending
                                        {/if}
                                    </td>
                                    <td>
                                        <img width="10" src="{$path}img/move.png">
                                    </td>
                                    <input type="hidden" name="ATTRIBUTES[{$key}][ORDER_{$sort}]" class="order" />
                                </tr>
                                {/foreach}
                            {/foreach}
                        </table>
                        <div class="panel-footer">
                            <button type="submit" value="1" id="module_form_submit_btn" name="submitAlgoliaSettings" class="btn btn-default pull-right">
                                <i class="process-icon-save"></i> Save changes
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        {/if}
    </div>
</div>