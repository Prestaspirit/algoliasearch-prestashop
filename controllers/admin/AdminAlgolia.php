<?php

include_once(PS_ADMIN_DIR.'/../classes/AdminTab.php');

class AdminAlgoliaController extends ModuleAdminController
{
    private $algolia_registry;
    private $theme_helper;
    private $indexer;
    private $algolia_helper;

    public function __construct()
    {
        parent::__construct();

        $this->bootstrap = true;

        $this->algolia_registry = Algolia\Core\Registry::getInstance();
        $this->theme_helper     = new \Algolia\Core\ThemeHelper($this->module);
        $this->indexer          = new \Algolia\Core\Indexer();

        if ($this->algolia_registry->validCredential)
        {
            $this->algolia_helper   = new \Algolia\Core\AlgoliaHelper(
                $this->algolia_registry->app_id,
                $this->algolia_registry->search_key,
                $this->algolia_registry->admin_key
            );
        }
    }

    public function initContent()
    {
        parent::initContent();

        $this->context->smarty->assign('module_dir', $this->module->getPath());
        $this->context->smarty->assign('warnings', array());
        $this->context->smarty->assign('algolia_registry', $this->algolia_registry);
        $this->context->smarty->assign('theme_helper', $this->theme_helper);

        $products_count = \Db::getInstance()->executeS('SELECT count(*) as count FROM `'._DB_PREFIX_.'product` WHERE `active` IS TRUE');

        $algoliaAdminSettings = array(
            "types"         => array(array('type' => 'products', 'name' => 'Products', 'count' => (int) $products_count[0]['count'])),
            "batch_count"   => $this->module->batch_count,
            "site_url"      => $this->module->getPath()
        );

        Media::addJsDef(array('algoliaAdminSettings' => $algoliaAdminSettings));

        $content = $this->context->smarty->fetch($this->getTemplatePath() . 'content.tpl');

        $this->context->smarty->assign(array('content' => $content));

        $this->context->controller->addJS($this->module->getPath().'js/admin.js');
        $this->context->controller->addCSS($this->module->getPath().'css/configure.css');
        $this->context->controller->addCSS($this->module->getPath().'css/admin.css');
    }

    public function postProcess()
    {
        parent::postProcess();

        if (Tools::isSubmit('submitAlgoliaSettings'))
        {
            $action = Tools::getValue('action');

            if (method_exists($this, $action))
                $this->$action();
        }

    }

    public function admin_post_update_account_info()
    {
        $app_id     = !empty($_POST['APP_ID'])      ? $_POST['APP_ID'] : '';
        $search_key = !empty($_POST['SEARCH_KEY'])  ? $_POST['SEARCH_KEY'] : '';
        $admin_key  = !empty($_POST['ADMIN_KEY'])   ? $_POST['ADMIN_KEY'] : '';
        $index_name = !empty($_POST['INDEX_NAME'])  ? $_POST['INDEX_NAME'] : '';

        $algolia_helper = new \Algolia\Core\AlgoliaHelper($app_id, $search_key, $admin_key);

        $this->algolia_registry->app_id     = $app_id;
        $this->algolia_registry->search_key = $search_key;
        $this->algolia_registry->admin_key  = $admin_key;
        $this->algolia_registry->index_name = $index_name;

        $algolia_helper->checkRights();
    }

    public function admin_post_update_type_of_search()
    {
        if (isset($_POST['TYPE_OF_SEARCH']) && in_array($_POST['TYPE_OF_SEARCH'], array('instant', 'autocomplete')))
            $this->algolia_registry->type_of_search = $_POST['TYPE_OF_SEARCH'];

        if (isset($_POST['JQUERY_SELECTOR']))
            $this->algolia_registry->instant_jquery_selector = str_replace('"', '\'', $_POST['JQUERY_SELECTOR']);

        if (isset($_POST['NUMBER_BY_PAGE']) && is_numeric($_POST['NUMBER_BY_PAGE']))
            $this->algolia_registry->number_by_page = $_POST['NUMBER_BY_PAGE'];

        if (isset($_POST['NUMBER_OF_WORD_FOR_CONTENT']) && is_numeric($_POST['NUMBER_OF_WORD_FOR_CONTENT']))
            $this->algolia_registry->number_of_word_for_content = $_POST['NUMBER_OF_WORD_FOR_CONTENT'];

        if (isset($_POST['NUMBER_BY_TYPE']) && is_numeric($_POST['NUMBER_BY_TYPE']))
            $this->algolia_registry->number_by_type = $_POST['NUMBER_BY_TYPE'];

        $search_input_selector  = !empty($_POST['SEARCH_INPUT_SELECTOR']) ? $_POST['SEARCH_INPUT_SELECTOR'] : '';
        $theme                  = !empty($_POST['THEME']) ? $_POST['THEME'] : 'default';

        $this->algolia_registry->search_input_selector  = str_replace('"', '\'', $search_input_selector);
        $this->algolia_registry->theme                  = $theme;

        //$this->algolia_helper->handleIndexCreation();
    }

    public function admin_post_reindex()
    {
        foreach ($_POST as $post)
        {
            $subaction = explode("__", $post);

            if (count($subaction) == 1 && $subaction[0] != "reindex")
            {
                if ($subaction[0] == 'handle_index_creation')
                {
                    $this->algolia_helper->handleIndexCreation();
                }

                if ($subaction[0] == 'index_taxonomies')
                {
                    //$this->indexer->indexTaxonomies();

                }
                if ($subaction[0] == 'move_indexes')
                {
                    $this->indexer->moveTempIndexes();
                }
            }

            if (count($subaction) == 2)
            {
                $this->indexer->indexProductsPart($this->module->batch_count, $subaction[1]);
            }
        }

        die();
    }
}