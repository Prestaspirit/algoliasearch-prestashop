<?php
/*
 * Copyright (c) 2013 Algolia
 * http://www.algolia.com/
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 *
 * VERSION 1.1.9
 *
 */
namespace AlgoliaSearch;

use Exception;

class AlgoliaException extends \Exception
{
}

class ClientContext {

    public $applicationID;
    public $apiKey;
    public $readHostsArray;
    public $writeHostsArray;
    public $curlMHandle;
    public $adminAPIKey;
    public $connectTimeout;

    function __construct($applicationID, $apiKey, $hostsArray) {
        $this->connectTimeout = 2; // connect timeout of 2s by default
        $this->readTimeout = 30; // global timeout of 30s by default
        $this->searchTimeout = 5; // search timeout of 5s by default
        $this->applicationID = $applicationID;
        $this->apiKey = $apiKey;
        $this->readHostsArray = $hostsArray;
        $this->writeHostsArray = $hostsArray;
        if ($this->readHostsArray == null || count($this->readHostsArray) == 0) {
            $this->readHostsArray = array($applicationID . "-dsn.algolia.net", $applicationID . "-1.algolianet.com", $applicationID . "-2.algolianet.com", $applicationID . "-3.algolianet.com");
            $this->writeHostsArray = array($applicationID . ".algolia.net", $applicationID . "-1.algolianet.com", $applicationID . "-2.algolianet.com", $applicationID . "-3.algolianet.com");
        }
        if ($this->applicationID == null || mb_strlen($this->applicationID) == 0) {
            throw new Exception('AlgoliaSearch requires an applicationID.');
        }
        if ($this->apiKey == null || mb_strlen($this->apiKey) == 0) {
            throw new Exception('AlgoliaSearch requires an apiKey.');
        }

        $this->curlMHandle = NULL;
        $this->adminAPIKey = NULL;
        $this->endUserIP = NULL;
        $this->rateLimitAPIKey = NULL;
        $this->headers = array();
    }

    function __destruct() {
        if ($this->curlMHandle != null) {
            curl_multi_close($this->curlMHandle);
        }
    }

    public function getMHandle($curlHandle) {
        if ($this->curlMHandle == null) {
            $this->curlMHandle = curl_multi_init();
        }
        curl_multi_add_handle($this->curlMHandle, $curlHandle);

        return $this->curlMHandle;
    }

    public function releaseMHandle($curlHandle) {
        curl_multi_remove_handle($this->curlMHandle, $curlHandle);
    }

    public function setRateLimit($adminAPIKey, $endUserIP, $rateLimitAPIKey) {
        $this->adminAPIKey = $adminAPIKey;
        $this->endUserIP = $endUserIP;
        $this->rateLimitAPIKey = $rateLimitAPIKey;
    }

    public function disableRateLimit() {
        $this->adminAPIKey = NULL;
        $this->endUserIP = NULL;
        $this->rateLimitAPIKey = NULL;

    }

    public function setExtraHeader($key, $value) {
        $this->headers[$key] = $value;
    }
}

/**
 * Entry point in the PHP API.
 * You should instantiate a Client object with your ApplicationID, ApiKey and Hosts
 * to start using Algolia Search API
 */
class Client {

    protected $context;
    protected $cainfoPath;

    /*
     * Algolia Search initialization
     * @param applicationID the application ID you have in your admin interface
     * @param apiKey a valid API key for the service
     * @param hostsArray the list of hosts that you have received for the service
     */
    function __construct($applicationID, $apiKey, $hostsArray = null, $options = array()) {
        if ($hostsArray == null) {
            $this->context = new ClientContext($applicationID, $apiKey, null);
        } else {
            $this->context = new ClientContext($applicationID, $apiKey, $hostsArray);
        }
        if(!function_exists('curl_init')){
            throw new \Exception('AlgoliaSearch requires the CURL PHP extension.');
        }
        if(!function_exists('json_decode')){
            throw new \Exception('AlgoliaSearch requires the JSON PHP extension.');
        }
        $this->cainfoPath = __DIR__ . '/resources/ca-bundle.crt';

        foreach ($options as $option => $value) {
            if ($option == "cainfo") {
                $this->cainfoPath = $value;
            } else {
                throw new \Exception('Unknown option: ' . $option);
            }
        }
    }

    /*
     * Release curl handle
     */
    function __destruct() {
    }

    /*
     * Change the default connect timeout of 2s to a custom value (only useful if your server has a very slow connectivity to Algolia backend)
     * @param connectTimeout the connection timeout
     * @param timeout the read timeout for the query
     * @param searchTimeout the read timeout used for search queries only
     */
    public function setConnectTimeout($connectTimeout, $timeout = 30, $searchTimeout = 5) {
        $version = curl_version();
        if ((version_compare(phpversion(), '5.2.3', '<') || version_compare($version['version'], '7.16.2', '<')) && $this->context->connectTimeout < 1) {
            throw new AlgoliaException("The timeout can't be a float with a PHP version less than 5.2.3 or a curl version less than 7.16.2");
        }
        $this->context->connectTimeout = $connectTimeout;
        $this->context->readTimeout = $timeout;
        $this->context->searchTimeout = $searchTimeout;
    }

    /*
     * Allow to use IP rate limit when you have a proxy between end-user and Algolia.
     * This option will set the X-Forwarded-For HTTP header with the client IP and the X-Forwarded-API-Key with the API Key having rate limits.
     * @param adminAPIKey the admin API Key you can find in your dashboard
     * @param endUserIP the end user IP (you can use both IPV4 or IPV6 syntax)
     * @param rateLimitAPIKey the API key on which you have a rate limit
     */
    public function enableRateLimitForward($adminAPIKey, $endUserIP, $rateLimitAPIKey) {
        $this->context->setRateLimit($adminAPIKey, $endUserIP, $rateLimitAPIKey);
    }

    /*
     * Disable IP rate limit enabled with enableRateLimitForward() function
     */
    public function disableRateLimitForward() {
        $this->context->disableRateLimit();
    }

    /*
     * Call isAlive
     */
    public function isAlive() {
        $this->request($this->context, "GET", "/1/isalive", null, null, $this->context->readHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
    }

    /*
     * Allow to set custom headers
     */
    public function setExtraHeader($key, $value) {
        $this->context->setExtraHeader($key, $value);
    }

    /*
     * This method allows to query multiple indexes with one API call
     *
     */
    public function multipleQueries($queries, $indexNameKey = "indexName", $strategy = "none") {
        if ($queries == null) {
            throw new \Exception('No query provided');
        }
        $requests = array();
        foreach ($queries as $query) {
            if (array_key_exists($indexNameKey, $query)) {
                $indexes = $query[$indexNameKey];
                unset($query[$indexNameKey]);
            } else {
                throw new \Exception('indexName is mandatory');
            }
            foreach ($query as $key => $value) {
                if (gettype($value) == "array") {
                    $query[$key] = json_encode($value);
                }
            }
            $req = array("indexName" => $indexes, "params" => http_build_query($query));
            array_push($requests, $req);
        }
        return $this->request($this->context, "POST", "/1/indexes/*/queries?strategy=" . $strategy, array(), array("requests" => $requests), $this->context->readHostsArray, $this->context->connectTimeout, $this->context->searchTimeout);
    }

    /*
     * List all existing indexes
     * return an object in the form:
     * array("items" => array(
     *                        array("name" => "contacts", "createdAt" => "2013-01-18T15:33:13.556Z"),
     *                        array("name" => "notes", "createdAt" => "2013-01-18T15:33:13.556Z")
     *                        ))
     */
    public function listIndexes() {
        return $this->request($this->context, "GET", "/1/indexes/", null, null, $this->context->readHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
    }

    /*
     * Delete an index
     *
     * @param indexName the name of index to delete
     * return an object containing a "deletedAt" attribute
     */
    public function deleteIndex($indexName) {
        return $this->request($this->context, "DELETE", "/1/indexes/" . urlencode($indexName), null, null, $this->context->writeHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
    }

    /**
     * Move an existing index.
     * @param srcIndexName the name of index to copy.
     * @param dstIndexName the new index name that will contains a copy of srcIndexName (destination will be overriten if it already exist).
     */
    public function moveIndex($srcIndexName, $dstIndexName) {
        $request = array("operation" => "move", "destination" => $dstIndexName);
        return $this->request($this->context, "POST", "/1/indexes/" . urlencode($srcIndexName) . "/operation", array(), $request, $this->context->writeHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
    }

    /**
     * Copy an existing index.
     * @param srcIndexName the name of index to copy.
     * @param dstIndexName the new index name that will contains a copy of srcIndexName (destination will be overriten if it already exist).
     */
    public function copyIndex($srcIndexName, $dstIndexName) {
        $request = array("operation" => "copy", "destination" => $dstIndexName);
        return $this->request($this->context, "POST", "/1/indexes/" . urlencode($srcIndexName) . "/operation", array(), $request, $this->context->writeHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
    }

    /**
     * Return last logs entries.
     * @param offset Specify the first entry to retrieve (0-based, 0 is the most recent log entry).
     * @param length Specify the maximum number of entries to retrieve starting at offset. Maximum allowed value: 1000.
     */
    public function getLogs($offset = 0, $length = 10, $type = "all") {
        if (gettype($type) == "boolean") { //Old prototype onlyError
            if ($type) {
                $type = "error";
            } else {
                $type = "all";
            }
        }
        return $this->request($this->context, "GET", "/1/logs?offset=" . $offset . "&length=" . $length . "&type=" . $type, null, null, $this->context->writeHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
    }

    /*
     * Get the index object initialized (no server call needed for initialization)

     * @param indexName the name of index
     */
    public function initIndex($indexName) {
        if (empty($indexName)) {
            throw new AlgoliaException('Invalid index name: empty string');
        }
        return new Index($this->context, $this, $indexName);
    }

    /*
     * List all existing user keys with their associated ACLs
     *
     */
    public function listUserKeys() {
        return $this->request($this->context, "GET", "/1/keys", null, null, $this->context->readHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
    }

    /*
     * Get ACL of a user key
     *
     */
    public function getUserKeyACL($key) {
        return $this->request($this->context, "GET", "/1/keys/" . $key, null, null, $this->context->readHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
    }

    /*
     * Delete an existing user key
     *
     */
    public function deleteUserKey($key) {
        return $this->request($this->context, "DELETE", "/1/keys/" . $key, null, null, $this->context->writeHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
    }

    /*
     * Create a new user key
     *
     * @param obj can be two different parameters:
     * The list of parameters for this key. Defined by a NSDictionary that
     * can contains the following values:
     *   - acl: array of string
     *   - indices: array of string
     *   - validity: int
     *   - referers: array of string
     *   - description: string
     *   - maxHitsPerQuery: integer
     *   - queryParameters: string
     *   - maxQueriesPerIPPerHour: integer
     * Or the list of ACL for this key. Defined by an array of NSString that
     * can contains the following values:
     *   - search: allow to search (https and http)
     *   - addObject: allows to add/update an object in the index (https only)
     *   - deleteObject : allows to delete an existing object (https only)
     *   - deleteIndex : allows to delete index content (https only)
     *   - settings : allows to get index settings (https only)
     *   - editSettings : allows to change index settings (https only)
     * @param validity the number of seconds after which the key will be automatically removed (0 means no time limit for this key)
     * @param maxQueriesPerIPPerHour Specify the maximum number of API calls allowed from an IP address per hour.  Defaults to 0 (no rate limit).
     * @param maxHitsPerQuery Specify the maximum number of hits this API key can retrieve in one call. Defaults to 0 (unlimited)
     * @param indexes Specify the list of indices to target (null means all)
     */
    public function addUserKey($obj, $validity = 0, $maxQueriesPerIPPerHour = 0, $maxHitsPerQuery = 0, $indexes = null) {
        if ($obj !== array_values($obj)) { // is dict of value
            $params = $obj;
            $params["validity"] = $validity;
            $params["maxQueriesPerIPPerHour"] = $maxQueriesPerIPPerHour;
            $params["maxHitsPerQuery"] = $maxHitsPerQuery;
        } else {
            $params = array(
                "acl" => $obj,
                "validity" => $validity,
                "maxQueriesPerIPPerHour" => $maxQueriesPerIPPerHour,
                "maxHitsPerQuery" => $maxHitsPerQuery
            );
        }

        if ($indexes != null) {
            $params['indexes'] = $indexes;
        }
        return $this->request($this->context, "POST", "/1/keys", array(), $params, $this->context->writeHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
    }

    /*
     * Update a user key
     *
     * @param obj can be two different parameters:
     * The list of parameters for this key. Defined by a NSDictionary that
     * can contains the following values:
     *   - acl: array of string
     *   - indices: array of string
     *   - validity: int
     *   - referers: array of string
     *   - description: string
     *   - maxHitsPerQuery: integer
     *   - queryParameters: string
     *   - maxQueriesPerIPPerHour: integer
     * Or the list of ACL for this key. Defined by an array of NSString that
     * can contains the following values:
     *   - search: allow to search (https and http)
     *   - addObject: allows to add/update an object in the index (https only)
     *   - deleteObject : allows to delete an existing object (https only)
     *   - deleteIndex : allows to delete index content (https only)
     *   - settings : allows to get index settings (https only)
     *   - editSettings : allows to change index settings (https only)
     * @param validity the number of seconds after which the key will be automatically removed (0 means no time limit for this key)
     * @param maxQueriesPerIPPerHour Specify the maximum number of API calls allowed from an IP address per hour.  Defaults to 0 (no rate limit).
     * @param maxHitsPerQuery Specify the maximum number of hits this API key can retrieve in one call. Defaults to 0 (unlimited)
     * @param indexes Specify the list of indices to target (null means all)
     */
    public function updateUserKey($key, $obj, $validity = 0, $maxQueriesPerIPPerHour = 0, $maxHitsPerQuery = 0, $indexes = null) {
        if ($obj !== array_values($obj)) { // is dict of value
            $params = $obj;
            $params["validity"] = $validity;
            $params["maxQueriesPerIPPerHour"] = $maxQueriesPerIPPerHour;
            $params["maxHitsPerQuery"] = $maxHitsPerQuery;
        } else {
            $params = array(
                "acl" => $obj,
                "validity" => $validity,
                "maxQueriesPerIPPerHour" => $maxQueriesPerIPPerHour,
                "maxHitsPerQuery" => $maxHitsPerQuery
            );
        }
        if ($indexes != null) {
            $params['indexes'] = $indexes;
        }
        return $this->request($this->context, "PUT", "/1/keys/" . $key, array(), $params, $this->context->writeHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
    }

    /**
     * Send a batch request targeting multiple indices
     * @param  $requests an associative array defining the batch request body
     */
    public function batch($requests) {
        return $this->request($this->context, "POST", "/1/indexes/*/batch", array(), array("requests" => $requests),
            $this->context->writeHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
    }

    /*
     * Generate a secured and public API Key from a list of tagFilters and an
     * optional user token identifying the current user
     *
     * @param privateApiKey your private API Key
     * @param tagFilters the list of tags applied to the query (used as security)
     * @param userToken an optional token identifying the current user
     *
     */
    public function generateSecuredApiKey($privateApiKey, $tagFilters, $userToken = null) {
        if (is_array($tagFilters)) {
            $tmp = array();
            foreach ($tagFilters as $tag) {
                if (is_array($tag)) {
                    $tmp2 = array();
                    foreach ($tag as $tag2) {
                        array_push($tmp2, $tag2);
                    }
                    array_push($tmp, '(' . join(',', $tmp2) . ')');
                } else {
                    array_push($tmp, $tag);
                }
            }
            $tagFilters = join(',', $tmp);
        }
        return hash_hmac('sha256', $tagFilters . $userToken, $privateApiKey);
    }

    public function request($context, $method, $path, $params = array(), $data = array(), $hostsArray, $connectTimeout, $readTimeout) {
        $exceptions = array();
        $cnt = 0;
        foreach ($hostsArray as &$host) {
            $cnt += 1;
            if ($cnt == 3) {
                $connectTimeout += 2;
                $readTimeout += 10;
            }
            try {
                $res = $this->doRequest($context, $method, $host, $path, $params, $data, $connectTimeout, $readTimeout);
                if ($res !== null)
                    return $res;
            } catch (AlgoliaException $e) {
                throw $e;
            } catch (\Exception $e) {
                $exceptions[$host] = $e->getMessage();
            }
        }
        throw new AlgoliaException('Hosts unreachable: ' . join(",", $exceptions));
    }

    public function doRequest($context, $method, $host, $path, $params, $data, $connectTimeout, $readTimeout) {
        if (strpos($host, "http") === 0) {
            $url = $host . $path;
        } else {
            $url = "https://" . $host . $path;
        }
        if ($params != null && count($params) > 0) {
            $params2 = array();
            foreach ($params as $key => $val) {
                if (is_array($val)) {
                    $params2[$key] = json_encode($val);
                } else {
                    $params2[$key] = $val;
                }
            }
            $url .= "?" . http_build_query($params2);

        }
        // initialize curl library
        $curlHandle = curl_init();
        //curl_setopt($curlHandle, CURLOPT_VERBOSE, true);
        if ($context->adminAPIKey == null) {
            curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array_merge(array(
                'X-Algolia-Application-Id: ' . $context->applicationID,
                'X-Algolia-API-Key: ' . $context->apiKey,
                'Content-type: application/json'
            ), $context->headers));
        } else {
            curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array_merge(array(
                'X-Algolia-Application-Id: ' . $context->applicationID,
                'X-Algolia-API-Key: ' . $context->adminAPIKey,
                'X-Forwarded-For: ' . $context->endUserIP,
                'X-Forwarded-API-Key: ' . $context->rateLimitAPIKey,
                'Content-type: application/json'
            ), $context->headers));
        }
        curl_setopt($curlHandle, CURLOPT_USERAGENT, "Algolia for PHP " . Version::get());
        //Return the output instead of printing it
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_FAILONERROR, true);
        curl_setopt($curlHandle, CURLOPT_ENCODING, '');
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curlHandle, CURLOPT_CAINFO, $this->cainfoPath);

        curl_setopt($curlHandle, CURLOPT_URL, $url);
        $version = curl_version();
        if (version_compare(phpversion(), '5.2.3', '>=') && version_compare($version['version'], '7.16.2', '>=') && $connectTimeout < 1) {
            curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT_MS, $connectTimeout * 1000);
            curl_setopt($curlHandle, CURLOPT_TIMEOUT_MS, $readTimeout * 1000);
        } else {
            curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
            curl_setopt($curlHandle, CURLOPT_TIMEOUT, $readTimeout);
        }

        curl_setopt($curlHandle, CURLOPT_NOSIGNAL, 1); # The problem is that on (Li|U)nix, when libcurl uses the standard name resolver, a SIGALRM is raised during name resolution which libcurl thinks is the timeout alarm.
        curl_setopt($curlHandle, CURLOPT_FAILONERROR, false);

        if ($method === 'GET') {
            curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($curlHandle, CURLOPT_HTTPGET, true);
            curl_setopt($curlHandle, CURLOPT_POST, false);
        } else if ($method === 'POST') {
            $body = ($data) ? json_encode($data) : '';
            curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curlHandle, CURLOPT_POST, true);
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $body);
        } elseif ($method === 'DELETE') {
            curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($curlHandle, CURLOPT_POST, false);
        } elseif ($method === 'PUT') {
            $body = ($data) ? json_encode($data) : '';
            curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $body);
            curl_setopt($curlHandle, CURLOPT_POST, true);
        }
        $mhandle = $context->getMHandle($curlHandle);

        // Do all the processing.
        $running = null;
        do {
            $mrc = curl_multi_exec($mhandle, $running);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        while ($running && $mrc == CURLM_OK) {
            if (curl_multi_select($mhandle, 0.1) == -1) {
                usleep(100);
            }
            do {
                $mrc = curl_multi_exec($mhandle, $running);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }
        $http_status = (int)curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        $response = curl_multi_getcontent($curlHandle);
        $error = curl_error($curlHandle);
        if (!empty($error)) {
            throw new \Exception($error);
        }
        if ($http_status === 0 || $http_status === 503) {
            // Could not reach host or service unavailable, try with another one if we have it
            $context->releaseMHandle($curlHandle);
            curl_close($curlHandle);
            return null;
        }

        $answer = json_decode($response, true);
        $context->releaseMHandle($curlHandle);
        curl_close($curlHandle);

        if (intval($http_status / 100) == 4) {
            throw new AlgoliaException(isset($answer['message']) ? $answer['message'] : $http_status + " error");
        }
        elseif (intval($http_status / 100) != 2) {
            throw new \Exception($http_status . ": " . $response);
        }

        switch (json_last_error()) {
            case JSON_ERROR_DEPTH:
                $errorMsg = 'JSON parsing error: maximum stack depth exceeded';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $errorMsg = 'JSON parsing error: unexpected control character found';
                break;
            case JSON_ERROR_SYNTAX:
                $errorMsg = 'JSON parsing error: syntax error, malformed JSON';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $errorMsg = 'JSON parsing error: underflow or the modes mismatch';
                break;
            case (defined('JSON_ERROR_UTF8') ? JSON_ERROR_UTF8 : -1): // PHP 5.3 less than 1.2.2 (Ubuntu 10.04 LTS)
                $errorMsg = 'JSON parsing error: malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            case JSON_ERROR_NONE:
            default:
                $errorMsg = null;
                break;
        }
        if ($errorMsg !== null)
            throw new AlgoliaException($errorMsg);

        return $answer;
    }
}


/*
 * Contains all the functions related to one index
 * You should use Client.initIndex(indexName) to retrieve this object
 */
class Index {

    public $indexName;
    private $client;
    private $urlIndexName;

    /*
     * Index initialization (You should not call this initialized yourself)
     */
    public function __construct($context, $client, $indexName) {
        $this->context = $context;
        $this->client = $client;
        $this->indexName = $indexName;
        $this->urlIndexName = urlencode($indexName);
    }

    /*
     * Perform batch operation on several objects
     *
     * @param objects contains an array of objects to update (each object must contains an objectID attribute)
     * @param objectIDKey  the key in each object that contains the objectID
     * @param objectActionKey  the key in each object that contains the action to perform (addObject, updateObject, deleteObject or partialUpdateObject)
     */
    public function batchObjects($objects, $objectIDKey = "objectID", $objectActionKey = "objectAction") {
        $requests = array();

        foreach ($objects as $obj) {
            // If no or invalid action, assume updateObject
            if (! isset($obj[$objectActionKey]) || ! in_array($obj[$objectActionKey], array('addObject', 'updateObject', 'deleteObject', 'partialUpdateObject', 'partialUpdateObjectNoCreate'))) {
                throw new \Exception('invalid or no action detected');
            }

            $action = $obj[$objectActionKey];
            unset($obj[$objectActionKey]); // The action key is not included in the object

            $req = array("action" => $action, "body" => $obj);

            if (array_key_exists($objectIDKey, $obj)) {
                $req["objectID"] = (string) $obj[$objectIDKey];
            }

            $requests[] = $req;
        }

        return $this->batch(array("requests" => $requests));
    }

    /*
     * Add an object in this index
     *
     * @param content contains the object to add inside the index.
     *  The object is represented by an associative array
     * @param objectID (optional) an objectID you want to attribute to this object
     * (if the attribute already exist the old object will be overwrite)
     */
    public function addObject($content, $objectID = null) {

        if ($objectID === null) {
            return $this->client->request($this->context, "POST", "/1/indexes/" . $this->urlIndexName, array(), $content, $this->context->writeHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
        } else {
            return $this->client->request($this->context, "PUT", "/1/indexes/" . $this->urlIndexName . "/" . urlencode($objectID), array(), $content, $this->context->writeHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
        }
    }

    /*
     * Add several objects
     *
     * @param objects contains an array of objects to add. If the object contains an objectID
     */
    public function addObjects($objects, $objectIDKey = "objectID") {
        $requests = $this->buildBatch("addObject", $objects, true, $objectIDKey);
        return $this->batch($requests);
    }

    /*
     * Get an object from this index
     *
     * @param objectID the unique identifier of the object to retrieve
     * @param attributesToRetrieve (optional) if set, contains the list of attributes to retrieve as a string separated by ","
     */
    public function getObject($objectID, $attributesToRetrieve = null) {
        $id = urlencode($objectID);
        if ($attributesToRetrieve === null)
            return $this->client->request($this->context, "GET", "/1/indexes/" . $this->urlIndexName . "/" . $id, null, null, $this->context->readHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
        else
            return $this->client->request($this->context, "GET", "/1/indexes/" . $this->urlIndexName . "/" . $id, array("attributes" => $attributesToRetrieve), null, $this->context->readHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
    }

    /*
     * Get several objects from this index
     *
     * @param objectIDs the array of unique identifier of objects to retrieve
     */
    public function getObjects($objectIDs) {
        if ($objectIDs == null) {
            throw new \Exception('No list of objectID provided');
        }
        $requests = array();
        foreach ($objectIDs as $object) {
            $req = array("indexName" => $this->indexName, "objectID" => $object);
            array_push($requests, $req);
        }
        return $this->client->request($this->context, "POST", "/1/indexes/*/objects", array(), array("requests" => $requests), $this->context->readHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
    }

    /*
     * Update partially an object (only update attributes passed in argument)
     *
     * @param partialObject contains the object attributes to override, the
     *  object must contains an objectID attribute
     */
    public function partialUpdateObject($partialObject, $createIfNotExists = true) {
        return $this->client->request($this->context, "POST", "/1/indexes/" . $this->urlIndexName . "/" . urlencode($partialObject["objectID"]) . "/partial" . ($createIfNotExists ? "" : "?createIfNotExists=false"), array(), $partialObject, $this->context->writeHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
    }

    /*
     * Partially Override the content of several objects
     *
     * @param objects contains an array of objects to update (each object must contains a objectID attribute)
     */
    public function partialUpdateObjects($objects, $objectIDKey = "objectID", $createIfNotExists = true) {
        if ($createIfNotExists) {
            $requests = $this->buildBatch("partialUpdateObject", $objects, true, $objectIDKey);
        } else {
            $requests = $this->buildBatch("partialUpdateObjectNoCreate", $objects, true, $objectIDKey);
        }
        return $this->batch($requests);
    }

    /*
     * Override the content of object
     *
     * @param object contains the object to save, the object must contains an objectID attribute
     */
    public function saveObject($object) {
        return $this->client->request($this->context, "PUT", "/1/indexes/" . $this->urlIndexName . "/" . urlencode($object["objectID"]), array(), $object, $this->context->writeHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
    }

    /*
     * Override the content of several objects
     *
     * @param objects contains an array of objects to update (each object must contains a objectID attribute)
     */
    public function saveObjects($objects, $objectIDKey = "objectID") {
        $requests = $this->buildBatch("updateObject", $objects, true, $objectIDKey);
        return $this->batch($requests);
    }

    /*
     * Delete an object from the index
     *
     * @param objectID the unique identifier of object to delete
     */
    public function deleteObject($objectID) {
        if ($objectID == null || mb_strlen($objectID) == 0) {
            throw new \Exception('objectID is mandatory');
        }
        return $this->client->request($this->context, "DELETE", "/1/indexes/" . $this->urlIndexName . "/" . urlencode($objectID), null, null, $this->context->writeHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
    }

    /*
     * Delete several objects
     *
     * @param objects contains an array of objectIDs to delete. If the object contains an objectID
     */
    public function deleteObjects($objects) {
        $objectIDs = array();
        foreach ($objects as $key => $id) {
            $objectIDs[$key] = array('objectID' => $id);
        }
        $requests = $this->buildBatch("deleteObject", $objectIDs, true);
        return $this->batch($requests);
    }

    /*
     * Delete all objects matching a query
     *
     * @param query the query string
     * @param params the optional query parameters
     */
    public function deleteByQuery($query, $args = array()) {
        $params["attributeToRetrieve"] = array('objectID');
        $params["hitsPerPage"] = 1000;
        $results = $this->search($query, $args);
        while ($results['nbHits'] != 0) {
            $objectIDs = array();
            foreach ($results['hits'] as $elt) {
                array_push($objectIDs, $elt['objectID']);
            }
            $res = $this->deleteObjects($objectIDs);
            $this->waitTask($res['taskID']);
            $results = $this->search($query, $args);
        }
    }

    /*
     * Search inside the index
     *
     * @param query the full text query
     * @param args (optional) if set, contains an associative array with query parameters:
     * - page: (integer) Pagination parameter used to select the page to retrieve.
     *                   Page is zero-based and defaults to 0. Thus, to retrieve the 10th page you need to set page=9
     * - hitsPerPage: (integer) Pagination parameter used to select the number of hits per page. Defaults to 20.
     * - attributesToRetrieve: a string that contains the list of object attributes you want to retrieve (let you minimize the answer size).
     *   Attributes are separated with a comma (for example "name,address").
     *   You can also use a string array encoding (for example ["name","address"]).
     *   By default, all attributes are retrieved. You can also use '*' to retrieve all values when an attributesToRetrieve setting is specified for your index.
     * - attributesToHighlight: a string that contains the list of attributes you want to highlight according to the query.
     *   Attributes are separated by a comma. You can also use a string array encoding (for example ["name","address"]).
     *   If an attribute has no match for the query, the raw value is returned. By default all indexed text attributes are highlighted.
     *   You can use `*` if you want to highlight all textual attributes. Numerical attributes are not highlighted.
     *   A matchLevel is returned for each highlighted attribute and can contain:
     *      - full: if all the query terms were found in the attribute,
     *      - partial: if only some of the query terms were found,
     *      - none: if none of the query terms were found.
     * - attributesToSnippet: a string that contains the list of attributes to snippet alongside the number of words to return (syntax is `attributeName:nbWords`).
     *    Attributes are separated by a comma (Example: attributesToSnippet=name:10,content:10).
     *    You can also use a string array encoding (Example: attributesToSnippet: ["name:10","content:10"]). By default no snippet is computed.
     * - minWordSizefor1Typo: the minimum number of characters in a query word to accept one typo in this word. Defaults to 3.
     * - minWordSizefor2Typos: the minimum number of characters in a query word to accept two typos in this word. Defaults to 7.
     * - getRankingInfo: if set to 1, the result hits will contain ranking information in _rankingInfo attribute.
     * - aroundLatLng: search for entries around a given latitude/longitude (specified as two floats separated by a comma).
     *   For example aroundLatLng=47.316669,5.016670).
     *   You can specify the maximum distance in meters with the aroundRadius parameter (in meters) and the precision for ranking with aroundPrecision
     *   (for example if you set aroundPrecision=100, two objects that are distant of less than 100m will be considered as identical for "geo" ranking parameter).
     *   At indexing, you should specify geoloc of an object with the _geoloc attribute (in the form {"_geoloc":{"lat":48.853409, "lng":2.348800}})
     * - insideBoundingBox: search entries inside a given area defined by the two extreme points of a rectangle (defined by 4 floats: p1Lat,p1Lng,p2Lat,p2Lng).
     *   For example insideBoundingBox=47.3165,4.9665,47.3424,5.0201).
     *   At indexing, you should specify geoloc of an object with the _geoloc attribute (in the form {"_geoloc":{"lat":48.853409, "lng":2.348800}})
     * - numericFilters: a string that contains the list of numeric filters you want to apply separated by a comma.
     *   The syntax of one filter is `attributeName` followed by `operand` followed by `value`. Supported operands are `<`, `<=`, `=`, `>` and `>=`.
     *   You can have multiple conditions on one attribute like for example numericFilters=price>100,price<1000.
     *   You can also use a string array encoding (for example numericFilters: ["price>100","price<1000"]).
     * - tagFilters: filter the query by a set of tags. You can AND tags by separating them by commas.
     *   To OR tags, you must add parentheses. For example, tags=tag1,(tag2,tag3) means tag1 AND (tag2 OR tag3).
     *   You can also use a string array encoding, for example tagFilters: ["tag1",["tag2","tag3"]] means tag1 AND (tag2 OR tag3).
     *   At indexing, tags should be added in the _tags** attribute of objects (for example {"_tags":["tag1","tag2"]}).
     * - facetFilters: filter the query by a list of facets.
     *   Facets are separated by commas and each facet is encoded as `attributeName:value`.
     *   For example: `facetFilters=category:Book,author:John%20Doe`.
     *   You can also use a string array encoding (for example `["category:Book","author:John%20Doe"]`).
     * - facets: List of object attributes that you want to use for faceting.
     *   Attributes are separated with a comma (for example `"category,author"` ).
     *   You can also use a JSON string array encoding (for example ["category","author"]).
     *   Only attributes that have been added in **attributesForFaceting** index setting can be used in this parameter.
     *   You can also use `*` to perform faceting on all attributes specified in **attributesForFaceting**.
     * - queryType: select how the query words are interpreted, it can be one of the following value:
     *    - prefixAll: all query words are interpreted as prefixes,
     *    - prefixLast: only the last word is interpreted as a prefix (default behavior),
     *    - prefixNone: no query word is interpreted as a prefix. This option is not recommended.
     * - optionalWords: a string that contains the list of words that should be considered as optional when found in the query.
     *   The list of words is comma separated.
     * - distinct: If set to 1, enable the distinct feature (disabled by default) if the attributeForDistinct index setting is set.
     *   This feature is similar to the SQL "distinct" keyword: when enabled in a query with the distinct=1 parameter,
     *   all hits containing a duplicate value for the attributeForDistinct attribute are removed from results.
     *   For example, if the chosen attribute is show_name and several hits have the same value for show_name, then only the best
     *   one is kept and others are removed.
     */
    public function search($query, $args = null) {
        if ($args === null) {
            $args = array();
        }
        $args["query"] = $query;
        return $this->client->request($this->context, "GET", "/1/indexes/" . $this->urlIndexName, $args, null, $this->context->readHostsArray, $this->context->connectTimeout, $this->context->searchTimeout);
    }

    /*
     * Perform a search with disjunctive facets generating as many queries as number of disjunctive facets
     *
     * @param query the query
     * @param disjunctive_facets the array of disjunctive facets
     * @param params a hash representing the regular query parameters
     * @param refinements a hash ("string" -> ["array", "of", "refined", "values"]) representing the current refinements
     * ex: { "my_facet1" => ["my_value1", ["my_value2"], "my_disjunctive_facet1" => ["my_value1", "my_value2"] }
     */
    public function searchDisjunctiveFaceting($query, $disjunctive_facets, $params = array(), $refinements = array()) {
        if (gettype($disjunctive_facets) != "string" && gettype($disjunctive_facets) != "array") {
            throw new AlgoliaException("Argument \"disjunctive_facets\" must be a String or an Array");
        }
        if (gettype($refinements) != "array") {
            throw new AlgoliaException("Argument \"refinements\" must be a Hash of Arrays");
        }

        if (gettype($disjunctive_facets) == "string") {
            $disjunctive_facets = split(",", $disjunctive_facets);
        }

        $disjunctive_refinements = array();
        foreach ($refinements as $key => $value) {
            if (in_array($key, $disjunctive_facets)) {
                $disjunctive_refinements[$key] = $value;
            }
        }
        $queries = array();
        $filters = array();

        foreach ($refinements as $key => $value) {
            $r = array_map(function ($val) use ($key) { return $key . ":" . $val;}, $value);

            if (in_array($key, $disjunctive_refinements)) {
                $filter = array_merge($filters, $r);
            } else {
                array_push($filters, $r);
            }
        }
        $params["indexName"] = $this->indexName;
        $params["query"] = $query;
        $params["facetFilters"] = $filters;
        array_push($queries, $params);
        foreach ($disjunctive_facets as $disjunctive_facet) {
            $filters = array();
            foreach ($refinements as $key => $value) {
                if ($key != $disjunctive_facet) {
                    $r = array_map(function($val) use($key) { return $key . ":" . $val;}, $value);

                    if (in_array($key, $disjunctive_refinements)) {
                        $filter = array_merge($filters, $r);
                    } else {
                        array_push($filters, $r);
                    }
                }
            }
            $params["indexName"] = $this->indexName;
            $params["query"] = $query;
            $params["facetFilters"] = $filters;
            $params["page"] = 0;
            $params["hitsPerPage"] = 0;
            $params["attributesToRetrieve"] = array();
            $params["attributesToHighlight"] = array();
            $params["attributesToSnippet"] = array();
            $params["facets"] = $disjunctive_facet;
            $params["analytics"] = false;
            array_push($queries, $params);
        }
        $answers = $this->client->multipleQueries($queries);

        $aggregated_answer = $answers['results'][0];
        $aggregated_answer['disjunctiveFacets'] = array();
        for ($i = 1; $i < count($answers['results']); $i++) {
            foreach ($answers['results'][$i]['facets'] as $key => $facet) {
                $aggregated_answer['disjunctiveFacets'][$key] = $facet;
                if (!in_array($key, $disjunctive_refinements)) {
                    continue;
                }
                foreach ($disjunctive_refinements[$key] as $r) {
                    if (is_null($aggregated_answer['disjunctiveFacets'][$key][$r])) {
                        $aggregated_answer['disjunctiveFacets'][$key][$r] = 0;
                    }
                }
            }
        }
        return $aggregated_answer;
    }

    /*
     * Browse all index content
     *
     * @param page Pagination parameter used to select the page to retrieve.
     *             Page is zero-based and defaults to 0. Thus, to retrieve the 10th page you need to set page=9
     * @param hitsPerPage: Pagination parameter used to select the number of hits per page. Defaults to 1000.
     */
    public function browse($page = 0, $hitsPerPage = 1000) {
        return $this->client->request($this->context, "GET", "/1/indexes/" . $this->urlIndexName . "/browse",
            array("page" => $page, "hitsPerPage" => $hitsPerPage), null, $this->context->readHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
    }

    /*
     * Wait the publication of a task on the server.
     * All server task are asynchronous and you can check with this method that the task is published.
     *
     * @param taskID the id of the task returned by server
     * @param timeBeforeRetry the time in milliseconds before retry (default = 100ms)
     */
    public function waitTask($taskID, $timeBeforeRetry = 100) {
        while (true) {
            $res = $this->client->request($this->context, "GET", "/1/indexes/" . $this->urlIndexName . "/task/" . $taskID, null, null, $this->context->readHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
            if ($res["status"] === "published")
                return $res;
            usleep($timeBeforeRetry * 1000);
        }
    }

    /*
     * Get settings of this index
     *
     */
    public function getSettings() {
        return $this->client->request($this->context, "GET", "/1/indexes/" . $this->urlIndexName . "/settings", null, null, $this->context->readHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
    }

    /*
     * This function deletes the index content. Settings and index specific API keys are kept untouched.
     */
    public function clearIndex() {
        return $this->client->request($this->context, "POST", "/1/indexes/" . $this->urlIndexName . "/clear", null, null, $this->context->writeHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
    }

    /*
     * Set settings for this index
     *
     * @param settigns the settings object that can contains :
     * - minWordSizefor1Typo: (integer) the minimum number of characters to accept one typo (default = 3).
     * - minWordSizefor2Typos: (integer) the minimum number of characters to accept two typos (default = 7).
     * - hitsPerPage: (integer) the number of hits per page (default = 10).
     * - attributesToRetrieve: (array of strings) default list of attributes to retrieve in objects.
     *   If set to null, all attributes are retrieved.
     * - attributesToHighlight: (array of strings) default list of attributes to highlight.
     *   If set to null, all indexed attributes are highlighted.
     * - attributesToSnippet**: (array of strings) default list of attributes to snippet alongside the number of words to return (syntax is attributeName:nbWords).
     *   By default no snippet is computed. If set to null, no snippet is computed.
     * - attributesToIndex: (array of strings) the list of fields you want to index.
     *   If set to null, all textual and numerical attributes of your objects are indexed, but you should update it to get optimal results.
     *   This parameter has two important uses:
     *     - Limit the attributes to index: For example if you store a binary image in base64, you want to store it and be able to
     *       retrieve it but you don't want to search in the base64 string.
     *     - Control part of the ranking*: (see the ranking parameter for full explanation) Matches in attributes at the beginning of
     *       the list will be considered more important than matches in attributes further down the list.
     *       In one attribute, matching text at the beginning of the attribute will be considered more important than text after, you can disable
     *       this behavior if you add your attribute inside `unordered(AttributeName)`, for example attributesToIndex: ["title", "unordered(text)"].
     * - attributesForFaceting: (array of strings) The list of fields you want to use for faceting.
     *   All strings in the attribute selected for faceting are extracted and added as a facet. If set to null, no attribute is used for faceting.
     * - attributeForDistinct: (string) The attribute name used for the Distinct feature. This feature is similar to the SQL "distinct" keyword: when enabled
     *   in query with the distinct=1 parameter, all hits containing a duplicate value for this attribute are removed from results.
     *   For example, if the chosen attribute is show_name and several hits have the same value for show_name, then only the best one is kept and others are removed.
     * - ranking: (array of strings) controls the way results are sorted.
     *   We have six available criteria:
     *    - typo: sort according to number of typos,
     *    - geo: sort according to decreassing distance when performing a geo-location based search,
     *    - proximity: sort according to the proximity of query words in hits,
     *    - attribute: sort according to the order of attributes defined by attributesToIndex,
     *    - exact:
     *        - if the user query contains one word: sort objects having an attribute that is exactly the query word before others.
     *          For example if you search for the "V" TV show, you want to find it with the "V" query and avoid to have all popular TV
     *          show starting by the v letter before it.
     *        - if the user query contains multiple words: sort according to the number of words that matched exactly (and not as a prefix).
     *    - custom: sort according to a user defined formula set in **customRanking** attribute.
     *   The standard order is ["typo", "geo", "proximity", "attribute", "exact", "custom"]
     * - customRanking: (array of strings) lets you specify part of the ranking.
     *   The syntax of this condition is an array of strings containing attributes prefixed by asc (ascending order) or desc (descending order) operator.
     *   For example `"customRanking" => ["desc(population)", "asc(name)"]`
     * - queryType: Select how the query words are interpreted, it can be one of the following value:
     *   - prefixAll: all query words are interpreted as prefixes,
     *   - prefixLast: only the last word is interpreted as a prefix (default behavior),
     *   - prefixNone: no query word is interpreted as a prefix. This option is not recommended.
     * - highlightPreTag: (string) Specify the string that is inserted before the highlighted parts in the query result (default to "<em>").
     * - highlightPostTag: (string) Specify the string that is inserted after the highlighted parts in the query result (default to "</em>").
     * - optionalWords: (array of strings) Specify a list of words that should be considered as optional when found in the query.
     */
    public function setSettings($settings) {
        return $this->client->request($this->context, "PUT", "/1/indexes/" . $this->urlIndexName . "/settings", array(), $settings, $this->context->writeHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
    }

    /*
     * List all existing user keys associated to this index with their associated ACLs
     *
     */
    public function listUserKeys() {
        return $this->client->request($this->context, "GET", "/1/indexes/" . $this->urlIndexName . "/keys", null, null, $this->context->readHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
    }

    /*
     * Get ACL of a user key associated to this index
     *
     */
    public function getUserKeyACL($key) {
        return $this->client->request($this->context, "GET", "/1/indexes/" . $this->urlIndexName . "/keys/" . $key, null, null, $this->context->readHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
    }

    /*
     * Delete an existing user key associated to this index
     *
     */
    public function deleteUserKey($key) {
        return $this->client->request($this->context, "DELETE", "/1/indexes/" . $this->urlIndexName . "/keys/" . $key, null, null, $this->context->writeHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
    }

    /*
     * Create a new user key associated to this index
     *
     * @param obj can be two different parameters:
     * The list of parameters for this key. Defined by a NSDictionary that
     * can contains the following values:
     *   - acl: array of string
     *   - indices: array of string
     *   - validity: int
     *   - referers: array of string
     *   - description: string
     *   - maxHitsPerQuery: integer
     *   - queryParameters: string
     *   - maxQueriesPerIPPerHour: integer
     * Or the list of ACL for this key. Defined by an array of NSString that
     * can contains the following values:
     *   - search: allow to search (https and http)
     *   - addObject: allows to add/update an object in the index (https only)
     *   - deleteObject : allows to delete an existing object (https only)
     *   - deleteIndex : allows to delete index content (https only)
     *   - settings : allows to get index settings (https only)
     *   - editSettings : allows to change index settings (https only)
     * @param validity the number of seconds after which the key will be automatically removed (0 means no time limit for this key)
     * @param maxQueriesPerIPPerHour Specify the maximum number of API calls allowed from an IP address per hour.  Defaults to 0 (no rate limit).
     * @param maxHitsPerQuery Specify the maximum number of hits this API key can retrieve in one call. Defaults to 0 (unlimited)
     */
    public function addUserKey($obj, $validity = 0, $maxQueriesPerIPPerHour = 0, $maxHitsPerQuery = 0) {
        if ($obj !== array_values($obj)) { // is dict of value
            $params = $obj;
            $params["validity"] = $validity;
            $params["maxQueriesPerIPPerHour"] = $maxQueriesPerIPPerHour;
            $params["maxHitsPerQuery"] = $maxHitsPerQuery;
        } else {
            $params = array(
                "acl" => $obj,
                "validity" => $validity,
                "maxQueriesPerIPPerHour" => $maxQueriesPerIPPerHour,
                "maxHitsPerQuery" => $maxHitsPerQuery
            );
        }
        return $this->client->request($this->context, "POST", "/1/indexes/" . $this->urlIndexName . "/keys", array(), $params,
            $this->context->writeHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
    }

    /*
     * Update a user key associated to this index
     *
     * @param obj can be two different parameters:
     * The list of parameters for this key. Defined by a NSDictionary that
     * can contains the following values:
     *   - acl: array of string
     *   - indices: array of string
     *   - validity: int
     *   - referers: array of string
     *   - description: string
     *   - maxHitsPerQuery: integer
     *   - queryParameters: string
     *   - maxQueriesPerIPPerHour: integer
     * Or the list of ACL for this key. Defined by an array of NSString that
     * can contains the following values:
     *   - search: allow to search (https and http)
     *   - addObject: allows to add/update an object in the index (https only)
     *   - deleteObject : allows to delete an existing object (https only)
     *   - deleteIndex : allows to delete index content (https only)
     *   - settings : allows to get index settings (https only)
     *   - editSettings : allows to change index settings (https only)
     * @param validity the number of seconds after which the key will be automatically removed (0 means no time limit for this key)
     * @param maxQueriesPerIPPerHour Specify the maximum number of API calls allowed from an IP address per hour.  Defaults to 0 (no rate limit).
     * @param maxHitsPerQuery Specify the maximum number of hits this API key can retrieve in one call. Defaults to 0 (unlimited)
     */
    public function updateUserKey($key, $obj, $validity = 0, $maxQueriesPerIPPerHour = 0, $maxHitsPerQuery = 0) {
        if ($obj !== array_values($obj)) { // is dict of value
            $params = $obj;
            $params["validity"] = $validity;
            $params["maxQueriesPerIPPerHour"] = $maxQueriesPerIPPerHour;
            $params["maxHitsPerQuery"] = $maxHitsPerQuery;
        } else {
            $params = array(
                "acl" => $obj,
                "validity" => $validity,
                "maxQueriesPerIPPerHour" => $maxQueriesPerIPPerHour,
                "maxHitsPerQuery" => $maxHitsPerQuery
            );
        }
        return $this->client->request($this->context, "PUT", "/1/indexes/" . $this->urlIndexName . "/keys/" . $key , array(), $params,
            $this->context->writeHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
    }

    /**
     * Send a batch request
     * @param  $requests an associative array defining the batch request body
     */
    public function batch($requests) {
        return $this->client->request($this->context, "POST", "/1/indexes/" . $this->urlIndexName . "/batch", array(), $requests,
            $this->context->writeHostsArray, $this->context->connectTimeout, $this->context->readTimeout);
    }

    /**
     * Build a batch request
     * @param  $action the batch action
     * @param  $objects the array of objects
     * @param  $withObjectID set an 'objectID' attribute
     * @param  $objectIDKey the objectIDKey
     * @return array
     */
    private function buildBatch($action, $objects, $withObjectID, $objectIDKey = "objectID") {
        $requests = array();
        foreach ($objects as $obj) {
            $req = array("action" => $action, "body" => $obj);
            if ($withObjectID && array_key_exists($objectIDKey, $obj)) {
                $req["objectID"] = (string) $obj[$objectIDKey];
            }
            array_push($requests, $req);
        }
        return array("requests" => $requests);
    }
}

class Version
{
    const VALUE                   = "1.5.5";

    public static $custom_value   = "";

    public static function get()
    {
        return self::VALUE.static::$custom_value;
    }
}