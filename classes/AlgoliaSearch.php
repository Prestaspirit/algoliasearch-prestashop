<?php

require_once(dirname(__FILE__).'/AbstractAlgolia.php');

class AlgoliaSearch extends AbstractAlgolia
{
    public function getIndexName()
    {
        return $this->index_name;
    }
}
