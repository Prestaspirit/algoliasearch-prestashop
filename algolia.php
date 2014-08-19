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
	const Facet_Search = true;
	const Simple_Search = false;

	protected $config_form = false;
	protected $_warnings = false;
	protected $algolia = false;

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

		$this->init();
	}

	public function install()
	{
		Configuration::updateValue('ALGOLIA_SEARCH_TYPE', Algolia::Facet_Search);

		return parent::install() &&
			$this->registerHook('displayTop') &&
			$this->registerHook('displayHeader') &&
			$this->registerHook('displayBackOfficeHeader') &&
			$this->registerHook('actionCronJob');
	}

	public function uninstall()
	{
		Configuration::deleteByName('ALGOLIA_POSITION_FIXED');
		Module::enableByName('blocksearch');

		return parent::uninstall();
	}

	public function hookDisplayBackOfficeHeader()
	{
		if (strcmp(Tools::getValue('configure'), $this->name) === 0)
			$this->context->controller->addCSS($this->_path.'css/configure.css');
	}

	public function hookDisplayHeader()
	{
		if ($this->algolia->isConfigurationValid() === false)
			return false;

		require_once(dirname(__FILE__).'/classes/AlgoliaSearch.php');

		$algolia_search = new AlgoliaSearch();
		$search_url = Context::getContext()->link->getModuleLink('algolia', 'search');

		Media::addJsDef(array(
			'algolia_application_id' => $algolia_search->getApplicationID(),
			'algolia_search_only_api_key' => $algolia_search->getSearchOnlyAPIKey(),
			'algolia_index_name' => $algolia_search->getIndexName(),
			'algolia_search_url' => $search_url,
		));
		
		$this->context->smarty->assign('algolia_search_url', $search_url);

		$this->context->controller->addJS($this->_path.'/js/typeahead.bundle.js');
		$this->context->controller->addJS($this->_path.'/js/hogan-3.0.1.js');
		$this->context->controller->addJS($this->_path.'/js/algoliasearch.min.js');
		$this->context->controller->addCSS($this->_path.'/css/algolia.css');

		if (Configuration::get('ALGOLIA_SEARCH_TYPE') == Algolia::Facet_Search)
			$this->context->controller->addJS($this->_path.'/js/algolia_facet_search.js');
		elseif (Configuration::get('ALGOLIA_SEARCH_TYPE') == Algolia::Simple_Search)
			$this->context->controller->addJS($this->_path.'/js/algolia_simple_search.js');
	}

	public function hookDisplayTop()
	{
		if ($this->algolia->isConfigurationValid() === false)
			return false;

		return $this->display(__FILE__, 'views/templates/hook/search.tpl');
	}

	public function hookActionCronJob()
	{
		return true;
	}

	protected function init()
	{
		if (Module::isEnabled($this->name) === false)
			return false;

		if (function_exists('curl_init') == false)
			$this->warning = $this->l('To be able to use this module, please activate cURL (PHP extension).');

		$this->_warnings = array();

		require_once(dirname(__FILE__).'/classes/AlgoliaLibrary.php');
		$this->algolia = new AlgoliaLibrary();

		if ($this->algolia->isConfigurationValid() === false)
			array_push($this->_warnings, $this->l('Invalid settings, please check your Algolia API keys.'));
		elseif (Configuration::get('ALGOLIA_POSITION_FIXED', false) == false)
			$this->setPosition();
	}

	protected function setPosition()
	{
		$position = 0;
		$blocksearch = Module::getInstanceByName('blocksearch');

		if ($blocksearch !== false)
		{
			$hook_top = Hook::getIdByName('displayTop');
			$position = $blocksearch->getPosition($hook_top);

			if (is_null($position) == false)
			{
				Module::disableByName('blocksearch');
				$this->updatePosition($hook_top, 0, $position);
			}
		}

		Configuration::updateValue('ALGOLIA_POSITION_FIXED', $position);
	}

	public function getContent()
	{
		if (((bool)Tools::isSubmit('submitAlgoliaSettings')) == true)
			$this->_postProcess();
		elseif (((bool)Tools::isSubmit('submitAlgoliaSync')) == true)
			$this->syncProducts();

		$this->init();

		$this->context->smarty->assign('module_dir', $this->_path);

		$settings_form = $this->getSettingsForm();
		$settings_form_values = $this->getSettingsFormValues();
		$this->context->smarty->assign('settings_form', $this->renderForm('settings', $settings_form, $settings_form_values));

		$sync_form = $this->getSyncForm();
		$sync_form_values = $this->getSyncFormValues();
		$this->context->smarty->assign('sync_form', $this->renderForm('sync', $sync_form, $sync_form_values));

		$this->context->smarty->assign('warnings', $this->_warnings);

		return $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
	}

	protected function syncProducts()
	{
		try
		{
			require_once(dirname(__FILE__).'/classes/AlgoliaSync.php');
			$algolia_sync = new AlgoliaSync();
			$algolia_sync->syncProducts();
		}
		catch (Exception $exception)
		{
			array_push($this->_warnings, $exception->getMessage());
		}
	}

	protected function renderForm($name, $form, $values)
	{
		$helper = new HelperForm();

		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$helper->module = $this;
		$helper->default_form_language = $this->context->language->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitAlgolia'.ucfirst($name);
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
			.'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');

		$helper->tpl_vars = array(
			'fields_value' => $values,
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id,
		);

		return $helper->generateForm(array($form));
	}

	protected function getSettingsForm()
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
					array(
						'type' => 'switch',
						'name' => 'ALGOLIA_SEARCH_TYPE',
						'label' => $this->l('Enable faceting'),
						'is_bool' => true,
						'values' => array(
							array(
								'id' => 'faceting_on',
								'value' => (int)Algolia::Facet_Search,
								'label' => $this->l('Enabled')
							),
							array(
								'id' => 'faceting_off',
								'value' => (int)Algolia::Simple_Search,
								'label' => $this->l('Disabled')
							)
						),
					),
				),
				'submit' => array(
					'title' => $this->l('Save'),
				),
			),
		);
	}

	protected function getSyncForm()
	{
		return array(
			'form' => array(
				'input' => array(
					array(
						'type' => 'free',
						'name' => 'ALGOLIA_SYNC_DESC',
					),
				),
				'submit' => array(
					'icon' => 'process-icon-refresh',
					'title' => $this->l('Sync'),
				),
			),
		);
	}

	protected function getSettingsFormValues()
	{
		return array(
			'ALGOLIA_APPLICATION_ID' => Configuration::get('ALGOLIA_APPLICATION_ID', null),
			'ALGOLIA_API_KEY' => Configuration::get('ALGOLIA_API_KEY', null),
			'ALGOLIA_SEARCH_ONLY_API_KEY' => Configuration::get('ALGOLIA_SEARCH_ONLY_API_KEY', null),
			'ALGOLIA_SEARCH_TYPE' => (int)Configuration::get('ALGOLIA_SEARCH_TYPE'),
		);
	}

	protected function getSyncFormValues()
	{
		return array(
			'ALGOLIA_SYNC_DESC' => '<p>Click on the "Sync" button to update your indexes on Algolia</p>'
		);
	}

	protected function _postProcess()
	{
		$form_values = $this->getSettingsFormValues();

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
