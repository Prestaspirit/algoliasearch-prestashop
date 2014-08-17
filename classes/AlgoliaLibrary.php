<?php

class AlgoliaLibrary
{
    public $application_id = false;
    public $api_key = false;
    public $search_only_api_key = false;

    public $index_name = 'prestashop';

    public function __construct()
    {
        $this->application_id = Configuration::get('ALGOLIA_APPLICATION_ID', null);
        $this->api_key = Configuration::get('ALGOLIA_API_KEY', null);
        $this->search_only_api_key = Configuration::get('ALGOLIA_SEARCH_ONLY_API_KEY', null);

        $this->loadClasses();
    }
    
    public function isConfigurationValid()
    {
	    $invalid = is_null($this->getApplicationID()) || is_null($this->getAPIKey()) || is_null($this->getSearchOnlyAPIKey());
	    
	    return $invalid ? false : true;
    }

    public function getApplicationID()
    {
        return $this->application_id;
    }

    public function getAPIKey()
    {
        return $this->api_key;
    }

    public function getSearchOnlyAPIKey()
    {
        return $this->search_only_api_key;
    }

    protected function loadClasses()
    {
        $location = '/../libraries/algoliasearch-client-php/src/AlgoliaSearch/';

        require_once(dirname(__FILE__).$location.'AlgoliaException.php');
        require_once(dirname(__FILE__).$location.'Client.php');
        require_once(dirname(__FILE__).$location.'ClientContext.php');
        require_once(dirname(__FILE__).$location.'Index.php');
    }

}
