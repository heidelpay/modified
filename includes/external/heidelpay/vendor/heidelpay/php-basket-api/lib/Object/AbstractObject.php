<?php

namespace Heidelpay\PhpBasketApi\Object;

use JsonSerializable;

/**
 * AbstractObject Class
 *
 * Abstract Class for all object implementations of the heidelpay PHP Basket API implementation
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/php-basket-api
 *
 * @author Stephano Vogel <development@heidelpay.com>
 *
 * @package heidelpay\php-basket-api\Object
 */
abstract class AbstractObject implements JsonSerializable
{
    /**
     * Returns a Json representation of the object instance.
     *
     * @param int $options json_encode options
     *
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }
}
