<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\vendor\paypal;

class PaymentRequest
{
    public function __construct($value, $desc, $paymentId)
    {
        $this->requestId = self::$paymentId++;

        $this->value = $value;
        $this->desc = $desc;
        $this->id = $paymentId;
    }


    public function setShippingAddress($name, $street, $city, $zip, $state = null, $country = 'USA')
    {
        $this->address = array(
            'SHIPTONAME'            => $name,
            'SHIPTOSTREET'          => $street,
            'SHIPTOCITY'            => $city,
            'SHIPTOZIP'             => $zip,
            'SHIPTOSTATE'           => $state,
            'SHIPTOCOUNTRYCODE'     => $country,
            'ADDROVERRIDE'          => 1
        );
    }

    /**
     * Sets PayPal class
     * Package's method
     *
     * @param \alchemy\vendor\PayPal $p
     */
    public function _setPayPal(\alchemy\vendor\PayPal $p)
    {
        $this->paypalObject = $p;
    }

    public function addItem($value, $name, $qty = 1, $id = null, $desc = null)
    {
        $requestId = 'L_PAYMENTREQUEST_' . $this->requestId . '_';
        $itemId = $this->itemId++;
        $value = number_format( (float)$value, 2);
        $this->itemTotalValue += $value * $qty;
        $this->items[] = array(
            $requestId . 'NAME'     . $itemId   => $name,
            $requestId . 'AMT'      . $itemId   => $value,
            $requestId . 'QTY'      . $itemId   => $qty,
            $requestId . 'NUMBER'   . $itemId   => $id,
            $requestId . 'DESC'     . $itemId   => $desc
        );
    }

    public function serialize()
    {
        $result = array(

            'PAYMENTREQUEST_' . $this->requestId . '_AMT' => &$this->value,
            'PAYMENTREQUEST_' . $this->requestId . '_DESC' => $this->desc,
            'PAYMENTREQUEST_' . $this->requestId . '_CURRENCYCODE' => $this->paypalObject->getPaymentCurrency(),
            'PAYMENTREQUEST_' . $this->requestId . '_PAYMENTACTION' => 'Sale'
        );

        if (!empty($this->address)) $result = array_merge($result, $this->address);
        else $result['NOSHIPPING'] = 1;

        //parse items if exists
        if (empty($this->items)) return $result;
        $this->value = $this->itemTotalValue;
        foreach ($this->items as $i)
        {
            $result = array_merge($result, $i);
        }

        return $result;

    }

    private $address = array();

    private $paypalObject;
    protected $desc;
    protected $value;
    protected $id;
    protected $itemTotalValue = 0;
    protected $items = array();
    private $itemId = 0;
    private $requestId;
    protected static $paymentId = 0;
}