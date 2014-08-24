<?php

require_once(dirname(__FILE__).'/AlgoliaLibrary.php');
require_once(dirname(__FILE__).'/AlgoliaProduct.php');

class AlgoliaSync extends AlgoliaLibrary
{

	public $index_settings = array(
		"attributesToIndex" => array("name", "description_short", "categories"),
		"attributesForFaceting" => array("available_now", "category"),
		"customRanking" => array("asc(price)"),
	);

	public function syncProducts()
	{
		if ($this->isConfigurationValid() == false)
			return false;

		$client = new \AlgoliaSearch\Client($this->application_id, $this->api_key);
		$index = $client->initIndex($this->index_name);
		$products = $this->getProductsToIndex();

		$settings = $this->getSettings();
		$index->setSettings($settings);
		return $index->saveObjects($products);
	}

	public function getSettings($id_lang = false)
	{
		$settings = $this->index_settings;

		$attributes_to_index = array();
		$attributes_for_faceting = array();

		foreach ($this->index_settings['attributesToIndex'] as $attribute)
		{
			if ($id_lang === false)
				foreach (Language::getLanguages() as $language)
					array_push($attributes_to_index, $attribute."_".$language['iso_code']);
			else
				array_push($attributes_to_index, $attribute."_".Language::getIsoById($id_lang));
		}

		foreach ($this->index_settings['attributesForFaceting'] as $attribute)
		{
			if ($id_lang === false)
				foreach (Language::getLanguages() as $language)
					array_push($attributes_for_faceting, $attribute."_".$language['iso_code']);
			else
				array_push($attributes_for_faceting, $attribute."_".Language::getIsoById($id_lang));
		}

		$settings['attributesToIndex'] = $attributes_to_index;
		$settings['attributesForFaceting'] = $attributes_for_faceting;

		return $settings;
	}

	protected function getProductsToIndex()
	{
		$products = array();
		$id_products = Db::getInstance()->executeS('SELECT `id_product` FROM `'._DB_PREFIX_.'product` WHERE `active` IS TRUE');

		if (count($id_products) > 0)
			foreach ($id_products as $id_product)
				array_push($products, AlgoliaProduct::getProduct($id_product['id_product']));

		return $products;
	}

	protected function formatProduct($id_product, $id_lang)
	{
		$link = new Link();
		$product = new Product($id_product, true, $id_lang);
		$category = new Category($product->id_category_default, $id_lang);

		$product->objectID = $product->id;
		$product->category = $category->name;
		$product->url = $link->getProductLink($product->id);

		/* Cover */
		$cover = Image::getCover($product->id);
		$product->image_link_small = $link->getImageLink($product->link_rewrite, $cover['id_image'], ImageType::getFormatedName('small'));
		$product->image_link_large = $link->getImageLink($product->link_rewrite, $cover['id_image'], ImageType::getFormatedName('large'));

		return $product;
	}
}
