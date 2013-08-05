<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\http;
class Response
{
    public function __construct($body, $status = 200, Headers $headers = null)
    {
        $this->body = $body;
        $this->status = $status;
        if ($headers) {
            $this->setHeaders($headers);
        }
    }

    public function setHeaders(Headers $headers)
    {
        $this->headers = $headers;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function setVersion($version)
    {
        $this->version = $version;
    }

    protected function sendStatusHeader()
    {
        if (!headers_sent()) {
            header(sprintf('HTTP/%s %s %s', $this->version, $this->status, self::$statusCodes[$this->status]));
        }
    }


    public function send()
    {
        $this->sendStatusHeader();
        if ($this->headers) {
            $this->headers->send();
        }
        echo $this->body;
    }

    public function __toString()
    {
        header(sprintf('HTTP/%s %s %s', $this->version, $this->status, self::$statusCodes[$this->status]));
        if ($this->headers) {
            $this->headers->send();
        }
        return $this->body . '';
    }

    /**
     * @var Headers
     */
    protected $headers;

    /**
     * @var string
     */
    protected $body;

    protected $status = 200;

    protected $version = '1.1';

    protected static $statusCodes = array (
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported'
    );
}