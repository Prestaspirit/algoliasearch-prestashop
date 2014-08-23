<?php

require_once(dirname(__FILE__)."/AlgoliaLibrary.php");

class AlgoliaProduct extends AlgoliaLibrary
{
    protected static $attributes = array(
        "available_now",
        "category" => array(
            "method" => "getCategoryName"
        ),
        "categories" => array(
            "method" => "getCategoriesNames"
        ),
        "description",
        "description_short",
        "ean13",
        "image" => array(
            "method" => "generateImageLink"
        ),
        "link_rewrite" => array(
            "method" => "generateLinkRewrite"
        ),
        "name",
        "price",
        "reference",
    );

    protected static $product_definition = false;

    protected static $languages = array();

    public static function getProduct($id_product)
    {
        if (count(self::$languages) === 0)
        {
            foreach (Language::getLanguages() as $language)
                self::$languages[$language["id_lang"]] = $language["iso_code"];

            self::$product_definition = Product::$definition['fields'];
        }

        return (array)self::initProduct($id_product);
    }

    protected static function initProduct($id_product)
    {
        $product = new stdClass();
        $ps_product = new Product($id_product);

        /* Required by Algolia */
        $product->objectID = $ps_product->id;

        foreach (self::$attributes as $key => $value)
        {
            foreach (self::$languages as $id_lang => $iso_code)
            {
                if ((is_array($value) == true) && (isset($value["method"]) === true))
                {
                    $method = $value["method"];
                    $product = self::$method($product, $ps_product, $id_lang, $iso_code);
                }
                elseif (isset(self::$product_definition[$value]["lang"]) == true)
                    $product->{$value.'_'.$iso_code} = $ps_product->{$value}[$id_lang];
                else
                    $product->{$value} = $ps_product->{$value};
            }
        }

        return $product;
    }

    protected static function getCategoryName($product, $ps_product, $id_lang, $iso_code)
    {
        $category = new Category($ps_product->id_category_default, $id_lang);
        $product->{"category_$iso_code"} = $category->name;
        return $product;
    }

    protected static function getCategoriesNames($product, $ps_product, $id_lang, $iso_code)
    {
        $product->{"categories_$iso_code"} = array();
        $id_categories = Product::getProductCategories($ps_product->id);

        foreach ($id_categories as $id_category)
        {
            $category = new Category($id_category, $id_lang);
            array_push($product->{"categories_$iso_code"}, $category->name);
        }

        return $product;
    }

    protected static function generateImageLink($product, $ps_product, $id_lang, $iso_code)
    {
        $link = new Link();
        $cover = Image::getCover($ps_product->id);

        $product->{"image_link_small_$iso_code"} = $link->getImageLink($ps_product->link_rewrite[$id_lang], $cover["id_image"], ImageType::getFormatedName("small"));
        $product->{"image_link_large_$iso_code"} = $link->getImageLink($ps_product->link_rewrite[$id_lang], $cover["id_image"], ImageType::getFormatedName("large"));

        return $product;
    }

    protected static function generateLinkRewrite($product, $ps_product, $id_lang, $iso_code)
    {
        $link = new Link();
        $product->{"link_$iso_code"} = $link->getProductLink($ps_product->id, $ps_product->link_rewrite[$id_lang], null, null, $id_lang);
        return $product;
    }

}
