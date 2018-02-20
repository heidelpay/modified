<?php

namespace Heidelpay\Tests\PhpBasketApi\Integration;

use Heidelpay\PhpBasketApi\Object\Authentication;
use Heidelpay\PhpBasketApi\Object\Basket;
use Heidelpay\PhpBasketApi\Object\BasketItem;
use Heidelpay\PhpBasketApi\Request;
use Heidelpay\PhpBasketApi\Response;
use PHPUnit\Framework\TestCase;

/**
 * Integration Testsuite for all requests to the API
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/php-basket-api
 *
 * @author Stephano Vogel <development@heidelpay.de>
 *
 * @package heidelpay\php-basket-api\tests\integration
 */
class RequestTest extends TestCase
{
    /**
     * @var string constant for test login user
     */
    const AUTH_LOGIN = '31ha07bc8142c5a171744e5aef11ffd3';

    /**
     * @var string constant for test login password
     */
    const AUTH_PASSWORD = '93167DE7';

    /**
     * @var string constant for test sender id
     */
    const AUTH_SENDER_ID = '31HA07BC8142C5A171745D00AD63D182';

    /**
     * @var Authentication the authentication object for all requests
     */
    protected $auth;

    /**
     * @var Basket the basket object for all requests
     */
    protected $basket;

    /**
     * Set up the fixture objects for all requests.
     */
    public function setUp()
    {
        // set the Authentication
        $this->auth = new Authentication(self::AUTH_LOGIN, self::AUTH_PASSWORD, self::AUTH_SENDER_ID);

        // set up a basket
        $this->basket = new Basket();
        $this->basket->setAmountTotalNet(8192);
        $this->basket->setAmountTotalVat(1557);
        $this->basket->setAmountTotalDiscount(0);
        $this->basket->setCurrencyCode('EUR');
        $this->basket->setBasketReferenceId('heidelpay-php-basket-api-integration-test');
        $this->basket->setNote('heidelpay php-basket-api test basket');

        // set up a first basket item
        $basketItemOne = new BasketItem();
        $basketItemOne->setPosition(1);
        $basketItemOne->setBasketItemReferenceId('heidelpay-php-basket-api-testitem-1');
        $basketItemOne->setUnit('Stk.');
        $basketItemOne->setArticleId('heidelpay-testitem-1');
        $basketItemOne->setTitle('Heidelpay Test Article #1');
        $basketItemOne->setDescription('Just for testing.');
        $basketItemOne->setType('goods');
        $basketItemOne->setImageUrl('https://placehold.it/223302316.jpg');
        $basketItemOne->setQuantity(1);
        $basketItemOne->setVat(19);
        $basketItemOne->setAmountPerUnit(1000);
        $basketItemOne->setAmountNet(840);
        $basketItemOne->setAmountGross(1000);
        $basketItemOne->setAmountVat(160);
        $basketItemOne->setAmountDiscount(0);

        // set up a second basket item
        $basketItemTwo = new BasketItem();
        $basketItemTwo->setPosition(2);
        $basketItemTwo->setBasketItemReferenceId('heidelpay-php-basket-api-testitem-2');
        $basketItemTwo->setUnit('Stk.');
        $basketItemTwo->setArticleId('heidelpay-testitem-2');
        $basketItemTwo->setTitle('Heidelpay Test Article #2');
        $basketItemTwo->setDescription('Just for testing.');
        $basketItemTwo->setType('goods');
        $basketItemTwo->setImageUrl('https://placehold.it/236566083.jpg');
        $basketItemTwo->setQuantity(1);
        $basketItemTwo->setVat(19);
        $basketItemTwo->setAmountPerUnit(7999);
        $basketItemTwo->setAmountNet(6722);
        $basketItemTwo->setAmountGross(7999);
        $basketItemTwo->setAmountVat(1277);
        $basketItemTwo->setAmountDiscount(0);

        // set up a third basket item (shipping)
        $basketItemThree = new BasketItem();
        $basketItemThree->setPosition(3);
        $basketItemThree->setBasketItemReferenceId('heidelpay-php-basket-api-testitem-3');
        $basketItemThree->setUnit('Stk.');
        $basketItemThree->setArticleId('heidelpay-testitem-3');
        $basketItemThree->setTitle('Heidelpay Test Article #3');
        $basketItemThree->setDescription('Just for testing.');
        $basketItemThree->setType('goods');
        $basketItemThree->setQuantity(1);
        $basketItemThree->setVat(19);
        $basketItemThree->setAmountPerUnit(750);
        $basketItemThree->setAmountNet(630);
        $basketItemThree->setAmountGross(750);
        $basketItemThree->setAmountVat(120);
        $basketItemThree->setAmountDiscount(0);

        $this->basket->addBasketItem($basketItemOne);
        $this->basket->addBasketItem($basketItemTwo);
        $this->basket->addBasketItem($basketItemThree);
    }

    /**
     * @return string
     */
    public function testAddNewBasket()
    {
        $request = new Request($this->auth, $this->basket);
        $response = $request->addNewBasket();

        $this->assertEquals(Response::RESULT_ACK, $response->getResult());
        $this->assertEquals(Response::METHOD_ADDNEWBASKET, $response->getMethod());

        $this->assertContains('SUCCESS', $response->printMessage());
        $this->assertContains(Response::METHOD_ADDNEWBASKET, $response->printMessage());

        $this->assertNotNull($response->getBasketId(), 'BasketId is null.');
        $this->assertNotNull($response->getResult(), 'Result is null.');
        $this->assertNotNull($response->getMethod(), 'Method is null.');
        $this->assertTrue($response->isSuccess(), 'Response is not success.');
        $this->assertFalse($response->isFailure(), 'Response is failure.');

        return $response->getBasketId();
    }

    /**
     * Tests if the basket that was just submitted can be retrieved by it's id.
     *
     * @depends testAddNewBasket
     *
     * @param string $basketId
     *
     * @return Response
     */
    public function testRetrieveBasket($basketId)
    {
        $request = new Request($this->auth);
        $response = $request->retrieveBasket($basketId);

        // test, if Response matches expected values
        $this->assertEquals(Response::RESULT_ACK, $response->getResult());
        $this->assertEquals(Response::METHOD_GETBASKET, $response->getMethod());
        $this->assertEquals($basketId, $response->getBasketId());

        $this->assertContains('SUCCESS', $response->printMessage());
        $this->assertContains(Response::METHOD_GETBASKET, $response->printMessage());

        // test, if the basket contents are the same as requested first.
        $this->assertEquals($this->basket->getBasketReferenceId(), $response->getBasket()->getBasketReferenceId());
        $this->assertEquals($this->basket->getAmountTotalNet(), $response->getBasket()->getAmountTotalNet());
        $this->assertEquals($this->basket->getAmountTotalVat(), $response->getBasket()->getAmountTotalVat());
        $this->assertEquals($this->basket->getAmountTotalDiscount(), $response->getBasket()->getAmountTotalDiscount());
        $this->assertEquals($this->basket->getCurrencyCode(), $response->getBasket()->getCurrencyCode());
        $this->assertEquals($this->basket->getNote(), $response->getBasket()->getNote());
        $this->assertEquals($this->basket->getItemCount(), $response->getBasket()->getItemCount());
        $this->assertEquals($this->basket->getBasketItems(), $response->getBasket()->getBasketItems());
        $this->assertEquals(
            $this->basket->getBasketItemByPosition(1),
            $response->getBasket()->getBasketItemByPosition(1)
        );
        $this->assertEquals(
            $this->basket->getBasketItemByPosition(2),
            $response->getBasket()->getBasketItemByPosition(2)
        );
        $this->assertEquals(
            $this->basket->getBasketItemByPosition(3),
            $response->getBasket()->getBasketItemByPosition(3)
        );

        // return for the overwriteBasket test.
        return $response;
    }

    /**
     * @depends testRetrieveBasket
     *
     * @param Response $apiResponse
     *
     * @return string
     */
    public function testOverwriteBasketByChangeAmounts(Response $apiResponse)
    {
        $request = new Request($this->auth);
        $request->setBasket($apiResponse->getBasket());

        // we change the amounts (gross, net, vat) of BasketItem #3, e.g. to emulate changed shipping fees
        $request->getBasket()->getBasketItemByPosition(3)->setAmountGross(1000);
        $request->getBasket()->getBasketItemByPosition(3)->setAmountNet(800);
        $request->getBasket()->getBasketItemByPosition(3)->setAmountVat(200);
        $request->getBasket()->getBasketItemByPosition(3)->setVat(20);

        // do the overwrite request.
        $response = $request->overwriteBasket($apiResponse->getBasketId());

        // confirm the request was successful.
        $this->assertTrue($response->isSuccess());
        $this->assertEquals(Response::RESULT_ACK, $response->getResult());
        $this->assertEquals(Response::METHOD_OVERWRITEBASKET, $response->getMethod());

        $this->assertContains('SUCCESS', $response->printMessage());
        $this->assertContains(Response::METHOD_OVERWRITEBASKET, $response->printMessage());

        // confirm that no basket was returned and the basket id is still the same.
        $this->assertEquals($apiResponse->getBasketId(), $response->getBasketId());
        $this->assertNull($response->getBasket());

        // return the basketId for the comparison test.
        return $response->getBasketId();
    }

    /**
     * @depends testOverwriteBasketByChangeAmounts
     *
     * @param string $basketId
     *
     * @return string
     */
    public function testOverwrittenBasketByChangeAmountsComparison($basketId)
    {
        $request = new Request($this->auth);
        $response = $request->retrieveBasket($basketId);

        $this->assertContains('SUCCESS', $response->printMessage());
        $this->assertContains(Response::METHOD_GETBASKET, $response->printMessage());

        // compare the original basket with the overwritten one (which we just loaded again via api call)
        $this->assertNotEquals(
            $this->basket->getBasketItemByPosition(3)->getAmountGross(),
            $response->getBasket()->getBasketItemByPosition(3)->getAmountGross()
        );
        $this->assertNotEquals(
            $this->basket->getBasketItemByPosition(3)->getAmountNet(),
            $response->getBasket()->getBasketItemByPosition(3)->getAmountNet()
        );
        $this->assertNotEquals(
            $this->basket->getBasketItemByPosition(3)->getAmountVat(),
            $response->getBasket()->getBasketItemByPosition(3)->getAmountVat()
        );
        $this->assertNotEquals(
            $this->basket->getBasketItemByPosition(3)->getVat(),
            $response->getBasket()->getBasketItemByPosition(3)->getVat()
        );
        $this->assertEquals(
            $this->basket->getBasketItemByPosition(3)->getArticleId(),
            $response->getBasket()->getBasketItemByPosition(3)->getArticleId()
        );

        return $response->getBasketId();
    }

    /**
     * @depends testOverwriteBasketByChangeAmounts
     *
     * @param string $basketId
     *
     * @return string
     */
    public function testOverwriteBasketByRemovingAPosition($basketId)
    {
        $request = new Request($this->auth);
        $response = $request->retrieveBasket($basketId);

        $this->assertContains('SUCCESS', $response->printMessage());
        $this->assertContains(Response::METHOD_GETBASKET, $response->printMessage());

        // remove the item on position 3.
        $response->getBasket()->deleteBasketItemByPosition(3);

        // set the basket in the request to the changed one.
        $request->setBasket($response->getBasket());
        $response = $request->overwriteBasket($response->getBasketId());

        // confirm the request was successful.
        $this->assertTrue($response->isSuccess());
        $this->assertEquals(Response::RESULT_ACK, $response->getResult());
        $this->assertEquals(Response::METHOD_OVERWRITEBASKET, $response->getMethod());

        $this->assertContains('SUCCESS', $response->printMessage());
        $this->assertContains(Response::METHOD_OVERWRITEBASKET, $response->printMessage());

        // confirm that no basket was returned and the basket id is still the same.
        $this->assertEquals($basketId, $response->getBasketId());
        $this->assertNull($response->getBasket());

        return $basketId;
    }

    /**
     * @depends testOverwriteBasketByRemovingAPosition
     *
     * @param string $basketId
     */
    public function testOverwrittenBasketByRemovingAPositionComparison($basketId)
    {
        $request = new Request($this->auth);
        $response = $request->retrieveBasket($basketId);

        $this->assertContains('SUCCESS', $response->printMessage());
        $this->assertContains(Response::METHOD_GETBASKET, $response->printMessage());

        // compare the original basket with the overwritten one (which we just loaded again via api call)
        $this->assertEquals(2, $response->getBasket()->getItemCount());
    }

    /**
     * @return string
     */
    public function testAddBasketWithMarketplaceBasketItemProperties()
    {
        // set properties that are meant to be used by Marketplaces
        $this->basket->getBasketItemByPosition(1)->setCommissionRate(5.00);
        $this->basket->getBasketItemByPosition(1)->setTransactionId('heidelpay-php-basket-api-test-1');
        $this->basket->getBasketItemByPosition(1)->setUsage('Marketplace Test');
        $this->basket->getBasketItemByPosition(1)->setIsMarketplaceItem();

        $request = new Request($this->auth, $this->basket);
        $response = $request->addNewBasket();

        $this->assertEquals(Response::RESULT_ACK, $response->getResult());
        $this->assertEquals(Response::METHOD_ADDNEWBASKET, $response->getMethod());

        $this->assertContains('SUCCESS', $response->printMessage());
        $this->assertContains(Response::METHOD_ADDNEWBASKET, $response->printMessage());

        $this->assertNotNull($response->getBasketId(), 'BasketId is null.');
        $this->assertNotNull($response->getResult(), 'Result is null.');
        $this->assertNotNull($response->getMethod(), 'Method is null.');
        $this->assertTrue($response->isSuccess(), 'Response is not success.');
        $this->assertFalse($response->isFailure(), 'Response is failure.');

        return $response->getBasketId();
    }

    /**
     * @depends testAddBasketWithMarketplaceBasketItemProperties
     *
     * @param string $basketId
     */
    public function testRetrieveBasketWithMarketplaceBasketItemProperties($basketId)
    {
        $request = new Request($this->auth);
        $response = $request->retrieveBasket($basketId);

        // test, if Response matches expected values
        $this->assertEquals(Response::RESULT_ACK, $response->getResult());
        $this->assertEquals(Response::METHOD_GETBASKET, $response->getMethod());
        $this->assertEquals($basketId, $response->getBasketId());

        $this->assertContains('SUCCESS', $response->printMessage());
        $this->assertContains(Response::METHOD_GETBASKET, $response->printMessage());

        $this->assertNotNull($response->getBasket()->getBasketItemByPosition(1)->getCommissionRate());
        $this->assertNotNull($response->getBasket()->getBasketItemByPosition(1)->getTransactionId());
        $this->assertNotNull($response->getBasket()->getBasketItemByPosition(1)->getUsage());
        $this->assertTrue($response->getBasket()->getBasketItemByPosition(1)->isMarketplaceItem());
    }

    /**
     * Test for invalid authentication data
     */
    public function testInvalidAuthenticationData()
    {
        $request = new Request();

        // set invalid auth data.
        $request->setAuthentication('invalid', self::AUTH_PASSWORD, self::AUTH_SENDER_ID);
        $request->setBasket($this->basket);

        $response = $request->addNewBasket();

        $this->assertEquals(Response::RESULT_NOK, $response->getResult());
        $this->assertEquals(Response::METHOD_ADDNEWBASKET, $response->getMethod());
        $this->assertNotEmpty($response->getBasketErrors());

        $this->assertContains('FAILURE', $response->printMessage());
        $this->assertContains(Response::METHOD_ADDNEWBASKET, $response->printMessage());
    }
}
