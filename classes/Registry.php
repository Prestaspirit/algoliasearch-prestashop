<?php namespace Algolia\Core;

class Registry
{
    private static $instance;
    private $options = array();
    private static $setting_key = 'algolia';

    private $attributes = array(
        'validCredential'               => false,
        'app_id'                        => '',
        'search_key'                    => '',
        'admin_key'                     => '',
        'index_name'                    => '',
        'searchable'                    => array('name' => array('ordered' => 'ordered', 'order' => 0), 'description' => array('ordered' => 'unordered', 'order' => 1)),
        'sortable'                      => array(),
        'type_of_search'                => array('autocomplete', 'instant'),
        'conjunctive_facets'            => array(),
        'disjunctive_facets'            => array(),
        'instant_jquery_selector'       => '#columns',
        'extras'                        => array(),
        'metas'                         => array(),
        'number_by_page'                => 10,
        'number_products'               => 3,
        'number_categories'             => 5,
        'number_of_word_for_content'    => 30,
        'search_input_selector'         => "[name='search_query']",
        'theme'                         => 'default',
        'replace_categories'            => true
    );

    public static function getInstance()
    {
        if (! isset(static::$instance))
            static::$instance = new self();

        return static::$instance;
    }

    private function __construct()
    {
        $options = unserialize(\Configuration::get(static::$setting_key));

        if (is_array($options))
            $this->options = $options;
        else
            $this->options = array();
    }

    public function __get($name)
    {
        if (isset($this->attributes[$name]))
        {
            if (isset($this->options[$name]))
                return $this->options[$name];
            else
                return $this->attributes[$name];
        }

        throw new \Exception("Unknown attribute: ".$name);
    }

    public function __set($name, $value)
    {
        if (isset($this->attributes[$name]))
        {
            $this->options[$name] = $value;
            $this->save();
        }
        else
        {
            throw new \Exception("Unknown attribute: ".$name);
        }
    }

    private function save()
    {
        $options = serialize($this->options);

        \Configuration::updateValue(static::$setting_key, $options);
    }

    public function reset_config_to_default()
    {
        foreach ($this->attributes as $key => $value)
            if (in_array($key, array('validCredential', 'app_id', 'search_key', 'admin_key', 'index_name')) == false)
                $this->options[$key] = $value;

        $this->save();
    }
}