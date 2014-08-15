<?php

require_once(dirname(__FILE__).'/AbstractAlgolia.php');

class AlgoliaSync extends AbstractAlgolia
{

    public function syncProducts()
    {
        $client = new \AlgoliaSearch\Client($this->application_id, $this->api_key);

        $index = $client->initIndex($this->index_name);

        $products = array();
        $id_lang = Context::getContext()->language->id;
        $id_products = Db::getInstance()->executeS('SELECT `id_product` FROM `'._DB_PREFIX_.'product`');

        if (count($id_products) > 0)
        {
            foreach ($id_products as &$product)
            {
                $product = (array) new Product($product['id_product']);
                $product['objectID'] = $product['id'];
                array_push($products, $product);
                continue;
            }
        }

        $index->saveObjects($products);
    }

}
