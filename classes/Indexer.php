<?php namespace Algolia\Core;

class Indexer
{
    private $algolia_helper;
    private $algolia_registry;
    private $prestashop_fetcher;

    public function __construct()
    {
        $this->algolia_registry     = \Algolia\Core\Registry::getInstance();

        if ($this->algolia_registry->validCredential)
        {
            $this->algolia_helper = new \Algolia\Core\AlgoliaHelper(
                $this->algolia_registry->app_id,
                $this->algolia_registry->search_key,
                $this->algolia_registry->admin_key
            );
        }

        $this->prestashop_fetcher  = new PrestashopFetcher();
    }

    public function moveTempIndexes()
    {
        foreach (\Language::getLanguages() as $language)
            $this->algolia_helper->move($this->algolia_registry->index_name.'all_' . $language['iso_code'] . '_temp',
                                        $this->algolia_registry->index_name.'all_' . $language['iso_code']);

        $this->algolia_helper->handleIndexCreation();
    }

    private function getProducts($limit, $language)
    {
        $products = array();
        $id_products = \Db::getInstance()->executeS('SELECT `id_product` FROM `'._DB_PREFIX_.'product` WHERE `active` IS TRUE '.$limit);

        if (count($id_products) > 0)
            foreach ($id_products as $id_product)
                array_push($products, $this->prestashop_fetcher->getProductObj($id_product['id_product'], $language));

        return $products;
    }

    public function indexProduct($product)
    {
        if ($product->active == false)
        {
            $this->deleteProduct($product->id);
            return;
        }

        foreach (\Language::getLanguages() as $language)
        {
            $object = $this->prestashop_fetcher->getProductObj($product->id, $language);

            $this->algolia_helper->pushObject($this->algolia_registry->index_name.'all_' . $language['iso_code'], $object);
        }
    }

    private function getNestedCats($cats, $names, &$results, $id_lang)
    {
        foreach ($cats as $cat)
        {
            if (isset($cat['children']) && is_array($cat['children']) && count($cat['children']) > 0)
            {
                if ($cat['is_root_category'] == 0)
                    $names[] = $cat['name'];

                $this->getNestedCats($cat['children'], $names, $results, $id_lang);
            }
            else
            {
                if ($cat['is_root_category'] == 0)
                {
                    $category = new \Category($cat['id_category']);
                    $product_count = $category->getProducts($id_lang, 1, 10000, null, null, true);
                    $link = new \Link();
                    $link = $link->getCategoryLink($cat['id_category'], null, $id_lang);

                    $names[] = array('name' => $cat['name'], 'objectID' => $cat['id_category'], 'product_count' => $product_count, 'url' => $link);
                }

                $results[] = $names;
                array_pop($names);
            }
        }
    }

    private function getCategories($id_lang)
    {
        $cats = \Category::getNestedCategories(null, $id_lang);

        $results = array();
        $this->getNestedCats($cats, array(), $results, $id_lang);

        $results = array_map(function ($cat) {
            $new_cat = $cat[count($cat) - 1];
            $path = $cat;
            array_pop($path);

            $path[] = $new_cat['name'];
            $new_cat['path'] = implode(' / ', $path);

            return $new_cat;
        }, $results);

        return $results;
    }

    public function indexCategories()
    {
        foreach (\Language::getLanguages() as $language)
            $this->algolia_helper->pushObjects($this->algolia_registry->index_name.'categories_' . $language['iso_code'], $this->getCategories($language['id_lang']));

    }

    public function deleteProduct($product_id)
    {
        foreach (\Language::getLanguages() as $language)
            $this->algolia_helper->deleteObject($this->algolia_registry->index_name.'all_' . $language['iso_code'], $product_id);
    }

    public function indexProductsPart($count, $offset)
    {
        foreach (\Language::getLanguages() as $language)
        {
            $objects = $this->getProducts("LIMIT ".($offset * $count).",".$count, $language);

            $this->algolia_helper->pushObjects($this->algolia_registry->index_name.'all_' . $language['iso_code'] . '_temp', $objects);
        }
    }
}