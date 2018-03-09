<?php

namespace Heidelpay\PhpBasketApi;

use Heidelpay\PhpBasketApi\Object\AbstractObject;

/**
 * heidelpay BasketError
 *
 * BasketError object representation for the heidelpay Basket API
 *
 * @version 1.2
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/php-basket-api
 *
 * @author Stephano Vogel <development@heidelpay.com>
 *
 * @package heidelpay\php-basket-api\Interaction\Object
 */
class BasketError extends AbstractObject
{
    /**
     * @var string $code The Error code
     */
    protected $code;

    /**
     * @var string $message The Error message
     */
    protected $message;

    /**
     * Returns the Error code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Returns the Error message.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Sets the error code.
     *
     * @param string $code
     *
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Sets the error code.
     *
     * @param string $message
     *
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Returns an array that is used for the JSON representation when using json_encode or toJson().
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'code' => $this->code,
            'message' => $this->message
        ];
    }

    /**
     * Prints the error message that the BasketError instance is representing.
     *
     * @return string
     */
    public function printMessage()
    {
        return sprintf('[Errorcode %s, Message: %s]', $this->code, $this->message);
    }
}
