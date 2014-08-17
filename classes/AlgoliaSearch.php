<?php

require_once(dirname(__FILE__).'/AbstractAlgolia.php');

class AlgoliaSearch extends AbstractAlgolia
{
    public function getIndexName()
    {
        $iso_code = Context::getContext()->language->iso_code;
        return $this->index_name.'_'.$iso_code;
    }
}
