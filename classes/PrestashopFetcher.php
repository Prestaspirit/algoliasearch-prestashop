<?php namespace Algolia\Core;


class PrestashopFetcher
{
    static $attributes = array(
        "available_now"             => null,
        "category"                  => "getCategoryName",
        "categories"                => "getCategoriesNames",
        "categories_without_path"   => "getCategoriesNamesWithoutPath",
        "date_add"                  => null,
        "date_upd"                  => null,
        "description"               => null,
        "description_short"         => null,
        "ean13"                     => null,
        "image_link_large"          => "generateImageLinkLarge",
        "image_link_small"          => "generateImageLinkSmall",
        "link"                      => "generateLinkRewrite",
        "manufacturer"              => "getManufacturerName",
        "name"                      => null,
        "price"                     => null,
        "price_tax_incl"            => "getPriceTaxIncl",
        "price_tax_excl"            => "getPriceTaxExcl",
        "reference"                 => null,
        "supplier"                  => "getSupplierName",
        'ordered_qty'               => 'getOrderedQty',
        'stock_qty'                 => 'getStockQty',
        'condition'                 => null,
        'weight'                    => null
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
                    $names[] = $cat['name'];
                }

                $results[] = $names;
                array_pop($names);
            }
        }
    }

    private function getNestedCatsWithoutPath($cats, &$results, $id_lang)
    {
        foreach ($cats as $cat)
        {
            if (isset($cat['children']) && is_array($cat['children']) && count($cat['children']) > 0)
            {
                $this->getNestedCatsWithoutPath($cat['children'], $results, $id_lang);
            }
            else
            {
                if ($cat['is_root_category'] == 0)
                    $results[] = $cat['name'];
            }
        }
    }

    private function getNestedCategoriesData($id_lang, $ps_product)
    {
        $cats = \Db::getInstance()->executeS('
				SELECT c.*, cl.*
				FROM `'._DB_PREFIX_.'category` c
				'.\Shop::addSqlAssociation('category', 'c').'
				LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON c.`id_category` = cl.`id_category`'.\Shop::addSqlRestrictionOnLang('cl').'
				LEFT JOIN `'._DB_PREFIX_.'category_product` cp ON cp.`id_product` = '.$ps_product->id.'
				WHERE 1 AND `id_lang` = '.(int)$id_lang.' AND c.`active` = 1
				AND cp.`id_category` = c.id_category
				ORDER BY c.`level_depth` ASC, category_shop.`position` ASC'
        );

        $categories = array();
        $buff = array();

        if (!isset($root_category))
            $root_category = \Category::getRootCategory()->id;

        foreach ($cats as $row)
        {
            $current = &$buff[$row['id_category']];
            $current = $row;

            if ($row['id_category'] == $root_category)
                $categories[$row['id_category']] = &$current;
            else
                $buff[$row['id_parent']]['children'][$row['id_category']] = &$current;
        }

        return $categories;
    }

    private function getCategoriesNamesWithoutPath($product, $ps_product, $id_lang)
    {
        $categories = $this->getNestedCategoriesData($id_lang, $ps_product);

        $results = array();
        $this->getNestedCatsWithoutPath($categories, $results, $id_lang);

        return $results;
    }

    private function getCategoriesNames($product, $ps_product, $id_lang)
    {
        $categories = $this->getNestedCategoriesData($id_lang, $ps_product);

        $results = array();
        $this->getNestedCats($categories, array(), $results, $id_lang);

        foreach ($results as $result)
        {
            for ($i = count($result) - 1; $i > 0; $i--)
            {
                $results[] = array_slice($result, 0, $i);
            }
        }

        $results = array_intersect_key($results, array_unique(array_map('serialize', $results)));
        
        foreach ($results as &$result)
            $result = implode(' /// ', $result);

        return $results;
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