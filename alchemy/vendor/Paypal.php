<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\vendor;

class PayPalException extends \Exception {}

/**
 * Paypal express checkout helper class
 */
class PayPal
{

    /**
     * Constructor
     *
     * @param $username paypal's username
     * @param $password paypal's password
     * @param $signature paypal's signature
     */
    public function __construct($username, $password, $signature)
    {
        $this->user = $username;
        $this->password = $password;
        $this->signature = $signature;
    }

    /**
     * Sets paypal in sandbox mode
     *
     * @param bool $sandbox
     */
    public static function setSandbox($sandbox = true)
    {
        self::$sandbox = $sandbox;
    }

    /**
     * Sets payment currency
     * @param string $currency currency code e.g. USD
     */
    public function setPaymentCurrency($currency = 'USD')
    {
        $this->currency = $currency;
    }

    /**
     * Gets currency code
     * @return string
     */
    public function getPaymentCurrency()
    {
        return $this->currency;
    }

    /**
     * Adds payment request
     *
     * @param paypal\PaymentRequest $request
     */
    public function addPaymentRequest(paypal\PaymentRequest $request)
    {
        $request->_setPayPal($this);
        $this->paymentRequests[] = $request;
    }

    /**
     * Gets express checkout token
     *
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Generates express checkout token
     *
     * @param $returnURL url where user will be redirected if payment was successfull
     * @param $cancelURL url where user will be redirected if payment was unsuccessfull
     * @return string token if payment was successfull
     * @throws PayPalException
     */
    public function generateExpressCheckoutToken($returnURL, $cancelURL)
    {
        if (empty($this->paymentRequests)) throw new PayPalException('You must call ' . __CLASS__ . "::addPaymentRequest before generating express checkout token");

        $checkoutData = array(
            'RETURNURL' => $returnURL,
            'CANCELURL' => $cancelURL
        );

        foreach ($this->paymentRequests as $p)
        {
            $checkoutData = array_merge($checkoutData, $p->serialize());
        }

        $response = $this->doRequest('SetExpressCheckout', $checkoutData);

        if ($response['ACK'] != 'Success')
        {
            throw new PayPalException($response['L_SHORTMESSAGE0'] . PHP_EOL . $response['L_LONGMESSAGE0'], $response['L_ERRORCODE0']);
        }
        return $this->token = $response['TOKEN'];
    }

    /**
     * Gets express checkout url for the transaction
     *
     * @param $returnURL url where user will be redirected if payment was successfull
     * @param $cancelURL url where user will be redirected if payment was unsuccessfull
     * @param bool $commit commits the payment
     * @return string url for express checkout
     */
    public function getExpressCheckoutURL($returnURL, $cancelURL, $commit = true)
    {
        $token = $this->getToken();
        if (!$token) $token = $this->generateExpressCheckoutToken($returnURL, $cancelURL);
        $url = self::$sandbox ? self::SANDBOX_URL : self::PRODUCTION_URL;
        return $url . ($commit ? '&useraction=commit' : '') . '&token=' . $token;
    }

    /**
     * Gets payment checkout details
     *
     * @param $token
     * @return mixed
     * @throws PayPalException
     */
    public function getExpressCheckoutDetails($token)
    {
        $data = array(
            'TOKEN' => $token
        );
        $response = $this->doRequest('GetExpressCheckoutDetails', $data);
        if ($response['ACK'] != 'Success')
        {
            throw new PayPalException('Invalid token or paypal timeout');
        }
        return $response;
    }

    public function doExpressCheckoutPayment($token)
    {
        $checkoutDetails = $this->getExpressCheckoutDetails($token);

        if (!array_key_exists('PAYERID', $checkoutDetails)) return false;

        $paymentData = array(
            'TOKEN'     => $token,
            'PAYERID'   => $checkoutDetails['PAYERID'],

        );

        //get complete data
        foreach ($checkoutDetails as $key => $value)
        {
            if (preg_match('#PAYMENTREQUEST_\d+_AMT#is', $key)) $paymentData[$key] = $value;
            else if (preg_match('#PAYMENTREQUEST_\d+_CURRENCYCODE#is', $key)) $paymentData[$key] = $value;
        }
        $paymentData['PAYMENTREQUEST_0_PAYMENTACTION'] = 'Sale';

        $response = $this->doRequest('DoExpressCheckoutPayment', $paymentData);

        if ($response['ACK'] == 'Success') return $response;
        return false;
    }

    protected function doRequest($method, $data)
    {
        $data = array_merge($data, array(
            'METHOD' => $method,
            'VERSION' => '86.0',
            'PWD' => $this->password,
            'USER' => $this->user,
            'SIGNATURE' => $this->signature,
        ));

        $post = '';
        foreach ($data as $n => $d)
        {
            $post .= '&' . $n . '=' . urlencode($d);
        }

        $gateway = self::$sandbox ? self::SANDBOX_API_GATEWAY : self::PRODUCTION_API_GATEWAY;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $gateway);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $httpResponse = curl_exec($ch);

        parse_str($httpResponse, $response);

        return $response;
    }

    private $user;
    private $password;
    private $signature;
    private $currency = 'USD';
    private $totalCost = 0.00;
    private static $sandbox = false;
    private $paymentRequests = array();
    private $token;

    const SANDBOX_URL = 'https://www.sandbox.paypal.com/webscr?cmd=_express-checkout';
    const PRODUCTION_URL = 'https://www.paypal.com/webscr&cmd=_express-checkout';
    const PRODUCTION_API_GATEWAY = 'https://api-3t.paypal.com/nvp';
    const SANDBOX_API_GATEWAY = 'https://api-3t.sandbox.paypal.com/nvp';

}