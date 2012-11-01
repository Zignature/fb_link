<?php
/**
 * Source code:
 * Copyright 2011 Facebook, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 *
 * Modifications and customizations:
 * @author - Ron Hickson
 * @website - http://www.hicksondesign.com
 * @date - 2012-09-25
 */

if (!function_exists('curl_init')) {
  throw new Exception('Facebook needs the CURL PHP extension.');
}
if (!function_exists('json_decode')) {
  throw new Exception('Facebook needs the JSON PHP extension.');
}

/**
 * Thrown when an API call returns an exception.
 *
 * @author Naitik Shah <naitik@facebook.com>
 */
class FacebookApiException extends Exception
{
  /**
   * The result from the API server that represents the exception information.
   */
  protected $result;

  /**
   * Make a new API Exception with the given result.
   *
   * @param array $result The result from the API server
   */
  public function __construct($result) {
    $this->result = $result;

    $code = isset($result['error_code']) ? $result['error_code'] : 0;

    if (isset($result['error_description'])) {
      // OAuth 2.0 Draft 10 style
      $msg = $result['error_description'];
    } else if (isset($result['error']) && is_array($result['error'])) {
      // OAuth 2.0 Draft 00 style
      $msg = $result['error']['message'];
    } else if (isset($result['error_msg'])) {
      // Rest server style
      $msg = $result['error_msg'];
    } else {
      $msg = 'Unknown Error. Check getResult()';
    }

    parent::__construct($msg, $code);
  }

  /**
   * Return the associated result object returned by the API server.
   *
   * @return array The result from the API server
   */
  public function getResult() {
    return $this->result;
  }

  /**
   * Returns the associated type for the error. This will default to
   * 'Exception' when a type is not available.
   *
   * @return string
   */
  public function getType() {
    if (isset($this->result['error'])) {
      $error = $this->result['error'];
      if (is_string($error)) {
        // OAuth 2.0 Draft 10 style
        return $error;
      } else if (is_array($error)) {
        // OAuth 2.0 Draft 00 style
        if (isset($error['type'])) {
          return $error['type'];
        }
      }
    }

    return 'Exception';
  }

  /**
   * To make debugging easier.
   *
   * @return string The string representation of the error
   */
  public function __toString() {
    $str = $this->getType() . ': ';
    if ($this->code != 0) {
      $str .= $this->code . ': ';
    }
    return $str . $this->message;
  }
}

/**
 * Provides access to the Facebook Platform.  This class provides
 * a majority of the functionality needed, but the class is abstract
 * because it is designed to be sub-classed.  The subclass must
 * implement the four abstract methods listed at the bottom of
 * the file.
 *
 * @author Naitik Shah <naitik@facebook.com>
 */
class Base_facebook {
  /**
   * Version.
   */
  const VERSION = '3.1.1';

  /**
   * Default options for curl.
   */
  public static $CURL_OPTS = array(
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_USERAGENT      => 'facebook-php-3.1',
  );

  /**
   * Maps aliases to Facebook domains.
   */
  public static $DOMAIN_MAP = array(
    'graph'       => 'https://graph.facebook.com/',
    'graph_video' => 'https://graph-video.facebook.com/',
    'www'         => 'https://www.facebook.com/',
  );

  /**
   * The Application ID.
   *
   * @var string
   */
  protected $appId;

  /**
   * The Application App Secret.
   *
   * @var string
   */
  protected $appSecret;

  /**
   * The OAuth access token received in exchange for a valid authorization
   * code.  null means the access token has yet to be determined.
   *
   * @var string
   */
  protected $accessToken = null;


  /**
   * Initialize a Facebook Application.
   *
   * The configuration:
   * - appId: the application ID
   * - secret: the application secret
   * - fileUpload: (optional) boolean indicating if file uploads are enabled
   *
   * @param array $config The application configuration
   */
  public function __construct() {
      
    $this->EE =& get_instance();
  
	$query = $this->EE->db->get('fb_link');
		
	// Check for the app data and continue
	if ($query->num_rows() > 0) {
		$config = $query->row_array();

		$this->appToken = $config['app_id'].'|'.$config['app_secret'];
		$this->accessToken = $config['access_token'];
	}
  }

  /**
   * Set the Application ID.
   *
   * @param string $appId The Application ID
   * @return BaseFacebook
   */
  public function setAppId($appId) {
    $this->appId = $appId;
    return $this;
  }

  /**
   * Get the Application ID.
   *
   * @return string the Application ID
   */
  public function getAppId() {
    return $this->appId;
  }

  /**
   * Set the App Secret.
   *
   * @param string $apiSecret The App Secret
   * @return BaseFacebook
   * @deprecated
   */
  public function setApiSecret($apiSecret) {
    $this->setAppSecret($apiSecret);
    return $this;
  }

  /**
   * Set the App Secret.
   *
   * @param string $appSecret The App Secret
   * @return BaseFacebook
   */
  public function setAppSecret($appSecret) {
    $this->appSecret = $appSecret;
    return $this;
  }

  /**
   * Get the App Secret.
   *
   * @return string the App Secret
   * @deprecated
   */
  public function getApiSecret() {
    return $this->getAppSecret();
  }

  /**
   * Get the App Secret.
   *
   * @return string the App Secret
   */
  public function getAppSecret() {
    return $this->appSecret;
  }

  /**
   * Sets the access token for api calls.  Use this if you get
   * your access token by other means and just want the SDK
   * to use it.
   *
   * @param string $access_token an access token.
   * @return BaseFacebook
   */
  public function setAccessToken($access_token) {
    $this->accessToken = $access_token;
    return $this;
  }

  /**
   * Determines the access token that should be used for API calls.
   * The first time this is called, $this->accessToken is set equal
   * to either a valid user access token, or it's set to the application
   * access token if a valid user access token wasn't available.  Subsequent
   * calls return whatever the first call returned.
   *
   * @return string The access token
   */
  public function getAccessToken() {
    if ($this->accessToken !== null) {
      // we've done this already and cached it.  Just return.
      return $this->accessToken;
    }

    // first establish access token to be the application
    // access token, in case we navigate to the /oauth/access_token
    // endpoint, where SOME access token is required.
    $this->setAccessToken($this->getApplicationAccessToken());
    return $this->accessToken;
  }
   
  /**
   * Returns the access token that should be used for logged out
   * users when no authorization code is available.
   *
   * @return string The application access token, useful for gathering
   *                public information about users and applications.
   *
   */
  protected function getApplicationAccessToken() {
    return $this->appToken;
  }
  
  /**
   * Return true if this is video post.
   *
   * @param string $path The path
   * @param string $method The http method (default 'GET')
   *
   * @return boolean true if this is video post
   */
  protected function isVideoPost($path, $method = 'GET') {
    if ($method == 'POST' && preg_match("/^(\/)(.+)(\/)(videos)$/", $path)) {
      return true;
    }
    return false;
  }

  /**
   * Invoke the Graph API.
   *
   * @param string $path The path (required)
   * @param string $method The http method (default 'GET')
   * @param array $params The query/post data
   *
   * @return mixed The decoded response object
   * @throws FacebookApiException
   */
  public function graph($path, $method = 'GET', $params = array()) {
    if (is_array($method) && empty($params)) {
      $params = $method;
      $method = 'GET';
    }    
    $params['method'] = $method; // method override as we always do a POST

    if ($this->isVideoPost($path, $method)) {
      $domainKey = 'graph_video';
    } else {
      $domainKey = 'graph';
    }
    $result = json_decode($this->_oauthRequest(
      $this->getUrl($domainKey, $path),
      $params
    ), true);
    
    // results are returned, errors are thrown
    if (is_array($result) && isset($result['error'])) {
      $this->throwAPIException($result);
      // @codeCoverageIgnoreStart
    }
    // @codeCoverageIgnoreEnd

    return $result;
  }

  /**
   * Make a OAuth Request.
   *
   * @param string $url The path (required)
   * @param array $params The query/post data
   *
   * @return string The decoded response object
   * @throws FacebookApiException
   */
  protected function _oauthRequest($url, $params) {
    if (!isset($params['access_token'])) {
      $params['access_token'] = $this->getAccessToken();
    }

    // json_encode all params values that are not strings
    foreach ($params as $key => $value) {
      if (!is_string($value)) {
        $params[$key] = json_encode($value);
      }
    }
    return $this->makeRequest($url, $params);
  }

  /**
   * Makes an HTTP request. This method can be overridden by subclasses if
   * developers want to do fancier things or use something other than curl to
   * make the request.
   *
   * @param string $url The URL to make the request to
   * @param array $params The parameters to use for the POST body
   * @param CurlHandler $ch Initialized curl handle
   *
   * @return string The response text
   */
  protected function makeRequest($url, $params, $ch=null) {
    if (!$ch) {
      $ch = curl_init();
    }

    $opts = self::$CURL_OPTS;
   
    $opts[CURLOPT_POSTFIELDS] = http_build_query($params, null, '&');
    
    $opts[CURLOPT_URL] = $url;
        
    // disable the 'Expect: 100-continue' behaviour. This causes CURL to wait
    // for 2 seconds if the server does not support this header.
    if (isset($opts[CURLOPT_HTTPHEADER])) {
      $existing_headers = $opts[CURLOPT_HTTPHEADER];
      $existing_headers[] = 'Expect:';
      $opts[CURLOPT_HTTPHEADER] = $existing_headers;
    } else {
      $opts[CURLOPT_HTTPHEADER] = array('Expect:');
    }

    curl_setopt_array($ch, $opts);
    $result = curl_exec($ch);
    
    if (curl_errno($ch) == 60) { // CURLE_SSL_CACERT
      self::errorLog('Invalid or no certificate authority found, '.
                     'using bundled information');
      curl_setopt($ch, CURLOPT_CAINFO,
                  dirname(__FILE__) . '/fb_ca_chain_bundle.crt');
      $result = curl_exec($ch);
    }

    // With dual stacked DNS responses, it's possible for a server to
    // have IPv6 enabled but not have IPv6 connectivity.  If this is
    // the case, curl will try IPv4 first and if that fails, then it will
    // fall back to IPv6 and the error EHOSTUNREACH is returned by the
    // operating system.
    if ($result === false && empty($opts[CURLOPT_IPRESOLVE])) {
        $matches = array();
        $regex = '/Failed to connect to ([^:].*): Network is unreachable/';
        if (preg_match($regex, curl_error($ch), $matches)) {
          if (strlen(@inet_pton($matches[1])) === 16) {
            self::errorLog('Invalid IPv6 configuration on server, '.
                           'Please disable or get native IPv6 on your server.');
            self::$CURL_OPTS[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            $result = curl_exec($ch);
          }
        }
    }

    if ($result === false) {
      $e = new FacebookApiException(array(
        'error_code' => curl_errno($ch),
        'error' => array(
        'message' => curl_error($ch),
        'type' => 'CurlException',
        ),
      ));
      curl_close($ch);
      throw $e;
    }
    curl_close($ch);
    return $result;
  }
  
  /**
   * Build the URL for given domain alias, path and parameters.
   *
   * @param $name string The name of the domain
   * @param $path string Optional path (without a leading slash)
   * @param $params array Optional query parameters
   *
   * @return string The URL for the given parameters
   */
  protected function getUrl($name, $path='', $params=array()) {
    $url = self::$DOMAIN_MAP[$name];
    if ($path) {
      if ($path[0] === '/') {
        $path = substr($path, 1);
      }
      $url .= $path;
    }
    if ($params) {
      $url .= '?' . http_build_query($params, null, '&');
    }
    return $url;
  }

  /**
   * Analyzes the supplied result to see if it was thrown
   * because the access token is no longer valid.  If that is
   * the case, then we destroy the session.
   *
   * @param $result array A record storing the error message returned
   *                      by a failed API call.
   */
  protected function throwAPIException($result) {
    $e = new FacebookApiException($result);
    switch ($e->getType()) {
      // OAuth 2.0 Draft 00 style
      case 'OAuthException':
        // OAuth 2.0 Draft 10 style
      case 'invalid_token':
        // REST server errors are just Exceptions
      case 'Exception':
        $message = $e->getMessage();
        if ((strpos($message, 'Error validating access token') !== false) ||
            (strpos($message, 'Invalid OAuth access token') !== false) ||
            (strpos($message, 'An active access token must be used') !== false)
        )
        break;
    }

    throw $e;
  }


  /**
   * Prints to the error log if you aren't in command line mode.
   *
   * @param string $msg Log message
   */
  protected static function errorLog($msg) {
    // disable error log if we are running in a CLI environment
    // @codeCoverageIgnoreStart
    if (php_sapi_name() != 'cli') {
      error_log($msg);
    }
    // uncomment this if you want to see the errors on the page
    // print 'error_log: '.$msg."\n";
    // @codeCoverageIgnoreEnd
  }

  /**
   * Base64 encoding that doesn't need to be urlencode()ed.
   * Exactly the same as base64_encode except it uses
   *   - instead of +
   *   _ instead of /
   *
   * @param string $input base64UrlEncoded string
   * @return string
   */
  protected static function base64UrlDecode($input) {
    return base64_decode(strtr($input, '-_', '+/'));
  }

}
