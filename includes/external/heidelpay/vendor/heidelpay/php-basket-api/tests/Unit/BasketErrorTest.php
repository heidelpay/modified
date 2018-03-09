<?php

namespace Heidelpay\Tests\PhpBasketApi\Unit;

use Heidelpay\PhpBasketApi\BasketError;
use PHPUnit\Framework\TestCase;

/**
 * BasketError Unit Tests
 *
 * Unit tests for the BasketError object
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
class BasketErrorTest extends TestCase
{
    /**
     * @var BasketError
     */
    protected $basketError;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->basketError = new BasketError();
    }

    /**
     * Unit test for the Error Code
     */
    public function testCode()
    {
        $code = '100.300.401';

        $this->assertNull($this->basketError->getCode());

        $this->basketError->setCode($code);
        $this->assertEquals($code, $this->basketError->getCode());
    }

    /**
     * Unit test for the error message
     */
    public function testMessage()
    {
        $message = 'Invalid Sender Id';

        $this->assertNull($this->basketError->getMessage());

        $this->basketError->setMessage($message);
        $this->assertEquals($message, $this->basketError->getMessage());
    }

    public function testJsonSerializable()
    {
        $this->assertNotEmpty($this->basketError->jsonSerialize());

        $this->assertArrayHasKey('code', $this->basketError->jsonSerialize());
        $this->assertArrayHasKey('message', $this->basketError->jsonSerialize());

        $this->assertNotEmpty($this->basketError->toJson());
    }

    /**
     * Unit test for error message printing
     */
    public function testPrintMessage()
    {
        $code = '100.300.401';
        $message = 'Invalid Sender Id';

        $this->basketError->setCode($code)->setMessage($message);

        $this->assertContains($code, $this->basketError->printMessage());
        $this->assertContains($message, $this->basketError->printMessage());

        $this->assertEquals('[Errorcode 100.300.401, Message: Invalid Sender Id]', $this->basketError->printMessage());
    }
}
