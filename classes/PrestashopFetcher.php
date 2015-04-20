<?php namespace Algolia\Core;


class PrestashopFetcher
{
    static $attributes = array(
        "available_now"     => null,
        "category"          => "getCategoryName",
        "categories"        => "getCategoriesNames",
        "date_add"          => null,
        "date_upd"          => null,
        "description"       => null,
        "description_short" => null,
        "ean13"             => null,
        "image_link_large"  => "generateImageLinkLarge",
        "image_link_small"  => "generateImageLinkSmall",
        "link"              => "generateLinkRewrite",
        "manufacturer"      => "getManufacturerName",
        "name"              => null,
        "price"             => null,
        "price_tax_incl"    => "getPriceTaxIncl",
        "price_tax_excl"    => "getPriceTaxExcl",
        "reference"         => null,
        "supplier"          => "getSupplierName",
        'ordered_qty'       => 'getOrderedQty',
        'stock_qty'         => 'getStockQty',
        'condition'         => null,
        'weight'            => null
    );

    private $product_definition = false;

    public function __construct()
    {
        $this->product_definition = \Product::$definition['fields'];
    }

    public function getProductObj($id_product, $language)
    {
        return (array) $this->initProduct($id_product, $language);
    }

    private function try_cast($value)
    {
        if (is_numeric($value) && floatval($value) == floatval(intval($value)))
            return intval($value);

        if (is_numeric($value))
            return floatval($value);

        return $value;
    }

    private function initProduct($id_product, $language)
    {
        $product = new \stdClass();
        $ps_product = new \Product($id_product);

        /* Required by Algolia */
        $product->objectID = $ps_product->id;

        /** Default Attribute **/
        foreach (static::$attributes as $key => $value)
        {
            if ($value != null && method_exists($this, $value))
            {
                $product->$key = $this->$value($product, $ps_product, $language['id_lang'], $language['iso_code']);
                continue;
            }

            if (isset($this->product_definition[$key]["lang"]) == true)
                $product->$key = $ps_product->{$key}[$language['id_lang']];
            else
                $product->$key = $ps_product->{$key};
        }

        /** Features **/
        foreach ($ps_product->getFrontFeatures($language['id_lang']) as $feature)
        {
            $name   = $feature['name'];
            $value  = $feature['value'];

            $product->$name = $value;
        }

        /** Attribute groups **/
        foreach ($ps_product->getAttributesGroups($language['id_lang']) as $attribute)
        {
            if (isset($product->{$attribute['group_name']}) == false)
                $product->{$attribute['group_name']} = array();

            if (in_array($attribute['attribute_name'], $product->{$attribute['group_name']}) == false)
                $product->{$attribute['group_name']}[] = $attribute['attribute_name'];
        }

        /** Casting **/
        foreach ($product as $key => &$value)
            $value = $this->try_cast($value);

        return $product;
    }


    /**
     * GETTERS
     */

    private function getStockQty($product, $ps_product)
    {
        return \Product::getQuantity($ps_product->id);
    }

    private function getOrderedQty($product, $ps_product)
    {
        $product_sold = \Db::getInstance()->getRow('SELECT SUM(product_quantity) as total FROM `'._DB_PREFIX_.'order_detail` where product_id = ' . $ps_product->id);

        return $product_sold['total'];
    }

    private function getPriceTaxExcl($product, $ps_product)
    {
        return \Product::getPriceStatic($ps_product->id, false, null, 2);
    }

    private function getPriceTaxIncl($product, $ps_product)
    {
        return \Product::getPriceStatic($ps_product->id, true, null, 2);
    }

    private function generateImageLinkLarge($product, $ps_product, $id_lang)
    {
        $link = new \Link();
        $cover = \Image::getCover($ps_product->id);

        return $link->getImageLink($ps_product->link_rewrite[$id_lang], $cover["id_image"], \ImageType::getFormatedName("large"));
    }

    private function generateImageLinkSmall($product, $ps_product, $id_lang)
    {
        $link = new \Link();
        $cover = \Image::getCover($ps_product->id);

        return $link->getImageLink($ps_product->link_rewrite[$id_lang], $cover["id_image"], \ImageType::getFormatedName("small"));
    }

    private function generateLinkRewrite($product, $ps_product, $id_lang)
    {
        $link = new \Link();
        return $link->getProductLink($ps_product->id, $ps_product->link_rewrite[$id_lang], null, null, $id_lang);
    }

    private function getCategoryName($product, $ps_product, $id_lang)
    {
        $category = new \Category($ps_product->id_category_default, $id_lang);

        return $category->name;
    }

    private function getCategoriesNames($product, $ps_product, $id_lang)
    {
        $categories = array();
        $id_categories = \Product::getProductCategories($ps_product->id);

        foreach ($id_categories as $id_category)
        {
            $category = new \Category($id_category, $id_lang);
            array_push($categories, $category->name);
        }

        return $categories;
    }

    private function getManufacturerName($product, $ps_product)
    {
        return $ps_product->manufacturer_name;
    }

    private function getSupplierName($product, $ps_product)
    {
        return $ps_product->supplier_name;
    }
}