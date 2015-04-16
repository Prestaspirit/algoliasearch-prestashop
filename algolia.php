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

require_once(dirname(__FILE__).'/controllers/front/FrontAlgolia.php');
require_once(dirname(__FILE__).'/classes/AlgoliaHelper.php');
require_once(dirname(__FILE__).'/classes/AttributesHelper.php');
require_once(dirname(__FILE__).'/classes/Registry.php');
require_once(dirname(__FILE__).'/classes/ThemeHelper.php');
require_once(dirname(__FILE__).'/classes/Indexer.php');
require_once(dirname(__FILE__).'/classes/PrestashopFetcher.php');
require_once(dirname(__FILE__).'/libraries/algolia/algoliasearch.php');


class Algolia extends Module
{
    private $front_controller;
    public $batch_count = 100;

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

        $this->front_controller = new FrontAlgoliaController($this);
	}

    public function getPath()
    {
        return $this->_path;
    }

    public function getContext()
    {
        return $this->context;
    }

	public function install()
	{
		return parent::install() &&
			$this->registerHook('displayTop') &&
			$this->registerHook('displayHeader') &&
            $this->registerHook('displayFooter') &&
			$this->registerHook('displayBackOfficeHeader') &&
			$this->registerHook('actionCronJob') &&
            $this->registerHook('actionProductUpdate') &&
            $this->registerHook('actionProductDelete') &&
            $this->registerHook('actionProductAdd') &&
            $this->addAdminTab();
	}

	public function uninstall()
	{
		Configuration::deleteByName('ALGOLIA_POSITION_FIXED');
		Module::enableByName('blocksearch');

        $this->removeAdminTab();

		return parent::uninstall();
	}

    public function addAdminTab()
    {
        $tab = new Tab();

        foreach(Language::getLanguages(false) as $lang)
            $tab->name[(int) $lang['id_lang']] = 'Algolia Search';

        $tab->class_name = 'AdminAlgolia';
        $tab->module = $this->name;
        $tab->id_parent = 0;

        if (!$tab->save())
            return false;

        return true;
    }

    public function removeAdminTab()
    {
        $classNames = array('AdminAlgolia');
        $return = true;

        foreach ($classNames as $className)
        {
            $tab = new Tab(Tab::getIdFromClassName($className));
            $return &= $tab->delete();
        }

        return $return;
    }

    /**
     * HOOKS
     */

    public function hookActionProductAdd($params)
    {
        $this->front_controller->hookActionProductAdd($params);
    }

    public function hookActionProductUpdate($params)
    {
        $this->front_controller->hookActionProductUpdate($params);
    }

    public function hookActionProductDelete($params)
    {
        $this->front_controller->hookActionProductDelete($params);
    }

    public function hookDisplayBackOfficeHeader()
    {
        $this->front_controller->hookDisplayBackOfficeHeader();
    }

    public function hookDisplayFooter()
    {
        $this->front_controller->hookDisplayFooter();
    }

	public function hookDisplayHeader()
	{
        $this->front_controller->hookDisplayHeader();
	}

	protected function init()
	{
		/* If the module is not active */
		if (Module::isEnabled($this->name) === false)
			return false;

		/* Add a default warning message if cURL extension is not available */
		if (function_exists('curl_init') == false)
			$this->warning = $this->l('To be able to use this module, please activate cURL (PHP extension).');
	}

	/* Run cron tasks */
	public function hookActionCronJob()
	{
	}

	/* Return cron job execution frequency */
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
