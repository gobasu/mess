<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\http;

class Headers implements \Iterator, \ArrayAccess
{
    public function __construct(array $headers = array())
    {
        $this->headers = $headers;
    }

    public function set($name, $value)
    {
        if (!isset(self::$validHeaders[$name])) {
            return;
        }
        $this->headers[$name] = $value;
    }

    /**
     * Parses Accept-* headers to more useful format and sorts result by q, e.g
     * text/html;level=2;q=0.4, text/html;level=1, text/*;q=0.3
     * will be turned into :
     * [0] => Array (
     *      [type] => text/html
     *      [level] => 1
     *      [q] => 1
     *  )
     * [1] => Array (
     *      [type] => text/html
     *      [level] => 2
     *      [q] => 0.4
     * )
     * [2] => Array (
     *      [type] => text/*
     *      [q] => 0.3
     * )
     *
     * @param string $accept
     * @return array
     */
    public static function parseAccept($accept)
    {
        $data = array();
        $item = 0;
        $accept = explode(',', $accept);
        foreach ($accept as $info)
        {
            $data[$item++] = array();

            $current = &$data[key($data)];
            $info = explode(';', $info);
            $current['type'] = trim($info[0]);
            array_shift($info);
            foreach ($info as $i) {
                $i = explode('=', $i);
                $current[trim($i[0])] = trim($i[1]);
            }
            if (!isset($current['q'])) {
                $current['q'] = 1;
            }
            next($data);
        }

        //sort by q
        usort($data, function($a, $b){
            if ($a['q'] == $b['q']) {
                return 0;
            }
            return ($a['q'] < $b['q']) ? 1 : -1;
        });

        return $data;
    }

    /**
     * Avoids from caching
     */
    public function avoidCacheControl()
    {
        $this->headers[self::HEADER_CACHE_CONTROL] = 'no-cache, must-revalidate';
        $this->headers[self::HEADER_EXPIRES] = 'Expires: Sat, 26 Jul 1997 05:00:00 GMT';
    }

    public function repairP3P()
    {
        $this->headers[self::HEADER_P3P] = 'CP="NOI ADM DEV COM NAV OUR STP"';
    }

    public function hideEnvironment()
    {
        $this->headers[self::HEADER_SERVER] = '';
        $this->headers[self::HEADER_X_POWERED_BY] = '';
    }

    public function setExpiration($expire)
    {
        $this->headers[self::HEADER_EXPIRES] = $expire;
    }

    public function setContentType($type)
    {
        $this->headers[self::HEADER_CONTENT_TYPE] = $type;
    }

    public function getContentType()
    {
        return $this->headers[self::HEADER_CONTENT_TYPE];
    }

    /**
     * Sends headers
     */
    public function send()
    {
        if (headers_sent()) {
            return false;
        }
        foreach ($this as $header => $value) {
            header($header . ': ' . $value);
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->headers[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->headers[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if (!isset(self::$validHeaders[$offset])) {
            return false;
        }
        $this->headers[$offset] = $value;
        return true;
    }

    public function offsetUnset($offset)
    {
        unset($this->headers[$offset]);
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        return current($this->headers);
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        return next($this->headers);
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return key($this->headers);
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        $valid = key($this->headers);
        return $valid !== null && $valid !== false;
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        reset($this->headers);
    }

    public function toArray()
    {
        return $this->headers;
    }

    private $headers = array();

    /**
     * The domain name of the server
     * @example Host: host.en
     */
    const HEADER_HOST               = 'Host';
    /**
     * What type of connection the user-agent would prefer
     * @example Connection: keep-alive
     */
    const HEADER_CONNECTION         = 'Connection';
    /**
     * The user agent string of the user agent
     * @example User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:12.0) Gecko/20100101 Firefox/12.0
     */
    const HEADER_USER_AGENT         = 'User-Agent';
    /**
     * Tells all caching mechanisms from server to client whether they may cache
     * this object. It is measured in seconds
     * @example Cache-Control: max-age=3600
     */
    const HEADER_CACHE_CONTROL      = 'Cache-Control';
    /**
     * Acceptable encodings.
     * @example Accept-Encoding: gzip, deflate
     */
    const HEADER_ACCEPT_ENCODING    = 'Accept-Encoding';
    /**
     * The language the content is in
     * @example Content-Language: en
     */
    const HEADER_ACCEPT_LANGUAGE    = 'Accept-Language';
    /**
     * Character sets that are acceptable
     * @example Accept-Charset: utf-8
     */
    const HEADER_ACCEPT_CHARSET     = 'Accept-Charset';
    /**
     * This header is supposed to set P3P policy, in the form of
     * P3P:CP="your_compact_policy".
     * @example P3P: CP="This is not a P3P policy! See http://www.google.com/support/accounts/bin/answer.py?hl=en&answer=151657 for more info."
     */
    const HEADER_P3P                = 'P3P';
    /**
     * The type of encoding used on the data. See HTTP compression.
     * @example Content-Encoding: gzip
     */
    const HEADER_CONTENT_ENCODING   = 'Content-Encoding';

    const HEADER_CONTENT_LENGTH     = 'Content-Length';
    /**
     * Gives the date/time after which the response is considered stale
     * @example Expires: Thu, 01 Dec 1994 16:00:00 GMT
     */
    const HEADER_EXPIRES            = 'Expires';
    /**
     * Specifies the technology used on server
     * @example X-Powered-By: PHP/5.4.4
     */
    const HEADER_X_POWERED_BY       = 'X-Powered-By';
    /**
     * A name for the server
     * @example Server: Apache/2.4.1 (Unix)
     */
    const HEADER_SERVER             = 'Server';
    /**
     * Used in redirection, or when a new resource has been created.
     * @example Location: http://www.w3.org/pub/WWW/People.html
     */
    const HEADER_LOCATION           = 'Location';
    /**
     * The last modified date for the requested object, in RFC 2822 format
     * @example Last-Modified: Tue, 15 Nov 1994 12:45:26 GMT
     */
    const HEADER_LAST_MODIFIED      = 'Last-Modified';
    /**
     * The date and time that the message was sent
     * @example Date: Tue, 15 Nov 1994 08:12:31 GMT
     */
    const HEADER_DATE               = 'Date';
    /**
     * The MIME type of this content
     * @example Content-Type: text/html; charset=utf-8
     */
    const HEADER_CONTENT_TYPE       = 'Content-Type';
    /**
     * Valid actions for a specified resource. To be used for a 405 Method not allowed
     * @example Allow: GET, HEAD
     */
    const HEADER_ALLOW              = 'Allow';
    /**
     * Specifying which web sites can participate in cross-origin resource sharing
     * @example Access-Control-Allow-Origin: *
     */
    const HEADER_ACCESS_CONTROL_ALLOW='Access-Control-Allow-Origin';
    /**
     * An opportunity to raise a "File Download" dialogue box for a known MIME type
     * with binary format or suggest a filename for dynamic content. Quotes are
     * necessary with special characters.
     * @example Content-Disposition: attachment; filename="fname.ext"
     */
    const HEADER_CONTENT_DISPOSITION = 'Content-Disposition';
    /**
     * Authentication credentials for HTTP authentication
     * @example Authorization: Basic QWxhZGRpbjpvcGVuIHNlc2FtZQ==
     */
    const HEADER_AUTHORIZATION = 'Authorization';
    /**
     * Authorization credentials for connecting to a proxy.
     * @example Proxy-Authorization: Basic QWxhZGRpbjpvcGVuIHNlc2FtZQ==
     */
    const HEADER_PROXY_AUTHORIZATION = 'Proxy-Authorization';
    /**
     * an HTTP cookie previously sent by the server with Set-Cookie (below)
     * @example Cookie: $Version=1; Skin=new;
     */
    const HEADER_COOKIE             = 'Cookie';
    /**
     * an HTTP cookie
     * @example Set-Cookie: UserID=JohnDoe; Max-Age=3600; Version=1
     */
    const HEADER_SET_COOKIE         = 'Set-Cookie';


    const ENCODING_GZIP             = 'gzip';
    const ENCODING_DEFLATE          = 'deflate';

    /**
     * Content types below
     */
    const CONTENT_TYPE_TEXT    = 'text/plain';
    const CONTENT_TYPE_JAVASCRIPT = 'application/x-javascript';
    const CONTENT_TYPE_JSON = 'application/json';
    const CONTENT_TYPE_RICHTEXT = 'text/richtext';
    const CONTENT_TYPE_JPEG = 'image/jpeg';
    const CONTENT_TYPE_PNG = 'image/png';
    const CONTENT_TYPE_SVG = 'image/svg+xml';
    const CONTENT_TYPE_GIF = 'image/gif';
    const CONTENT_TYPE_ZIP = 'application/zip';
    const CONTENT_TYPE_PDF = 'application/pdf';
    const CONTENT_TYPE_MP3 = 'audio/mpeg';
    const CONTENT_TYPE_BMP = 'image/bmp';
    const CONTENT_TYPE_MPEG = 'video/mpeg';

    protected static $validHeaders = array (

        self::HEADER_USER_AGENT             => 1,
        self::HEADER_ACCEPT_CHARSET         => 1,
        self::HEADER_ACCEPT_ENCODING        => 1,
        self::HEADER_ACCEPT_LANGUAGE        => 1,
        self::HEADER_ACCESS_CONTROL_ALLOW   => 1,
        self::HEADER_ALLOW                  => 1,
        self::HEADER_CACHE_CONTROL          => 1,
        self::HEADER_COOKIE                 => 1,
        self::HEADER_CONNECTION             => 1,
        self::HEADER_CONTENT_DISPOSITION    => 1,
        self::HEADER_CONTENT_ENCODING       => 1,
        self::HEADER_CONTENT_LENGTH         => 1,
        self::HEADER_CONTENT_TYPE           => 1,
        self::HEADER_DATE                   => 1,
        self::HEADER_EXPIRES                => 1,
        self::HEADER_HOST                   => 1,
        self::HEADER_LOCATION               => 1,
        self::HEADER_LAST_MODIFIED          => 1,
        self::HEADER_P3P                    => 1,
        self::HEADER_SERVER                 => 1,
        self::HEADER_SET_COOKIE             => 1,
        self::HEADER_USER_AGENT             => 1,
        self::HEADER_X_POWERED_BY           => 1,
        self::HEADER_AUTHORIZATION          => 1,
        self::HEADER_PROXY_AUTHORIZATION    => 1
    );
}
