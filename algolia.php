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

require_once(dirname(__FILE__).'/classes/AlgoliaHelper.php');
require_once(dirname(__FILE__).'/classes/Registry.php');
require_once(dirname(__FILE__).'/classes/ThemeHelper.php');
require_once(dirname(__FILE__).'/classes/Indexer.php');
require_once(dirname(__FILE__).'/classes/PrestashopFetcher.php');
require_once(dirname(__FILE__).'/libraries/algoliasearch-client-php/algoliasearch.php');


class Algolia extends Module
{
    public $batch_count = 50;
    private $algolia_helper;
    private $algolia_registry;
    private $theme_helper;
    private $indexer;

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

        $this->algolia_registry = \Algolia\Core\Registry::getInstance();
        $this->theme_helper = new \Algolia\Core\ThemeHelper($this);
        $this->indexer = new \Algolia\Core\Indexer();

        if ($this->algolia_registry->validCredential)
        {
            $this->algolia_helper   = new \Algolia\Core\AlgoliaHelper(
                $this->algolia_registry->app_id,
                $this->algolia_registry->search_key,
                $this->algolia_registry->admin_key
            );
        }

/*        echo '<pre>';
        print_r(array_merge($this->getFeatures(), $this->getAttributes(), $this->getFilters()));
        die();
*/

	}

    public function getFeatures()
    {
        global $cookie;
        return array_map(function ($a) {
            return $a['name'];
        }, \Feature::getFeatures($cookie->id_lang));
    }

    public function getAttributes()
    {
        global $cookie;

        return array_map(function ($a) {
            return $a['attribute_group'];
        }, Db::getInstance()->executeS('
			SELECT DISTINCT agl.`name` AS `attribute_group`
			FROM `'._DB_PREFIX_.'attribute_group` ag
			LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl
				ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int)$cookie->id_lang.')
			LEFT JOIN `'._DB_PREFIX_.'attribute` a
				ON a.`id_attribute_group` = ag.`id_attribute_group`
			LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al
				ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int)$cookie->id_lang.')
			'.Shop::addSqlAssociation('attribute_group', 'ag').'
			'.Shop::addSqlAssociation('attribute', 'a').'
			'.(false ? 'WHERE a.`id_attribute` IS NOT NULL AND al.`name` IS NOT NULL AND agl.`id_attribute_group` IS NOT NULL' : '').'
			ORDER BY agl.`name` ASC, a.`position` ASC
		'));
    }

    public function getFilters()
    {
        static $cache = null;

        $id_shop = (int) Context::getContext()->shop->id;

        if (is_array($cache))
            return $cache;

        $home_category = Configuration::get('PS_HOME_CATEGORY');
        $id_parent = (int)Tools::getValue('id_category', Tools::getValue('id_category_layered', $home_category));

        $filters = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT * FROM '._DB_PREFIX_.'layered_category
			WHERE id_category = '.(int)$id_parent.'
				AND id_shop = '.$id_shop.'
			GROUP BY `type`, id_value ORDER BY position ASC'
        );

        return array_map(function ($a) {
            return $a["type"];
        },$filters);
    }

    public function getPath()
    {
        return $this->_path;
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
            $tab->name[(int) $lang['id_lang']] = 'Algolia';

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
        $this->indexer->indexProduct($params['product']);
    }

    public function hookActionProductUpdate($params)
    {
        $this->indexer->indexProduct($params['product']);
    }

    public function hookActionProductDelete($params)
    {
        $this->indexer->deleteProduct($params['product']->id);
    }

    public function hookDisplayBackOfficeHeader()
    {
        if (strcmp(Tools::getValue('configure'), $this->name) === 0)
            $this->context->controller->addCSS($this->_path.'css/configure.css');
    }

    public function hookDisplayFooter()
    {
        $path = $this->_path;

        include __DIR__.'/themes/'.$this->algolia_registry->theme.'/templates.php';
    }

	public function hookDisplayHeader()
	{
        if ($this->algolia_registry->validCredential == false)
			return false;

        $search_url = Context::getContext()->link->getModuleLink('algolia', 'search');
        $this->context->smarty->assign('algolia_search_url', $search_url);

		/* Add CSS & JS files required for Algolia search */
		$this->context->controller->addJS($this->_path.'/js/typeahead.bundle.js');
		$this->context->controller->addJS($this->_path.'/js/hogan-3.0.1.js');
		$this->context->controller->addJS($this->_path.'/js/algoliasearch.min.js');
        $this->context->controller->addCSS($this->_path.'/css/algolia.css');

        $this->context->controller->addJS($this->_path.'/js/main.js');
        $this->context->controller->addCSS($this->_path.'/themes/'.$this->algolia_registry->theme.'/styles.css');
        $this->context->controller->addJS($this->_path.'/themes/'.$this->algolia_registry->theme.'/theme.js');

        global $cookie;

        $current_language = \Language::getIsoById($cookie->id_lang);

        $indices = array();
        $indices[] = array('index_name' => $this->algolia_registry->index_name.'all_'.$current_language, 'name' => 'Products', 'order1' => 0, 'order2' => 0);

        $facets = array();

        foreach (\Feature::getFeatures($cookie->id_lang) as $feature)
            $facets[] = array('tax' => $feature['name'], 'name' => $feature['name'], 'order1' => 0,'order2' => 0, 'type' => 'conjunctive');

        $algoliaSettings = array(
            'app_id'                    => $this->algolia_registry->app_id,
            'search_key'                => $this->algolia_registry->search_key,
            'indices'                   => $indices,
            'sorting_indices'           => array(),//$sorting_indices,
            'index_name'                => $this->algolia_registry->index_name,
            'type_of_search'            => $this->algolia_registry->type_of_search,
            'instant_jquery_selector'   => str_replace("\\", "", $this->algolia_registry->instant_jquery_selector),
            'facets'                    => $facets,
            'facetsLabels'              => array(),//$facetsLabels,
            'number_by_type'            => $this->algolia_registry->number_by_type,
            'number_by_page'            => $this->algolia_registry->number_by_page,
            'search_input_selector'     => str_replace("\\", "", $this->algolia_registry->search_input_selector),
            "plugin_url"                => $this->_path,
            "language"                  => $current_language,
            'theme'                     => $this->theme_helper->get_current_theme()
        );

        Media::addJsDef(array('algoliaSettings' => $algoliaSettings));

		/*if (Configuration::get('ALGOLIA_SEARCH_TYPE') == Algolia::Facet_Search)
			$this->context->controller->addJS($this->_path.'/js/algolia_facet_search.js');
		elseif (Configuration::get('ALGOLIA_SEARCH_TYPE') == Algolia::Simple_Search)
			$this->context->controller->addJS($this->_path.'/js/algolia_simple_search.js');*/
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
		//return $this->syncProducts();
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
