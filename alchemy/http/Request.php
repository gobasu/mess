<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\http;
class RequestException extends \Exception {}
class Request 
{
    /**
     * Gets global request performed to server
     * @return Request
     */
    public static function getGlobal()
    {
        if (!(self::$globalRequest instanceof Request)) {

            //create headers
            $headers = array(
                'Host'              => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null,
                'Connection'        => isset($_SERVER['HTTP_CONNECTION']) ? $_SERVER['HTTP_CONNECTION'] : null,
                'User-Agent'        => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null,
                'Cache-Control'     => isset($_SERVER['HTTP_CACHE_CONTROL']) ? $_SERVER['HTTP_CACHE_CONTROL'] : null,
                'Accept-Encoding'   => isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : null,
                'Accept-Language'   => isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : null,
                'Accept-Charset'    => isset($_SERVER['HTTP_ACCEPT_CHARSET']) ? $_SERVER['HTTP_ACCEPT_CHARSET'] : null
            );

            //create global request's data
            $requestData = array();
            switch ($_SERVER['REQUEST_METHOD']) {
                case self::METHOD_POST:
                    $requestData = $_POST;
                    break;
                case self::METHOD_PUT:
                    $requestData = file_get_contents("php://input");
                    break;
                case self::METHOD_GET:
                    $requestData = $_GET;
                    break;
                default:
                    $requestData = $_REQUEST;
                    break;
            }

            self::$globalRequest = new self($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'], $requestData, new Headers($headers));
            //is XHR
            self::$globalRequest->isXHR(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest');

            //is secure
            if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) {
                self::$globalRequest->isSecure(true);
            }

        }
        return self::$globalRequest;
    }

    /**
     * Constructor
     *
     * @param string $url request url or uri
     * @param string $method request method (GET, POST, DELETE OR PUT)
     * @param array $parameters request post data
     * @param Headers $headers requests headers @see Header
     */
    public function __construct($url, $method = self::METHOD_GET, $data = array(), Headers $headers = null)
    {
        $url = parse_url($url);

        $this->uri = isset($url['path']) ? $url['path'] : '/';
        if (isset($url['query'])) {
            parse_str($url['query'], $this->query);
        }
        $this->data = $data;
        if (isset($url['scheme'])) {
            $this->scheme = $url['scheme'];
        }
        if (isset($url['host'])) {
            $this->host = $url['host'];
        }

        $this->method = $method;
        $this->headers = $headers;
    }

    /**
     * Checks whatever request's method is POST
     * @return bool true if request's method is POST
     */
    public function isPost()
    {
        return $this->getMethod() == self::METHOD_POST;
    }

    /**
     * Checks whatever request's method is GET
     * @return bool true is request's method is GET
     */
    public function isGet()
    {
        return $this->getMethod() == self::METHOD_GET;
    }

    /**
     * Checks whatever request's method is DELETE
     * @return bool true is request's method is DELETE
     */
    public function isDelete()
    {
        return $this->getMethod() == self::METHOD_DELETE;
    }

    /**
     * Checks whatever request's method is PUT
     * @return bool true is request's method is PUT
     */
    public function isPut()
    {
        return $this->getMethod() == self::METHOD_PUT;
    }

    /**
     * Checks whatever request was made as XHR
     * @return bool
     */
    public function isXHR($set = null)
    {
        if ($set !== null) {
            $this->isXHR = $set;
        }
        return $this->isXHR;
    }

    /**
     * Gets request's query string
     *
     * @return array
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Sets request's query string
     *
     * @param array $query
     */
    public function setQuery(array $query)
    {
        $this->query = $query;
    }

    /**
     * Sends a request using CURLlib
     *
     * @param int $timeout timeout in ms
     * @return Response
     * @throws RequestException
     */
    public function send($timeout = null)
    {
        //set curl options
        $url = $this->scheme . '://' . $this->host . $this->uri . (empty($this->query) ? '' : '?' . http_build_query($this->query));
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER  => true
        );
        if ($timeout) {
            $options[CURLOPT_TIMEOUT_MS] = $timeout;
        }
        if (!empty($this->data)) {
            $options[CURLOPT_POST]          = 1;
            $options[CURLOPT_POSTFIELDS]    = $this->data;
        }

        if ($this->method == self::METHOD_PUT) {
            $options[CURLOPT_PUT]               = 1;
            $options[CURLOPT_BINARYTRANSFER]    = 1;
        }

        if ($this->caFile) {
            $this->verifyPeer = true;
            $options[CURLOPT_CAINFO] = $this->caFile;
        }
        $options[CURLOPT_SSL_VERIFYPEER] = $this->verifyPeer;
        $options[CURLOPT_SSL_VERIFYHOST] = $this->verifyPeer;

        if ($this->headers) {
            $options[CURLOPT_HTTPHEADER] = $this->headers->toArray();
        }

        //perform curl request
        $handler = curl_init($url);
        curl_setopt_array($handler, $options);
        $result = curl_exec($handler);
        $info = curl_getinfo($handler);
        $errorNo = curl_errno($handler);
        $errorMessage = curl_error($handler);
        curl_close($handler);

        if ($errorNo !== 0) {
            throw new RequestException($errorMessage, $errorNo);
        }
        $header = explode("\n", substr($result, 0, $info['header_size']));
        $body = substr($result, $info['header_size']);

        //http version
        $version = substr($header[0],5,3);
        array_shift($header);

        $headers = new Headers();

        foreach ($header as $h) {
            $pos = strpos($h, ':');
            if (!$pos) {
                continue;
            }
            $headers->set(substr($h, 0, $pos), substr($h, $pos + 2));
        }


        $response = new Response($body, $info['http_code']);
        $response->setVersion($version);
        return $response;
    }

    /**
     * Sets file that will be used to storage cookies
     * while making requests
     *
     * @param $file
     */
    public function setCookieJar($file)
    {
        $this->cookieJar = $file;
    }

    /**
     * Sets request's data
     *
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Returns request's data
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    public function setVerifyPeer($verify = true)
    {
        $this->verifyPeer = $verify;
    }

    public function setCAFile($filename)
    {
        if (!is_readable($filename)) {
            throw new RequestException(sprintf('Cert info file `%s` is not readable', $filename));
        }
        $this->caFile = $filename;
    }

    /**
     * Checks/sets whatever request is/should be done by ssl
     *
     * @param null $set
     * @return bool
     */
    public function isSecure($set = null)
    {
        if ($set !== null) {
            if ($set) {
                $this->scheme = 'https';
            } else {
                $this->scheme = 'http';
            }
        }
        return $this->scheme == 'https';
    }

    /**
     * Gets request's scheme eg. http, https
     * Defult is http
     *
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    public function setScheme($scheme = 'http')
    {
        $this->scheme = $scheme;
    }
    public function getMethod()
    {
        return $this->method;
    }

    public function setHeaders(Headers $headers)
    {
        $this->headers = $headers;
    }

    public function getHeader($header)
    {
        return $this->headers[$header];
    }

    public function getAllHeaders()
    {
        return $this->headers;
    }

    public function getURI()
    {
        return $this->uri;
    }

    /**
     * Query
     * @var array
     */
    protected $query = array();

    /**
     * @var string
     */
    protected $uri = '/';

    /**
     * @var string
     */
    protected $host;

    /**
     * @var array
     */
    protected $data = array();

    /**
     * @var string
     */
    protected $method;

    /**
     * @var bool
     */
    protected $isXHR = false;

    /**
     * @var string
     */
    protected $scheme = 'http';

    /**
     * @var Headers
     */
    protected $headers;

    /**
     * @var bool
     */
    protected $verifyPeer = false;

    /**
     * Filename where cookies are preserved
     *
     * @var string
     */
    protected $cookieJar;

    protected $caFile;

    /**
     * @var Request
     */
    private static $globalRequest;

    const METHOD_GET    = 'GET';
    const METHOD_POST   = 'POST';
    const METHOD_PUT    = 'PUT';
    const METHOD_DELETE = 'DELETE';

}