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

    public function indexAllProducts()
    {
        /*global $wpdb;

        foreach (array_keys($this->algolia_registry->indexable_types) as $type)
        {
            $query = "SELECT COUNT(*) as count FROM " . $wpdb->posts . " WHERE post_status IN ('publish') AND post_type = '".$type."'";
            $result = $wpdb->get_results($query);
            $count = $result[0]->count;
            $max = 10000;

            for ($i = 0; $i < ceil($count / $max); $i++)
                $this->indexPostsTypePart($type, $max, $i * $max);
        }*/
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