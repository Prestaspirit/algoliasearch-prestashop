<?php

class FrontAlgoliaController
{
    private $algolia_helper;
    private $algolia_registry;
    private $theme_helper;
    private $indexer;
    private $attribute_helper;

    private $module;

    public function __construct(&$module)
    {
        $this->module           = $module;

        $this->algolia_registry = \Algolia\Core\Registry::getInstance();
        $this->theme_helper     = new \Algolia\Core\ThemeHelper($this->module);
        $this->indexer          = new \Algolia\Core\Indexer();
        $this->attribute_helper = new \Algolia\Core\AttributesHelper();

        if ($this->algolia_registry->validCredential)
        {
            $this->algolia_helper   = new \Algolia\Core\AlgoliaHelper(
                $this->algolia_registry->app_id,
                $this->algolia_registry->search_key,
                $this->algolia_registry->admin_key
            );
        }
    }

    public function hookDisplayHeader()
    {
        if ($this->algolia_registry->validCredential == false)
            return false;

        $search_url = Context::getContext()->link->getModuleLink('algolia', 'search');
        $this->module->getContext()->smarty->assign('algolia_search_url', $search_url);

        /* Add CSS & JS files required for Algolia search */
        $this->module->getContext()->controller->addJS($this->module->getPath().'/libraries/typeahead/typeahead.js');
        $this->module->getContext()->controller->addJS($this->module->getPath().'/libraries/hogan/hogan.js');
        $this->module->getContext()->controller->addJS($this->module->getPath().'/libraries/jquery/jquery-ui.js');
        $this->module->getContext()->controller->addJS($this->module->getPath().'/libraries/algolia/algoliasearch.min.js');

        $this->module->getContext()->controller->addJS($this->module->getPath().'/js/main.js');

        $this->module->getContext()->controller->addCSS($this->module->getPath().'/libraries/jquery/jquery-ui.min.css');

        $this->module->getContext()->controller->addCSS($this->module->getPath().'/themes/'.$this->algolia_registry->theme.'/styles.css');
        $this->module->getContext()->controller->addJS($this->module->getPath().'/themes/'.$this->algolia_registry->theme.'/theme.js');

        global $cookie;

        $current_language = \Language::getIsoById($cookie->id_lang);

        $indices = array();

        if ($this->algolia_registry->number_products > 0)
            $indices[] = array('index_name' => $this->algolia_registry->index_name.'all_'.$current_language, 'name' => 'products', 'order1' => 1, 'order2' => 0, 'nbHits' => $this->algolia_registry->number_products);

        if ($this->algolia_registry->number_categories > 0)
            $indices[] = array('index_name' => $this->algolia_registry->index_name.'categories_'.$current_language, 'name' => 'categories', 'order1' => 0, 'order2' => 0, 'nbHits' => $this->algolia_registry->number_categories);

        $facets = array();
        $sorting_indices = array();

        $attributes = $this->attribute_helper->getAllAttributes($cookie->id_lang);

        foreach ($this->algolia_registry->sortable as $sortable)
            $sorting_indices[] = array(
                'index_name' => $this->algolia_registry->index_name . 'all_' . $current_language . '_' . $sortable['name'] . '_' . $sortable['sort'],
                'label' => $sortable['name'] . '_' . $sortable['sort']
            );

        foreach ($attributes as $key => $value)
        {
            if (isset($this->algolia_registry->metas[$key]) && $this->algolia_registry->metas[$key]['facetable'])
                $facets[] = array('tax' => $value->name, 'name' => $value->name, 'order1' => $value->order, 'order2' => 0, 'type' => $value->facet_type);
        }

        $currency = new CurrencyCore($cookie->id_currency);
        $currency = $currency->sign;

        $algoliaSettings = array(
            'app_id'                    => $this->algolia_registry->app_id,
            'search_key'                => $this->algolia_registry->search_key,
            'indices'                   => $indices,
            'sorting_indices'           => $sorting_indices,
            'index_name'                => $this->algolia_registry->index_name,
            'type_of_search'            => $this->algolia_registry->type_of_search,
            'instant_jquery_selector'   => str_replace("\\", "", $this->algolia_registry->instant_jquery_selector),
            'facets'                    => $facets,
            'number_by_page'            => $this->algolia_registry->number_by_page,
            'search_input_selector'     => str_replace("\\", "", $this->algolia_registry->search_input_selector),
            "plugin_url"                => $this->module->getPath(),
            "language"                  => $current_language,
            'theme'                     => $this->theme_helper->get_current_theme(),
            'currency'                  => $currency
    );

        Media::addJsDef(array('algoliaSettings' => $algoliaSettings));
    }

    public function hookActionSearch($params)
    {
        if (in_array('instant', $this->algolia_registry->type_of_search))
        {
            global $cookie;

            $current_language = \Language::getIsoById($cookie->id_lang);

            $url = '/index.php#q='.$params['expr'].'&page=0&refinements=%5B%5D&numerics_refinements=%7B%7D&index_name=%22'.$this->algolia_registry->index_name.'all_'.$current_language.'%22';

            header('Location: '.$url);

            die();
        }

    }

    public function hookActionProductListOverride($params)
    {
        if (in_array('instant', $this->algolia_registry->type_of_search) == false)
            return;

        if ($this->algolia_registry->replace_categories == false || isset($this->algolia_registry->metas['categories']) == false)
            return;

        if (isset($_GET['id_category']) == false || is_numeric($_GET['id_category']) == false)
            return;

        $category_id = $_GET['id_category'];

        $path = array();
        $context = Context::getContext();
        $interval = Category::getInterval($category_id);
        $id_root_category = $context->shop->getCategory();
        $interval_root = Category::getInterval($id_root_category);

        if ($interval)
        {
            $sql = 'SELECT c.id_category, cl.name, cl.link_rewrite
						FROM '._DB_PREFIX_.'category c
						LEFT JOIN '._DB_PREFIX_.'category_lang cl ON (cl.id_category = c.id_category'.Shop::addSqlRestrictionOnLang('cl').')
						'.Shop::addSqlAssociation('category', 'c').'
						WHERE c.nleft <= '.$interval['nleft'].'
							AND c.nright >= '.$interval['nright'].'
							AND c.nleft >= '.$interval_root['nleft'].'
							AND c.nright <= '.$interval_root['nright'].'
							AND cl.id_lang = '.(int)$context->language->id.'
							AND c.active = 1
							AND c.level_depth > '.(int)$interval_root['level_depth'].'
						ORDER BY c.level_depth ASC';

            $categories = Db::getInstance()->executeS($sql);

            foreach ($categories as $category)
            {
                $path[] = $category['name'];
            }
        }

        $path = implode(' /// ', $path);

        global $cookie;

        $current_language = \Language::getIsoById($cookie->id_lang);

        $url = '/index.php?category=1#q=&page=0&refinements=%5B%7B%22categories%22%3A%22'.$path.'%22%7D%5D&numerics_refinements=%7B%7D&index_name=%22'.$this->algolia_registry->index_name.'all_'.$current_language.'%22';

        header('Location: '.$url);

        die();
    }

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
        if (strcmp(Tools::getValue('configure'), $this->module->name) === 0)
            $this->module->getContext()->controller->addCSS($this->module->getPath().'css/configure.css');
    }

    public function hookDisplayFooter()
    {
        $path = $this->module->getPath();

        include __DIR__.'/../../themes/'.$this->algolia_registry->theme.'/templates.php';
    }
}
