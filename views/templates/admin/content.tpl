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
        <button data-formurl="index.php?controller=AdminAlgolia&configure=algolia&action=admin_post_reindex&token={$token}" type="button" class="button button-primary " id="algolia_reindex" name="algolia_reindex">
            <i class="dashicons dashicons-upload"></i>
            Reindex data
        </button>
    </h2>

    <div class="wrapper">
        <div style="clear: both;"</div>

        <div id="results-wrapper" style="display: none;">
            <div class="content">
                <div class="show-hide">

                    <div class="content-item">
                        <div>Progression</div>
                        <div style='padding: 5px;'>
                            <div id="reindex-percentage">
                            </div>
                            <div style='clear: both'></div>
                        </div>
                    </div>

                    <div class="content-item">
                        <div>Logs</div>
                        <div style='padding: 5px;'>
                            <table id="reindex-log"></table>
                        </div>
                    </div>

                    <div class="content-item">
                        <button style="display: none;" type="submit" name="submit" id="submit" class="close-results button button-primary">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {/if}

    <!-- Nav tabs -->
    <ul class="nav nav-tabs" role="tablist">
        {if $algolia_registry->validCredential ne true}
            <li class="active"><a href="#settings_template" role="tab" data-toggle="tab">Credentials</a></li>
        {else}
            <li><a href="#settings_template" role="tab" data-toggle="tab">Credentials</a></li>
        {/if}

        {if $algolia_registry->validCredential}
            <li class="active"><a href="#ui_template" role="tab" data-toggle="tab">UI</a></li>
            <li><a href="#sync_template" role="tab" data-toggle="tab">Sync</a></li>
        {/if}
    </ul>

    <!-- Tab panes -->
    <div class="tab-content">
        {if $algolia_registry->validCredential ne true}
        <div class="tab-pane active" id="settings_template">
        {else}
        <div class="tab-pane" id="settings_template">
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
                                API Key
                            </label>
                            <div class="col-lg-6 ">
                                <input type="text" name="SEARCH_KEY" id="SEARCH_KEY" value="{$algolia_registry->search_key}" class="">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">
                                Search-Only API Key
                            </label>
                            <div class="col-lg-6 ">
                                <input type="text" name="ADMIN_KEY" id="ADMIN_KEY" value="{$algolia_registry->admin_key}" class="">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="algolia_index_name" class="control-label col-lg-3">
                                Index names prefix
                            </label>
                            <div class="col-lg-6 ">
                                <input type="text" value="{$algolia_registry->index_name}" name="INDEX_NAME" id="algolia_index_name" placeholder="prestashop_">
                            </div>
                        </div>
                    </div><!-- /.form-wrapper -->
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
            <form action="index.php?controller=AdminAlgolia&configure=algolia&action=admin_post_update_type_of_search&token={$token}" method="post">
                <div class="content-wrapper" id="type_of_search">
                    <div class="content">
                        <h3>Search bar</h3>
                        <p class="help-block">Configure here your search input field.</p>
                        <div class="content-item">
                            <label for="search-input-selector">DOM selector</label>
                            <div>
                                <input type="text" value="{$algolia_registry->search_input_selector}" name="SEARCH_INPUT_SELECTOR" id="search-input-selector">
                                <p class="description">The jQuery selector used to select your search bar.</p>
                            </div>
                        </div>
                        <div class="has-extra-content content-item">
                            <label>Search experience</label>
                            <div>
                                <input type="radio"
                                {if $algolia_registry->type_of_search eq 'autocomplete'}
                                    checked="checked"
                                {/if}
                                class="instant_radio"
                                name="TYPE_OF_SEARCH"
                                value="autocomplete"
                                id="instant_radio_autocomplete" />
                                <label for="instant_radio_autocomplete">Autocomplete</label>
                                <p class="description">Add an auto-completion menu to your search bar.</p>
                            </div>
                            <div class="show-hide" style="display: none;">
                                <div>
                                    <label for="instant_radio_autocomplete_nb_results">Results by section</label>
                                    <input type="number" min="0" value="{$algolia_registry->number_by_type}" name="NUMBER_BY_TYPE" id="instant_radio_autocomplete_nb_results">
                                    <p class="description">The number of results per section in the dropdown menu.</p>
                                </div>
                            </div>
                        </div>
                        <div class="has-extra-content content-item">
                            <div>
                                <input type="radio"
                                {if $algolia_registry->type_of_search eq 'instant'}
                                    checked="checked"
                                {/if}
                                class="instant_radio"
                                name="TYPE_OF_SEARCH"
                                value="instant"
                                id="instant_radio_instant" />
                                <label for="instant_radio_instant">Instant-search results page</label>
                                <p class="description">Refresh the whole results page as you type.</p>
                            </div>
                            <div class="show-hide" style="display: none;">
                                <div>
                                    <label for="instant_radio_instant_jquery_selector">DOM selector</label>
                                    <input type="text"
                                           id="instant_radio_instant_jquery_selector"
                                           value="{$algolia_registry->instant_jquery_selector}"
                                    placeholder="#content"
                                    name="JQUERY_SELECTOR"
                                    value="" />
                                    <p class="description">The jQuery selector used to inject the search results.</p>
                                </div>
                                <div>
                                    <label for="instant_radio_instant_nb_results">Number of results by page</label>
                                    <input type="number" min="0" value="{$algolia_registry->number_by_page}" name="NUMBER_BY_PAGE" id="instant_radio_instant_nb_results">
                                    <p class="description">The number of results to display on a results page.</p>
                                </div>
                                <div>
                                    <label for="instant_radio_content_nb_snippet">Number of words on the content snippet</label>
                                    <input type="number" min="0" value="{$algolia_registry->number_of_word_for_content}" name="NUMBER_OF_WORD_FOR_CONTENT" id="instant_radio_content_nb_snippet">
                                    <p class="description">The number of results to display on a results page.</p>
                                </div>
                            </div>
                        </div>
                        <h3>Theme</h3>
                        <p class="help-block">Configure here the theme of your search results.</p>
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
                            </div>
                            <div style="clear: both"></div>
                        </div>
                        <div class="panel-footer">
                            <button type="submit" value="1" id="module_form_submit_btn" name="submitAlgoliaSettings" class="btn btn-default pull-right">
                                <i class="process-icon-save"></i> Save changes
                            </button>
                            <div style="clear: both"></div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="tab-pane" id="sync_template">
            <form id="module_form" class="defaultForm form-horizontal" action="index.php?controller=AdminAlgolia&configure=algolia&action=admin_post_reindex&token={$token}" method="post" enctype="multipart/form-data" novalidate="">
                <button type="submit" value="1" id="module_form_submit_btn" name="submitAlgoliaSettings" class="btn btn-default pull-right">
                    <i class="process-icon-save"></i> Save changes
                </button>
            </form>
        </div>
        {/if}
    </div>
</div>