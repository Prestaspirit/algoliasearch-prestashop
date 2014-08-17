<?php
/**
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
*/

if (!defined('_PS_VERSION_'))
	exit;

class Algolia extends Module
{
	protected $config_form = false;

	public function __construct()
	{
		$this->version = '1.0';
		$this->name = 'algolia';
		$this->author = 'PrestaShop';
		$this->tab = 'front_office_features';

		$this->bootstrap = true;

		parent::__construct();

		$this->displayName = $this->l('Algolia');
		$this->description = $this->l('My Algolia module.');

		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
	}

	public function install()
	{
		return parent::install() &&
			$this->registerHook('displayTop') &&
			$this->registerHook('displayHeader') &&
			$this->registerHook('displayBackOfficeHeader') &&
			$this->registerHook('actionCronJob');
	}

	public function hookDisplayBackOfficeHeader()
	{
		if (strcmp(Tools::getValue('configure'), $this->name) === 0)
			$this->context->controller->addCSS($this->_path.'css/configure.css');
	}

	public function hookDisplayHeader()
	{
		require_once(dirname(__FILE__).'/classes/AlgoliaSearch.php');

		$algolia_search = new AlgoliaSearch();

		Media::addJsDef(array(
			'algolia_application_id' => $algolia_search->getApplicationID(),
			'algolia_search_only_api_key' => $algolia_search->getSearchOnlyAPIKey(),
			'algolia_index_name' => $algolia_search->getIndexName(),
		));

		$this->context->controller->addJS('//twitter.github.com/hogan.js/builds/3.0.1/hogan-3.0.1.js');
		$this->context->controller->addJS($this->_path.'/js/typeahead.bundle.js');
		
		$this->context->controller->addJS('//rawgithub.com/algolia/algoliasearch-client-js/master/dist/algoliasearch.min.js');
		$this->context->controller->addJS($this->_path.'/js/algolia.js');
		$this->context->controller->addCSS($this->_path.'/css/algolia.css');
	}

	public function hookDisplayTop()
	{
		return $this->display(__FILE__, 'views/templates/hook/search.tpl');
	}

	public function hookActionCronJob()
	{
		return true;
	}

	public function getContent()
	{
		if (((bool)Tools::isSubmit('submitAlgolia')) == true)
			$this->_postProcess();

		require_once(dirname(__FILE__).'/classes/AlgoliaSync.php');
		$algolia_sync = new AlgoliaSync();
		$algolia_sync->syncProducts();

		$this->context->smarty->assign('module_dir', $this->_path);
		$this->context->smarty->assign('settings_form', $this->renderForm());
		return $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
	}

	protected function renderForm()
	{
		$helper = new HelperForm();

		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$helper->module = $this;
		$helper->default_form_language = $this->context->language->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitAlgolia';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
			.'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');

		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFormValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id,
		);

		return $helper->generateForm(array($this->getConfigForm()));
	}

	protected function getConfigForm()
	{
		return array(
			'form' => array(
				'input' => array(
					array(
						'col' => 4,
						'type' => 'text',
						'name' => 'ALGOLIA_APPLICATION_ID',
						'label' => $this->l('Application ID'),
					),
					array(
						'col' => 6,
						'type' => 'text',
						'name' => 'ALGOLIA_API_KEY',
						'label' => $this->l('API Key'),
					),
					array(
						'col' => 6,
						'type' => 'text',
						'name' => 'ALGOLIA_SEARCH_ONLY_API_KEY',
						'label' => $this->l('Search-Only API Key'),
					),
				),
				'submit' => array(
					'title' => $this->l('Save'),
				),
			),
		);
	}

	protected function getConfigFormValues()
	{
		return array(
			'ALGOLIA_APPLICATION_ID' => Configuration::get('ALGOLIA_APPLICATION_ID', null),
			'ALGOLIA_API_KEY' => Configuration::get('ALGOLIA_API_KEY', null),
			'ALGOLIA_SEARCH_ONLY_API_KEY' => Configuration::get('ALGOLIA_SEARCH_ONLY_API_KEY', null),
		);
	}

	protected function _postProcess()
	{
		$form_values = $this->getConfigFormValues();

		foreach (array_keys($form_values) as $key)
			Configuration::updateValue($key, Tools::getValue($key));
	}

	public function getCronFrequency()
	{
		return array(
			'hour' => -1,
			'day' => -1,
			'month' => -1,
			'day_of_week' => -1
		);
	}
}
