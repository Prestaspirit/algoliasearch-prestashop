<?php namespace Algolia\Core;

class AttributesHelper
{
    private $algolia_registry;

    public function __construct()
    {
        $this->algolia_registry = Registry::getInstance();
    }

    public function getAllAttributes()
    {
        $attributes = array();

        $metas = $this->algolia_registry->metas;

        $type = 'attribute';
        foreach (array_keys(\Algolia\Core\PrestashopFetcher::$attributes) as $defaultAttribute)
        {
            $id = $defaultAttribute;

            $attributes[$id]                 = new \stdClass();

            $attributes[$id]->id             = 0;
            $attributes[$id]->type           = $type;
            $attributes[$id]->name           = $defaultAttribute;
            $attributes[$id]->checked        = true;
            $attributes[$id]->facetable      = isset($metas[$id]) ? $metas[$id]['facetable'] : false;
            $attributes[$id]->facet_type     = isset($metas[$id]) ? $metas[$id]['type'] : 'conjunctive';
        }

        $type = 'feature';
        foreach($this->getFeatures() as $feature)
        {
            $id = $type.'_'.$feature['id_feature'];

            $attributes[$id]                 = new \stdClass();

            $attributes[$id]->id             = $feature['id_feature'];
            $attributes[$id]->type           = $type;
            $attributes[$id]->name           = $feature['name'];
            $attributes[$id]->checked        = isset($metas[$id]) ? $metas[$id]['indexable'] : false;
            $attributes[$id]->facetable      = isset($metas[$id]) ? $metas[$id]['facetable'] : false;
            $attributes[$id]->facet_type     = isset($metas[$id]) ? $metas[$id]['type'] : 'conjunctive';
        }

        $type = 'group';
        foreach($this->getAttributes() as $attribute)
        {
            $id = $type.'_'.$attribute['id'];

            $attributes[$id]                 = new \stdClass();

            $attributes[$id]->id             = $attribute['id'];
            $attributes[$id]->type           = $type;
            $attributes[$id]->name           = $attribute['attribute_group'];
            $attributes[$id]->checked        = isset($metas[$id]) ? $metas[$id]['indexable'] : false;
            $attributes[$id]->facetable      = isset($metas[$id]) ? $metas[$id]['facetable'] : false;
            $attributes[$id]->facet_type     = isset($metas[$id]) ? $metas[$id]['type'] : 'conjunctive';
        }

        return $attributes;
    }


    private function getFeatures()
    {
        global $cookie;

        return \Feature::getFeatures($cookie->id_lang);
    }

    private function getAttributes()
    {
        global $cookie;

        return \Db::getInstance()->executeS('
			SELECT DISTINCT agl.`id_attribute_group` as `id`, agl.`name` AS `attribute_group`
			FROM `'._DB_PREFIX_.'attribute_group` ag
			LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl
				ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.(int)$cookie->id_lang.')
			LEFT JOIN `'._DB_PREFIX_.'attribute` a
				ON a.`id_attribute_group` = ag.`id_attribute_group`
			LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al
				ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int)$cookie->id_lang.')
			'.\Shop::addSqlAssociation('attribute_group', 'ag').'
			'.\Shop::addSqlAssociation('attribute', 'a').'
			'.(false ? 'WHERE a.`id_attribute` IS NOT NULL AND al.`name` IS NOT NULL AND agl.`id_attribute_group` IS NOT NULL' : '').'
			ORDER BY agl.`name` ASC, a.`position` ASC
		');
    }
}