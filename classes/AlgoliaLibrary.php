<?php

class AlgoliaLibrary
{
	public $application_id = false;
	public $api_key = false;
	public $search_only_api_key = false;

	public $index_prefix = 'prestashop_';
	public $index_name = false;

	public function __construct()
	{
		$this->application_id = Configuration::get('ALGOLIA_APPLICATION_ID', false);
		$this->api_key = Configuration::get('ALGOLIA_API_KEY', false);
		$this->search_only_api_key = Configuration::get('ALGOLIA_SEARCH_ONLY_API_KEY', false);

        $shop_name = Configuration::get('PS_SHOP_NAME');
		$this->index_name = $this->index_prefix.self::slugify($shop_name);

		$this->loadClasses();
	}

	public function isConfigurationValid()
	{
		return $this->getApplicationID() && $this->getAPIKey() && $this->getSearchOnlyAPIKey();
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

	protected static function slugify($text)
	{
		$text = preg_replace('~[^\\pL\d]+~u', '-', $text);
		$text = trim($text, '-');
		$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
		$text = strtolower($text);
		$text = preg_replace('~[^-\w]+~', '', $text);

		if (empty($text) == true)
		return 'n-a';

		return $text;
	}

}
