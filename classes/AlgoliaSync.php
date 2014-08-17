<?php

require_once(dirname(__FILE__).'/AlgoliaLibrary.php');

class AlgoliaSync extends AlgoliaLibrary
{

    public function syncProducts()
    {
    	if ($this->isConfigurationValid() == false)
    		return false;
    
        $iso_codes = array();
        $client = new \AlgoliaSearch\Client($this->application_id, $this->api_key);

        foreach (Language::getLanguages() as $language)
        {
            $index_name = $this->index_name.'_'.$language['iso_code'];
            $index = $client->initIndex($index_name);

            $products = $this->addProductsToIndex($index, $language);
            $index->saveObjects($products);
        }
    }

    protected function addProductsToIndex(&$index, $language)
    {
        $products = array();
        $id_products = Db::getInstance()->executeS('SELECT `id_product` FROM `'._DB_PREFIX_.'product`');

        if (count($id_products) > 0)
        {
            foreach ($id_products as &$product)
            {
                $product = (array) new Product($product['id_product'], true, $language['id_lang']);
                $product['objectID'] = $product['id'];

                foreach ($product as $key => $value)
                    if (is_array($value))
                        unset($product[$key]);

                array_push($products, $product);
            }
        }

        return $products;
    }
}
