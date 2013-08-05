<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\vendor;

class DropboxException extends \Exception {}

/**
 * Dropbox PHP Core API
 */
class Dropbox
{
    public function __construct($key = 'pwgvypkmmjat3jz', $secret = 'w2b7en2xwlfrmoo', $namespace = 'dropbox')
    {

        $this->key = $key;
        $this->secret = $secret;
        $this->requestTokenSecret = &$_SESSION[$namespace . '_oauth_request_secret'];
        $this->requestToken = &$_SESSION[$namespace . '_oauth_request_token'];
        $this->oauthToken = &$_SESSION[$namespace . '_oauth_token'];
        $this->oauthTokenSecret = &$_SESSION[$namespace . '_oauth_secret'];
    }

    public function forget()
    {
        $this->requestToken = null;
        $this->requestTokenSecret = null;
        $this->oauthToken = null;
        $this->oauthTokenSecret = null;
    }
    public function getAuthorizationURL($callback = 'http://lotos/lotos/projects/edit/1')
    {
        if (!$this->requestToken || !$this->requestTokenSecret) {
            $token = $this->doRequest('/oauth/request_token');
            $this->requestTokenSecret = $token['oauth_token_secret'];
            $this->requestToken = $token['oauth_token'];
        }
        return self::DB_GATEWAY . '/authorize?oauth_token=' . $this->requestToken . '&oauth_callback=' . $callback;
    }

    public function authorize($oauthToken, $oauthTokenSecret)
    {
        $this->oauthToken = $oauthToken;
        $this->oauthTokenSecret = $oauthTokenSecret;
    }

    public function isAuthorized()
    {
        if ($this->oauthTokenSecret && $this->oauthToken)
        {
            return true;
        }
        return false;
    }

    public function getAccountInfo()
    {
        $data = $this->doRequest('/account/info');
        if ($this->reponseCode != 200) {
            $this->forget();
            $error = json_decode($this->response, true);
            throw new DropboxException($error['error'], $this->reponseCode);
        }
        return $data;
    }

    public function getAccessToken($requestToken = null)
    {
        if ($this->isAuthorized() && !$requestToken) {
            return true;
        }

        if (!$requestToken) {
            if (isset($_GET['oauth_token'])) {
                $requestToken = $_GET['oauth_token'];
            } else {
                throw new DropboxException('Please authorize user first by Dropbox->getAuthorizationURL');
            }
        }

        $this->oauthToken = $requestToken;
        $response = $this->doRequest('/oauth/access_token');
        $this->oauthTokenSecret = $response['oauth_token_secret'];
        $this->oauthToken = $response['oauth_token'];

    }


    protected function doRequest($url, $data = array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::API_GATEWAY . $url);
        $headers = $this->getHeaders();
        print_r($headers);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        //curl_setopt($ch, CURLOPT_CON)
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array());
        $httpResponse = curl_exec($ch);

        $info = curl_getinfo($ch);
        $this->reponseCode = $info['http_code'];
        $this->response = $httpResponse;
        curl_close($ch);
        parse_str($httpResponse, $response);

        return $response;
    }

    private function getHeaders()
    {
        return array(
            'Authorization: OAuth oauth_version="1.0", oauth_signature_method="PLAINTEXT",' .
                ' oauth_consumer_key="' . $this->key . '"' . (
                    $this->oauthToken ? ', oauth_token="' . $this->oauthToken . '"' : (
                        $this->requestToken ? ', oauth_token="' . $this->requestToken . '"' : ''
                    )
                ) .
                ', oauth_signature="' . $this->secret . '&' . (
                    $this->oauthToken ? $this->oauthTokenSecret : (
                        $this->requestTokenSecret ? $this->requestTokenSecret : ''
                    )
                ) . '"',
            'Content-Type: multipart/form-data;'
        );
    }

    const API_GATEWAY = 'https://api.dropbox.com/1';
    const DB_GATEWAY  = 'https://www.dropbox.com/1/oauth';

    protected $key = '';
    protected $secret = '';
    protected $requestTokenSecret;
    protected $requestToken;
    protected $oauthToken;
    protected $oauthTokenSecret;

    private $reponseCode;
    private $response;
}