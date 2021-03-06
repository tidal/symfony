<?php

namespace Symfony\Components\RequestHandler;

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Request is the base implementation of a user request.
 *
 * After initialization, the request is read-only. The only writable
 * values are the query ones (mostly by the router).
 *
 * You can reinitialize the request by calling the setParameters() method.
 *
 * @package    Symfony
 * @subpackage Components_RequestHandler
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class Request implements RequestInterface
{
  protected $pathParameters;
  protected $requestParameters;
  protected $queryParameters;
  protected $serverParameters;
  protected $filesParameters;
  protected $cookiesParameters;
  protected $languages;
  protected $charsets;
  protected $acceptableContentTypes;
  protected $scriptName;
  protected $pathInfo;
  protected $requestUri;
  protected $baseUrl;
  protected $basePath;
  protected $method;
  protected $format;

  static protected $formats;

  /**
   * Constructor.
   *
   * @param array $query   The GET parameters
   * @param array $request The POST parameters
   * @param array $path    The parameters parsed from the PATH_INFO (see Router)
   * @param array $cookies The COOKIE parameters
   * @param array $files   The FILES parameters
   * @param array $server  The SERVER parameters
   */
  public function __construct(array $query = null, array $request = null, array $path = null, array $cookies = null, array $files = null, array $server = null)
  {
    $this->setParameters($request, $query, $path, $cookies, $files, $server);
  }

  /**
   * Sets the parameters for this request.
   *
   * This method also re-initializes all properties.
   *
   * @param array $query   The GET parameters
   * @param array $request The POST parameters
   * @param array $path    The parameters parsed from the PATH_INFO
   * @param array $cookies The COOKIE parameters
   * @param array $files   The FILES parameters
   * @param array $server  The SERVER parameters
   */
  public function setParameters(array $query = null, array $request = null, array $path = null, array $cookies = null, array $files = null, array $server = null)
  {
    $this->requestParameters = null !== $request ? $request : $_POST;
    $this->queryParameters = null !== $query ? $query : $_GET;
    $this->pathParameters = null !== $path ? $path : array();
    $this->cookiesParameters = null !== $cookies ? $cookies : $_COOKIE;
    $this->filesParameters = self::convertFileInformation(null !== $files ? $files : $_FILES);
    $this->serverParameters = null !== $server ? $server : $_SERVER;

    $this->languages = null;
    $this->charsets = null;
    $this->acceptableContentTypes = null;
    $this->scriptName = null;
    $this->pathInfo = null;
    $this->requestUri = null;
    $this->baseUrl = null;
    $this->basePath = null;
    $this->method = null;
    $this->format = null;
  }

  /**
   * Creates a Request based on a given URI and configuration.
   *
   * @param string $uri        The URI
   * @param string $method     The HTTP method
   * @param array  $parameters The request (GET) or query (POST) parameters
   * @param array  $cookies    The request cookies ($_COOKIE)
   * @param array  $files      The request files ($_FILES)
   * @param array  $server     The server parameters ($_SERVER)
   *
   * @return Request A Request instance
   */
  static public function createFromUri($uri, $method = 'get', $parameters = array(), $cookies = array(), $files = array(), $server = array())
  {
    if (in_array($method, array('post', 'put', 'delete')))
    {
      $request = $parameters;
      $query = array();
    }
    else
    {
      $request = array();
      $query = $parameters;
    }

    $queryString = false !== ($pos = strpos($uri, '?')) ? html_entity_decode(substr($uri, $pos + 1)) : '';
    parse_str($queryString, $qs);
    if (is_array($qs))
    {
      $query = array_replace($qs, $query);
    }

    $server = array_replace(array(
      'HTTP_HOST'            => 'localhost',
      'SERVER_NAME'          => 'localhost',
      'SERVER_PORT'          => 80,
      'HTTP_USER_AGENT'      => 'SymfonyClient/1.0',
      'HTTP_ACCEPT'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
      'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
      'HTTP_ACCEPT_CHARSET'  => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
      'REMOTE_ADDR'          => '127.0.0.1',
      'REQUEST_METHOD'       => strtoupper($method),
      'PATH_INFO'            => '',
      'REQUEST_URI'          => $uri,
      'SCRIPT_NAME'          => '',
      'SCRIPT_FILENAME'      => '',
      'QUERY_STRING'         => $queryString,
    ), $server);

    return new self($request, $query, array(), $cookies, $files, $server);
  }

  /**
   * Clones a request and overrides some of its parameters.
   *
   * @param array $query   The GET parameters
   * @param array $request The POST parameters
   * @param array $path    The parameters parsed from the PATH_INFO
   * @param array $cookies The COOKIE parameters
   * @param array $files   The FILES parameters
   * @param array $server  The SERVER parameters
   */
  public function duplicate(array $query = null, array $request = null, array $path = null, array $cookies = null, array $files = null, array $server = null)
  {
    $dup = clone $this;
    $dup->setParameters(
      null !== $query ? $query : $this->queryParameters,
      null !== $request ? $request : $this->requestParameters,
      null !== $path ? $path : $this->pathParameters,
      null !== $cookies ? $cookies : $this->cookiesParameters,
      null !== $files ? $files : $this->filesParameters,
      null !== $server ? $server : $this->serverParameters
    );

    return $dup;
  }

  // Order of precedence: GET, PATH, POST, COOKIE
  // Avoid using this method in controllers:
  //  * slow
  //  * prefer to get from a "named" source
  // This method is mainly useful for libraries that want to provide some flexibility
  public function getParameter($key, $default = null)
  {
    return $this->getQueryParameter($key, $this->getPathParameter($key, $this->getRequestParameter($key, $default)));
  }

  /**
   * Returns the server parameters ($_SERVER).
   *
   * @return array An array of server parameters
   */
  public function getServerParameters()
  {
    return $this->serverParameters;
  }

  /**
   * Returns a server parameter ($_SERVER).
   *
   * @param string $key     The key
   * @param mixed  $default The default value
   */
  public function getServerParameter($key, $default = null)
  {
    return isset($this->serverParameters[$key]) ? $this->serverParameters[$key] : $default;
  }

  /**
   * Returns the path parameters.
   *
   * @return array An array of path parameters
   */
  public function getPathParameters()
  {
    return $this->pathParameters;
  }

  /**
   * Sets the path parameters.
   *
   * @param array An array of path parameters
   */
  public function setPathParameters(array $parameters)
  {
    $this->pathParameters = $parameters;
  }

  /**
   * Returns a path parameter.
   *
   * @param string $key     The key
   * @param mixed  $default The default value
   */
  public function getPathParameter($key, $default = null)
  {
    return isset($this->pathParameters[$key]) ? $this->pathParameters[$key] : $default;
  }

  /**
   * Returns the request parameters ($_POST).
   *
   * @return array An array of request parameters
   */
  public function getRequestParameters()
  {
    return $this->requestParameters;
  }

  /**
   * Returns a request parameter ($_POST).
   *
   * @param string $key     The server key
   * @param mixed  $default The default value
   */
  public function getRequestParameter($key, $default = null)
  {
    return isset($this->requestParameters[$key]) ? $this->requestParameters[$key] : $default;
  }

  /**
   * Returns the query parameters ($_GET).
   *
   * @return array An array of query parameters
   */
  public function getQueryParameters()
  {
    return $this->queryParameters;
  }

  /**
   * Returns a query parameter ($_GET).
   *
   * @param string $key     The server key
   * @param mixed  $default The default value
   */
  public function getQueryParameter($key, $default = null)
  {
    return isset($this->queryParameters[$key]) ? $this->queryParameters[$key] : $default;
  }

  /**
   * Returns a cookie value ($_COOKIE).
   *
   * @param string $key    The key
   * @param mixed  $default The default value
   *
   * @return mixed The cookie value
   */
  public function getCookie($key, $default = null)
  {
    return isset($this->cookiesParameters[$key]) ? $cookiesParameters[$key] : $default;
  }

  /**
   * Returns the array of cookies ($_COOKIE).
   *
   * @param array An array of cookies
   */
  public function getCookies()
  {
    return $this->cookiesParameters;
  }

  /**
   * Returns a file from the request ($_FILES).
   *
   * @param  string $key A key
   *
   * @return array The associated file
   */
  public function getFile($key)
  {
    return isset($this->filesParameters[$key]) ? $this->filesParameters[$key] : array();
  }

  /**
   * Returns the array of files ($_FILES).
   *
   * @return array An associative array of files
   */
  public function getFiles()
  {
    return $this->filesParameters;
  }

  public function getHttpHeader($name, $default = null)
  {
    return $this->getServerParameter('HTTP_'.strtoupper(strtr($name, '-', '_')), $default);
  }

  /**
   * Returns current script name.
   *
   * @return string
   */
  public function getScriptName()
  {
    return $this->getServerParameter('SCRIPT_NAME', $this->getServerParameter('ORIG_SCRIPT_NAME', ''));
  }

  public function getPathInfo()
  {
    if (null === $this->pathInfo)
    {
      $this->pathInfo = $this->preparePathInfo();
    }

    return $this->pathInfo;
  }

  public function getBasePath()
  {
    if (null === $this->basePath)
    {
      $this->basePath = $this->prepareBasePath();
    }

    return $this->basePath;
  }

  public function getBaseUrl()
  {
    if (null === $this->baseUrl)
    {
      $this->baseUrl = $this->prepareBaseUrl();
    }

    return $this->baseUrl;
  }

  public function getScheme()
  {
    return ($this->getServerParameter('HTTPS') == 'on') ? 'https' : 'http';
  }

  public function getHttpHost()
  {
    $host = $this->getServerParameter('HTTP_HOST');
    if (!empty($host))
    {
      return $host;
    }

    $scheme = $this->getScheme();
    $name   = $this->getServerParameter('SERVER_NAME');
    $port   = $this->getServerParameter('SERVER_PORT');

    if (($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443))
    {
      return $name;
    }
    else
    {
      return $name.':'.$port;
    }
  }

  public function getRequestUri()
  {
    if (null === $this->requestUri)
    {
      $this->requestUri = $this->prepareRequestUri();
    }

    return $this->requestUri;
  }

  public function isSecure()
  {
    return (
      (strtolower($this->getServerParameter('HTTPS')) == 'on' || $this->getServerParameter('HTTPS') == 1)
      ||
      (strtolower($this->getServerParameter('HTTP_SSL_HTTPS')) == 'on' || $this->getServerParameter('HTTP_SSL_HTTPS') == 1)
      ||
      (strtolower($this->getServerParameter('HTTP_X_FORWARDED_PROTO')) == 'https')
    );
  }

  /**
   * Returns the host name.
   *
   * @return string
   */
  public function getHost()
  {
    if ($host = $this->getServerParameter('HTTP_X_FORWARDED_HOST'))
    {
      $elements = implode(',', $host);

      return trim($elements[count($elements) - 1]);
    }
    else
    {
      return $this->getServerParameter('HTTP_HOST', $this->getServerParameter('SERVER_NAME', $this->getServerParameter('SERVER_ADDR', '')));
    }
  }

  /**
   * Gets the request method.
   *
   * @return string The request method
   */
  public function getMethod()
  {
    if (null === $this->method)
    {
      switch ($this->getServerParameter('REQUEST_METHOD', 'GET'))
      {
        case 'POST':
          $this->method = strtoupper($this->getRequestParameter('_method', 'POST'));
          break;

        case 'PUT':
          $this->method = 'PUT';
          break;

        case 'DELETE':
          $this->method = 'DELETE';
          break;

        case 'HEAD':
          $this->method = 'HEAD';
          break;

        default:
          $this->method = 'GET';
      }
    }

    return $this->method;
  }

  /**
   * Gets the mime type associated with the format.
   *
   * @param  string $format  The format
   *
   * @return string The associated mime type (null if not found)
   */
  public function getMimeType($format)
  {
    if (null === static::$formats)
    {
      static::initializeFormats();
    }

    return isset(static::$formats[$format]) ? static::$formats[$format][0] : null;
  }

  /**
   * Gets the format associated with the mime type.
   *
   * @param  string $mimeType  The associated mime type
   *
   * @return string The format (null if not found)
   */
  public function getFormat($mimeType)
  {
    if (null === static::$formats)
    {
      static::initializeFormats();
    }

    foreach (static::$formats as $format => $mimeTypes)
    {
      if (in_array($mimeType, (array) $mimeTypes))
      {
        return $format;
      }
    }

    return null;
  }

  /**
   * Associates a format with mime types.
   *
   * @param string       $format     The format
   * @param string|array $mimeTypes  The associated mime types (the preferred one must be the first as it will be used as the content type)
   */
  public function setFormat($format, $mimeTypes)
  {
    if (null === static::$formats)
    {
      static::initializeFormats();
    }

    static::$formats[$format] = is_array($mimeTypes) ? $mimeTypes : array($mimeTypes);
  }

  /**
   * Gets the request format.
   *
   * Here is the process to determine the format:
   *
   *  * format defined by the user (with setRequestFormat())
   *  * _format request parameter
   *  * null
   *
   * @return string The request format
   */
  public function getRequestFormat()
  {
    if (null === $this->format)
    {
      $this->format = $this->getParameter('_format', 'html');
    }

    return $this->format;
  }

  /**
   * Returns the preferred language.
   *
   * @param  array  $cultures  An array of ordered available cultures
   *
   * @return string The preferred culture
   */
  public function getPreferredLanguage(array $cultures = null)
  {
    $preferredLanguages = $this->getLanguages();

    if (null === $cultures)
    {
      return isset($preferredLanguages[0]) ? $preferredLanguages[0] : null;
    }

    if (!$preferredLanguages)
    {
      return $cultures[0];
    }

    $preferredLanguages = array_values(array_intersect($preferredLanguages, $cultures));

    return isset($preferredLanguages[0]) ? $preferredLanguages[0] : $cultures[0];
  }

  /**
   * Gets a list of languages acceptable by the client browser.
   *
   * @return array Languages ordered in the user browser preferences
   */
  public function getLanguages()
  {
    if (null !== $this->languages)
    {
      return $this->languages;
    }

    $languages = $this->splitHttpAcceptHeader($this->getHttpHeader('ACCEPT_LANGUAGE'));
    foreach ($languages as $lang)
    {
      if (strstr($lang, '-'))
      {
        $codes = explode('-', $lang);
        if ($codes[0] == 'i')
        {
          // Language not listed in ISO 639 that are not variants
          // of any listed language, which can be registerd with the
          // i-prefix, such as i-cherokee
          if (count($codes) > 1)
          {
            $lang = $codes[1];
          }
        }
        else
        {
          for ($i = 0, $max = count($codes); $i < $max; $i++)
          {
            if ($i == 0)
            {
              $lang = strtolower($codes[0]);
            }
            else
            {
              $lang .= '_'.strtoupper($codes[$i]);
            }
          }
        }
      }

      $this->languages[] = $lang;
    }

    return $this->languages;
  }

  /**
   * Gets a list of charsets acceptable by the client browser.
   *
   * @return array List of charsets in preferable order
   */
  public function getCharsets()
  {
    if (null !== $this->charsets)
    {
      return $this->charsets;
    }

    return $this->charsets = $this->splitHttpAcceptHeader($this->getHttpHeader('ACCEPT_CHARSET'));
  }

  /**
   * Gets a list of content types acceptable by the client browser
   *
   * @return array Languages ordered in the user browser preferences
   */
  public function getAcceptableContentTypes()
  {
    if (null !== $this->acceptableContentTypes)
    {
      return $this->acceptableContentTypes;
    }

    return $this->acceptableContentTypes = $this->splitHttpAcceptHeader($this->getHttpHeader('ACCEPT'));
  }

  /**
   * Returns true if the request is a XMLHttpRequest.
   *
   * It works if your JavaScript library set an X-Requested-With HTTP header.
   * It is known to work with Prototype, Mootools, jQuery.
   *
   * @return bool true if the request is an XMLHttpRequest, false otherwise
   */
  public function isXmlHttpRequest()
  {
    return 'XMLHttpRequest' == $this->getHttpHeader('X_REQUESTED_WITH');
  }

  /**
   * Splits an Accept-* HTTP header.
   *
   * @param string $header  Header to split
   */
  public function splitHttpAcceptHeader($header)
  {
    if (!$header)
    {
      return array();
    }

    $values = array();
    foreach (array_filter(explode(',', $header)) as $value)
    {
      // Cut off any q-value that might come after a semi-colon
      if ($pos = strpos($value, ';'))
      {
        $q     = (float) trim(substr($value, $pos + 3));
        $value = trim(substr($value, 0, $pos));
      }
      else
      {
        $q = 1;
      }

      $values[$value] = $q;
    }

    arsort($values);

    return array_keys($values);
  }

  /*
   * The following methods are derived from code of the Zend Framework (1.10dev - 2010-01-24)
   *
   * Code subject to the new BSD license (http://framework.zend.com/license/new-bsd).
   *
   * Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
   */

  protected function prepareRequestUri()
  {
    $requestUri = '';

    if (isset($this->serverParameters['HTTP_X_REWRITE_URL']))
    {
      // check this first so IIS will catch
      $requestUri = $this->serverParameters['HTTP_X_REWRITE_URL'];
    }
    elseif (
        // IIS7 with URL Rewrite: make sure we get the unencoded url (double slash problem)
        isset($this->serverParameters['IIS_WasUrlRewritten'])
        && $this->serverParameters['IIS_WasUrlRewritten'] == '1'
        && isset($this->serverParameters['UNENCODED_URL'])
        && $this->serverParameters['UNENCODED_URL'] != ''
        )
    {
      $requestUri = $this->serverParameters['UNENCODED_URL'];
    }
    elseif (isset($this->serverParameters['REQUEST_URI']))
    {
      $requestUri = $this->serverParameters['REQUEST_URI'];
      // Http proxy reqs setup request uri with scheme and host [and port] + the url path, only use url path
      $schemeAndHttpHost = $this->getScheme() . '://' . $this->getHttpHost();
      if (strpos($requestUri, $schemeAndHttpHost) === 0)
      {
        $requestUri = substr($requestUri, strlen($schemeAndHttpHost));
      }
    }
    elseif (isset($this->serverParameters['ORIG_PATH_INFO']))
    {
      // IIS 5.0, PHP as CGI
      $requestUri = $this->serverParameters['ORIG_PATH_INFO'];
      if (!empty($this->serverParameters['QUERY_STRING']))
      {
        $requestUri .= '?' . $this->serverParameters['QUERY_STRING'];
      }
    }

    return $requestUri;
  }

  protected function prepareBaseUrl()
  {
    $baseUrl = '';

    $filename = (isset($this->serverParameters['SCRIPT_FILENAME'])) ? basename($this->serverParameters['SCRIPT_FILENAME']) : '';

    if (isset($this->serverParameters['SCRIPT_NAME']) && basename($this->serverParameters['SCRIPT_NAME']) === $filename)
    {
      $baseUrl = $this->serverParameters['SCRIPT_NAME'];
    }
    elseif (isset($this->serverParameters['PHP_SELF']) && basename($this->serverParameters['PHP_SELF']) === $filename)
    {
      $baseUrl = $this->serverParameters['PHP_SELF'];
    }
    elseif (isset($this->serverParameters['ORIG_SCRIPT_NAME']) && basename($this->serverParameters['ORIG_SCRIPT_NAME']) === $filename)
    {
      $baseUrl = $this->serverParameters['ORIG_SCRIPT_NAME']; // 1and1 shared hosting compatibility
    }
    else
    {
        // Backtrack up the script_filename to find the portion matching
        // php_self
        $path    = isset($this->serverParameters['PHP_SELF']) ? $this->serverParameters['PHP_SELF'] : '';
        $file    = isset($this->serverParameters['SCRIPT_FILENAME']) ? $this->serverParameters['SCRIPT_FILENAME'] : '';
        $segs    = explode('/', trim($file, '/'));
        $segs    = array_reverse($segs);
        $index   = 0;
        $last    = count($segs);
        $baseUrl = '';
        do
        {
          $seg     = $segs[$index];
          $baseUrl = '/' . $seg . $baseUrl;
          ++$index;
        } while (($last > $index) && (false !== ($pos = strpos($path, $baseUrl))) && (0 != $pos));
    }

    // Does the baseUrl have anything in common with the request_uri?
    $requestUri = $this->getRequestUri();

    if ($baseUrl && 0 === strpos($requestUri, $baseUrl))
    {
      // full $baseUrl matches
      return $baseUrl;
    }

    if ($baseUrl && 0 === strpos($requestUri, dirname($baseUrl)))
    {
      // directory portion of $baseUrl matches
      return rtrim(dirname($baseUrl), '/');
    }

    $truncatedRequestUri = $requestUri;
    if (($pos = strpos($requestUri, '?')) !== false)
    {
      $truncatedRequestUri = substr($requestUri, 0, $pos);
    }

    $basename = basename($baseUrl);
    if (empty($basename) || !strpos($truncatedRequestUri, $basename))
    {
      // no match whatsoever; set it blank
      return '';
    }

    // If using mod_rewrite or ISAPI_Rewrite strip the script filename
    // out of baseUrl. $pos !== 0 makes sure it is not matching a value
    // from PATH_INFO or QUERY_STRING
    if ((strlen($requestUri) >= strlen($baseUrl)) && ((false !== ($pos = strpos($requestUri, $baseUrl))) && ($pos !== 0)))
    {
      $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
    }

    return rtrim($baseUrl, '/');
  }

  protected function prepareBasePath()
  {
    $basePath = '';
    $filename = (isset($this->serverParameters['SCRIPT_FILENAME'])) ? basename($this->serverParameters['SCRIPT_FILENAME']) : '';
    $baseUrl = $this->getBaseUrl();
    if (empty($baseUrl))
    {
      return '';
    }

    if (basename($baseUrl) === $filename)
    {
      $basePath = dirname($baseUrl);
    }
    else
    {
      $basePath = $baseUrl;
    }

    if ('\\' === DIRECTORY_SEPARATOR)
    {
      $basePath = str_replace('\\', '/', $basePath);
    }

    return rtrim($basePath, '/');
  }

  protected function preparePathInfo()
  {
    $baseUrl = $this->getBaseUrl();

    if (null === ($requestUri = $this->getRequestUri()))
    {
      return '';
    }

    $pathInfo = '';

    // Remove the query string from REQUEST_URI
    if ($pos = strpos($requestUri, '?'))
    {
      $requestUri = substr($requestUri, 0, $pos);
    }

    if ((null !== $baseUrl) && (false === ($pathInfo = substr($requestUri, strlen($baseUrl)))))
    {
      // If substr() returns false then PATH_INFO is set to an empty string
      return '';
    }
    elseif (null === $baseUrl)
    {
      return $requestUri;
    }

    return (string) $pathInfo;
  }

  /**
   * Converts uploaded file array to a format following the $_GET and $POST naming convention.
   *
   * It's safe to pass an already converted array, in which case this method just returns the original array unmodified.
   *
   * @param  array $taintedFiles An array representing uploaded file information
   *
   * @return array An array of re-ordered uploaded file information
   */
  protected function convertFileInformation(array $taintedFiles)
  {
    $files = array();
    foreach ($taintedFiles as $key => $data)
    {
      $files[$key] = $this->fixPhpFilesArray($data);
    }

    return $files;
  }

  static protected function initializeFormats()
  {
    static::$formats = array(
      'txt'  => 'text/plain',
      'js'   => array('application/javascript', 'application/x-javascript', 'text/javascript'),
      'css'  => 'text/css',
      'json' => array('application/json', 'application/x-json'),
      'xml'  => array('text/xml', 'application/xml', 'application/x-xml'),
      'rdf'  => 'application/rdf+xml',
      'atom' => 'application/atom+xml',
    );
  }

  static protected function fixPhpFilesArray($data)
  {
    $fileKeys = array('error', 'name', 'size', 'tmp_name', 'type');
    $keys = array_keys($data);
    sort($keys);

    if ($fileKeys != $keys || !isset($data['name']) || !is_array($data['name']))
    {
      return $data;
    }

    $files = $data;
    foreach ($fileKeys as $k)
    {
      unset($files[$k]);
    }
    foreach (array_keys($data['name']) as $key)
    {
      $files[$key] = self::fixPhpFilesArray(array(
        'error'    => $data['error'][$key],
        'name'     => $data['name'][$key],
        'type'     => $data['type'][$key],
        'tmp_name' => $data['tmp_name'][$key],
        'size'     => $data['size'][$key],
      ));
    }

    return $files;
  }
}
