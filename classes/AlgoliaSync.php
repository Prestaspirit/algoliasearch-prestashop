<?php

require_once(dirname(__FILE__).'/AlgoliaLibrary.php');

class AlgoliaSync extends AlgoliaLibrary
{

	protected $index_settings = array(
		"attributesToIndex" => array("name", "category"),
		"customRanking" => array("asc(base_price)", 'desc(date_upd)')
	);

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

            $index->setSettings($this->index_settings);
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
            	$id_lang = $language['id_lang'];
            	$id_product = $product['id_product'];

                $product = (array) $this->formatProduct($id_product, $id_lang);


                foreach ($product as $key => $value)
                    if (is_array($value))
                        unset($product[$key]);

                array_push($products, $product);
            }
        }

        return $products;
    }

    protected function formatProduct($id_product, $id_lang)
    {
    	$link = new Link();
    	$product = new Product($id_product, true, $id_lang);
	    $category = new Category($product->id_category_default, $id_lang);

        $product->objectID = $product->id;
	    $product->category = $category->name;
		$product->link = $link->getProductLink($product->id);



		$cover = Image::getCover($product->id);
		$type = ImageType::getFormatedName('small');

	    $product->image_link = $link->getImageLink($product->link_rewrite, $cover['id_image'], $type);

	    return $product;
    }
}
