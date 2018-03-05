<?php

namespace Heidelpay\Tests\PhpBasketApi\Unit;

use Heidelpay\PhpBasketApi\Exception\BasketException;
use Heidelpay\PhpBasketApi\Response;
use PHPUnit\Framework\TestCase;

/**
 * Response Unit Tests
 *
 * Unit tests for the Response Class.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/php-basket-api
 *
 * @author Stephano Vogel <development@heidelpay.com>
 *
 * @package heidelpay\php-basket-api\tests\unit
 */
class ResponseTest extends TestCase
{
    /**
     * Unit test for JSON
     */
    public function testJsonSerializable()
    {
        $response = new Response();

        $this->assertNotEmpty($response->jsonSerialize());

        $this->assertArrayHasKey('result', $response->jsonSerialize());
        $this->assertArrayHasKey('method', $response->jsonSerialize());
        $this->assertArrayHasKey('basket', $response->jsonSerialize());
        $this->assertArrayHasKey('basketId', $response->jsonSerialize());
        $this->assertArrayHasKey('basketErrors', $response->jsonSerialize());

        $this->assertNotEmpty($response->toJson());
    }

    /**
     * Unit test for parsing an invalid json response
     */
    public function testInvalidJsonResponse()
    {
        $jsonResponse = '{test:"test",}';
        $response = new Response($jsonResponse);

        $this->assertNull($response->getResult());
        $this->assertNull($response->getMethod());
        $this->assertNull($response->getBasket());
        $this->assertNull($response->getBasketId());
        $this->assertEmpty($response->getBasketErrors());
    }

    /**
     * Unit test for parsing a json response with wrong itemcount provided
     */
    public function testInvalidItemCountJsonResponse()
    {
        $jsonResponse = '{"basket":{"itemCount": 2, "basketItems": [{}]}}';

        $this->expectException(BasketException::class);
        $response = new Response($jsonResponse);
        $this->assertNull($response->getBasketId());
    }
}
