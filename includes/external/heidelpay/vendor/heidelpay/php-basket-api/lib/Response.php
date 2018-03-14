<?php

namespace Heidelpay\PhpBasketApi;

use Heidelpay\PhpBasketApi\Exception\BasketException;
use Heidelpay\PhpBasketApi\Exception\InvalidBasketitemPositionException;
use Heidelpay\PhpBasketApi\Object\AbstractObject;
use Heidelpay\PhpBasketApi\Object\Basket;
use Heidelpay\PhpBasketApi\Object\BasketItem;

/**
 * Representation of the heidelpay Basket API Response
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/php-basket-api
 *
 * @author Stephano Vogel <development@heidelpay.com>
 *
 * @package heidelpay\php-basket-api\interaction\object
 */
class Response extends AbstractObject
{
    /**
     * @var string The application name
     */
    const APP_NAME = 'heidelpay Basket-API';

    /**
     * @var string ACK result code
     */
    const RESULT_ACK = 'ACK';

    /**
     * @var string NOK result code
     */
    const RESULT_NOK = 'NOK';

    /**
     * @var string API method name for adding a basket
     */
    const METHOD_ADDNEWBASKET = 'addNewBasket';

    /**
     * @var string API method name for overwriting a basket
     */
    const METHOD_OVERWRITEBASKET = 'overwriteBasket';

    /**
     * @var string API method name for getting a basket
     */
    const METHOD_GETBASKET = 'getBasket';

    /**
     * @var string Response result (either "ACK" or "NOK")
     */
    protected $result;

    /**
     * @var string The Basket called method, e.g. 'addNewBasket', 'getBasket', 'overwriteBasket'
     */
    protected $method;

    /**
     * @var string Basket Id for reference in following transactions
     */
    protected $basketId;

    /**
     * @var Basket a basket object, if present in the response
     */
    protected $basket;

    /**
     * @var BasketError[] array of response errors
     */
    protected $basketErrors = [];

    /**
     * Response constructor.
     *
     * The response should be in json format (as a string), so it can
     * be parsed correctly.
     *
     * @param string|null $content
     */
    public function __construct($content = null)
    {
        if ($content !== null && is_string($content)) {
            $this->parseResponse($content);
        }
    }

    /**
     * Returns true, if the request results in a 'ACK' (acknowledged).
     *
     * @return bool
     */
    public function isSuccess()
    {
        return $this->result === self::RESULT_ACK;
    }

    /**
     * Returns true, if the request results in a 'NOK' (not ok).
     *
     * @return bool
     */
    public function isFailure()
    {
        return $this->result === self::RESULT_NOK;
    }

    /**
     * Returns the Response result, which is either 'ACK' or 'NOK'.
     *
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param string $result
     *
     * @return $this
     */
    protected function setResult($result)
    {
        $this->result = $result;
        return $this;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $method
     *
     * @return $this
     */
    protected function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @return string
     */
    public function getBasketId()
    {
        return $this->basketId;
    }

    /**
     * @param string $basketId
     *
     * @return $this
     */
    protected function setBasketId($basketId)
    {
        $this->basketId = $basketId;
        return $this;
    }

    /**
     * @return Basket
     */
    public function getBasket()
    {
        return $this->basket;
    }

    /**
     * @param Basket $basket
     *
     * @return $this
     */
    protected function setBasket(Basket $basket)
    {
        $this->basket = $basket;
        return $this;
    }

    /**
     * @return BasketError[]
     */
    public function getBasketErrors()
    {
        return $this->basketErrors;
    }

    /**
     * @param BasketError $basketError
     *
     * @return $this
     */
    private function addBasketError(BasketError $basketError)
    {
        $this->basketErrors[] = $basketError;
        return $this;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'result' => $this->result,
            'method' => $this->method,
            'basket' => $this->basket,
            'basketId' => $this->basketId,
            'basketErrors' => array_values($this->basketErrors)
        ];
    }

    /**
     * Prints a formatted message of the Response, including the basket errors.
     *
     * @return string
     */
    public function printMessage()
    {
        $messages = [];

        foreach ($this->basketErrors as $basketError) {
            $messages[] = $basketError->printMessage();
        }

        if ($this->isSuccess()) {
            return sprintf('%s - %s Request SUCCESS. %s', self::APP_NAME, $this->method, implode(', ', $messages));
        }

        return sprintf('%s - %s Request FAILURE. %s', self::APP_NAME, $this->method, implode(', ', $messages));
    }

    /**
     * Parses a raw json response into a instance of this class.
     *
     * @param string $response a raw json response from a cURL request
     *
     * @throws BasketException
     */
    private function parseResponse($response)
    {
        /** @var \stdClass $obj */
        // if the json cannot be parsed, do nothing.
        if (!$obj = json_decode($response)) {
            return;
        }

        $this->setResponseParameters($obj);

        if (isset($obj->basket)) {
            // instanciate a new Basket
            $basket = new Basket();

            // go through all properties of the parsed object and
            // set the Basket's properties by their values.
            $this->setBasketProperties($basket, $obj);

            // iterate through the basket items.
            if (isset($obj->basket->basketItems) && !empty($obj->basket->basketItems)) {
                sort($obj->basket->basketItems);
                $this->setBasketItemProperties($basket, $obj->basket->basketItems);
            }

            $this->setBasket($basket);

            if (isset($obj->basket->itemCount) && $this->basket->getItemCount() !== $obj->basket->itemCount) {
                throw new BasketException(
                    'Itemcount ' . $this->basket->getItemCount() . ' does not match ' . $obj->basket->itemCount . '!'
                );
            }
        }

        // iterate through all basket errors, create object instances
        // of them and add them to the basketErrors array.
        if (isset($obj->basketErrors) && is_array($obj->basketErrors)) {
            $this->setBasketErrors($obj->basketErrors);
        }
    }

    /**
     * Returns if the provided BasketItem uses Marketplace properties (that are not null)
     *
     * @param BasketItem $basketItem
     *
     * @return bool
     */
    private function itemHasMarketplaceProperties(BasketItem $basketItem)
    {
        return $basketItem->getChannel() !== null
            || $basketItem->getCommissionRate() !== null
            || $basketItem->getTransactionId() !== null
            || $basketItem->getUsage() !== null;
    }

    /**
     * Sets response parameters (result, request method &basket id)
     *
     * @param \stdClass $obj
     */
    private function setResponseParameters(\stdClass $obj)
    {
        if (isset($obj->result)) {
            $this->setResult($obj->result);
        }

        if (isset($obj->method)) {
            $this->setMethod($obj->method);
        }

        if (isset($obj->basketId)) {
            $this->setBasketId($obj->basketId);
        }
    }

    /**
     * Sets the BasketErrors for the Reponse instance.
     *
     * @param array $basketErrors
     */
    private function setBasketErrors($basketErrors)
    {
        foreach ($basketErrors as $basketError) {
            $objErr = new BasketError();

            if (isset($basketError->code)) {
                $objErr->setCode($basketError->code);
            }

            if (isset($basketError->message)) {
                $objErr->setMessage($basketError->message);
            }

            $this->addBasketError($objErr);
        }
    }

    /**
     * Sets the Basket's properties.
     *
     * @param Basket    $basket
     * @param \stdClass $obj
     */
    private function setBasketProperties(Basket $basket, \stdClass $obj)
    {
        foreach (get_object_vars($obj->basket) as $class_var => $value) {
            if ($class_var !== 'basketItems') {
                $basket->$class_var = $value;
            }
        }
    }

    /**
     * @param Basket $basket
     * @param array  $basketItems
     *
     * @throws BasketException
     */
    private function setBasketItemProperties(Basket $basket, array $basketItems)
    {
        foreach ($basketItems as $basketItem) {
            $item = new BasketItem();

            foreach (get_object_vars($basketItem) as $class_var => $value) {
                $item->$class_var = $value;
            }

            // if marketplace parameters are provided, set the BasketItem's
            // isMarketplaceItem property to 'true'.
            if ($this->itemHasMarketplaceProperties($item)) {
                $item->setIsMarketplaceItem();
            }

            try {
                $basket->addBasketItem($item, $item->getPosition());
            } catch (InvalidBasketitemPositionException $e) {
                throw new BasketException('Could not add BasketItem to Basket during parsing!');
            }
        }
    }
}
