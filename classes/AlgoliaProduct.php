<?php

require_once(dirname(__FILE__)."/AlgoliaLibrary.php");

class AlgoliaProduct extends AlgoliaLibrary
{
    protected static $attributes = array(
        "available_now" => array("available_now"),
        "description" => array("description"),
        "description_short" => array("description_short"),
        "ean13" => "ean13",
        "image" => array("image", "method" => "imageLink"),
        "link_rewrite" => array("link_rewrite", "method" => "linkRewrite"),
        "name" => array("name"),
        "objectID" => "id",
        "price" => "price",
        "reference" => "reference",
    );

    protected static $languages = array();

    public static function getProduct($id_product)
    {
        if (count(self::$languages) === 0)
            foreach (Language::getLanguages() as $language)
                self::$languages[$language["id_lang"]] = $language["iso_code"];

        return self::initProduct($id_product);
    }

    protected static function initProduct($id_product)
    {
        $product = array();
        $ps_product = (array)new Product($id_product);
        // $values = array_intersect_key($ps_product, self::$attributes);

        foreach (self::$attributes as $key => $value)
        {
            if (is_array($value) === true)
            {
                foreach (self::$languages as $lang_key => $lang_iso_code)
                {
                    if (isset(self::$attributes[$key]["method"]) === true)
                    {
                        $method = "get".self::$attributes[$key]["method"];
                        $product[$key."_$lang_iso_code"] = self::$method($id_product, $ps_product[$key][$lang_key], $lang_key);
                    }
                    else
                        $product[$key."_$lang_iso_code"] = $ps_product[$key][$lang_key];
                }
            }
            else
                $product[$key] = $ps_product[$key];
        }

        d($product);
        return $product;
    }

    protected static function getImageLink($id_product, $link_rewrite, $id_lang)
    {
        d(func_get_args());
    }

    protected static function getLinkRewrite($id_product, $link_rewrite, $id_lang)
    {
        return Context::getContext()->link->getProductLink($id_product, $link_rewrite, null, null, $id_lang);
    }

    // protected function old()
    // {
    //     $link = new Link();
    //     $ps_product = new Product($id_product);
    //     d($ps_product);
    //     $category = new Category($product->id_category_default, $id_lang);
    //
    //     $product->objectID = $product->id;
    //     $product->category = $category->name;
    //     $product->url = $link->getProductLink($product->id);
    //
    //     /* Cover */
    //     $cover = Image::getCover($product->id);
    //     $product->image_link_small = $link->getImageLink($product->link_rewrite, $cover["id_image"], ImageType::getFormatedName("small"));
    //     $product->image_link_large = $link->getImageLink($product->link_rewrite, $cover["id_image"], ImageType::getFormatedName("large"));
    //
    //     return $product;
    //
    //     die(var_dump(self::$attributes));
    // }
}
