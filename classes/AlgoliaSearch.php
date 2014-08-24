<?php

require_once(dirname(__FILE__).'/AlgoliaLibrary.php');

class AlgoliaSearch extends AlgoliaLibrary
{
    public function getIndexName()
    {
        return $this->index_name;
    }
}
