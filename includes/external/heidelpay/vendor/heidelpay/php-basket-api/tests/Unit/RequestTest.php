<?php

namespace Heidelpay\Tests\PhpBasketApi\Unit;

use Heidelpay\PhpBasketApi\Object\Authentication;
use Heidelpay\PhpBasketApi\Request;
use PHPUnit\Framework\TestCase;

/**
 * Request Unit Tests
 *
 * Unit tests for the Request object
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/php-basket-api
 *
 * @author Stephano Vogel <development@heidelpay.com>
 *
 * @package heidelpay\php-basket-api\test\unit
 */
class RequestTest extends TestCase
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->request = new Request();
    }

    /**
     * Unit test for the sandbox mode
     */
    public function testSandboxMode()
    {
        $this->assertTrue($this->request->isSandboxMode());

        $this->request->setIsSandboxMode(false);
        $this->assertFalse($this->request->isSandboxMode());
    }

    /**
     * Unit test for the Authentication
     */
    public function testAuthentication()
    {
        $login = 'login';
        $password = 'password';
        $sender = 'sender';

        $this->assertNull($this->request->getAuthentication()->getLogin());
        $this->assertNull($this->request->getAuthentication()->getPassword());
        $this->assertNull($this->request->getAuthentication()->getSender());

        $this->request->setAuthentication($login, $password, $sender);

        $this->assertEquals($login, $this->request->getAuthentication()->getLogin());
        $this->assertEquals($password, $this->request->getAuthentication()->getPassword());
        $this->assertEquals($sender, $this->request->getAuthentication()->getSender());
    }

    public function testBasket()
    {
        $this->assertNotNull($this->request->getBasket());
    }
}
