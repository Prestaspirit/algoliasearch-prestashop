<?php namespace Algolia\Core;


class PrestashopFetcher
{
    static $attributes = array(
        "available_now",
        "category" => array(
            "method" => "getCategoryName"
        ),
        "categories" => array(
            "method" => "getCategoriesNames"
        ),
        "date_add",
        "date_upd",
        "description",
        "description_short",
        "ean13",
        "image" => array(
            "method" => "generateImageLink"
        ),
        "link_rewrite" => array(
            "method" => "generateLinkRewrite"
        ),
        "manufacturer" => array(
            "method" => "getManufacturerName"
        ),
        "name",
        "price",
        "prices" => array(
            "method" => "getPrices"
        ),
        "reference",
        "supplier" => array(
            "method" => "getSupplierName"
        ),
        "features" => array(
            "method" => "getFeatures"
        )
    );

    private $product_definition = false;

    public function __construct()
    {
        $this->product_definition = \Product::$definition['fields'];
    }

    private function try_cast($value)
    {
        if (is_numeric($value) && floatval($value) == intval($value))
            return intval($value);

        if (is_numeric($value))
            return floatval($value);

        return $value;
    }

    public function getProductObj($id_product, $language)
    {
        return (array) $this->initProduct($id_product, $language);
    }

    public function getPrices($product, $ps_product)
    {
        $product->price_tax_excl = \Product::getPriceStatic($ps_product->id, false, null, 2);
        $product->price_tax_incl = \Product::getPriceStatic($ps_product->id, true, null, 2);

        return $product;
    }

    public function getFeatures($product, $ps_product, $id_lang, $iso_code)
    {
        foreach ($ps_product->getFrontFeatures($id_lang) as $feature)
        {
            $name   = $feature['name'];
            $value  = $feature['value'];

            $product->$name = $value;
        }

        return $product;
    }

    private function initProduct($id_product, $language)
    {
        $product = new \stdClass();
        $ps_product = new \Product($id_product);

        /* Required by Algolia */
        $product->objectID = $ps_product->id;

        foreach (static::$attributes as $key => $value)
        {
            if ((is_array($value) == true) && (isset($value["method"]) === true))
            {
                $method = $value["method"];
                $product = $this->try_cast(self::$method($product, $ps_product, $language['id_lang'], $language['iso_code']));
            }
            elseif (isset($this->product_definition[$value]["lang"]) == true)
                $product->{$value} = $this->try_cast($ps_product->{$value}[$language['id_lang']]);
            else
                $product->{$value} = $this->try_cast($ps_product->{$value});
        }

        return $product;
    }

    protected static function generateImageLink($product, $ps_product, $id_lang, $iso_code)
    {
        $link = new \Link();
        $cover = \Image::getCover($ps_product->id);

        $product->image_link_small = $link->getImageLink($ps_product->link_rewrite[$id_lang], $cover["id_image"], \ImageType::getFormatedName("small"));
        $product->image_link_large = $link->getImageLink($ps_product->link_rewrite[$id_lang], $cover["id_image"], \ImageType::getFormatedName("large"));

        return $product;
    }

    protected static function generateLinkRewrite($product, $ps_product, $id_lang, $iso_code)
    {
        $link = new \Link();
        $product->link = $link->getProductLink($ps_product->id, $ps_product->link_rewrite[$id_lang], null, null, $id_lang);

        return $product;
    }

    protected static function getCategoryName($product, $ps_product, $id_lang, $iso_code)
    {
        $category = new \Category($ps_product->id_category_default, $id_lang);
        $product->category = $category->name;

        return $product;
    }

    protected static function getCategoriesNames($product, $ps_product, $id_lang, $iso_code)
    {
        $product->categories = array();
        $id_categories = \Product::getProductCategories($ps_product->id);

        foreach ($id_categories as $id_category)
        {
            $category = new \Category($id_category, $id_lang);
            array_push($product->categories, $category->name);
        }

        return $product;
    }

    public static function getManufacturerName($product, $ps_product, $id_lang, $iso_code)
    {
        $product->manufacturer = $ps_product->manufacturer_name;

        return $product;
    }

    public static function getSupplierName($product, $ps_product, $id_lang, $iso_code)
    {
        $product->supplier = $ps_product->supplier_name;

        return $product;
    }
}