<?php

namespace Heidelpay\Tests\PhpBasketApi\Unit\Object;

use Heidelpay\PhpBasketApi\Exception\InvalidBasketitemIdException;
use Heidelpay\PhpBasketApi\Exception\InvalidBasketitemPositionException;
use Heidelpay\PhpBasketApi\Object\Basket;
use Heidelpay\PhpBasketApi\Object\BasketItem;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the PHP Basket API Object
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/php-basket-api
 *
 * @author Jens Richter <development@heidelpay.com>
 *
 * @package heidelpay\php-basket-api\tests\unit\object
 */
class BasketTest extends TestCase
{
    /**
     * @var Basket the testing fixture
     */
    protected $basket;

    /**
     * Sets up the Basket fixture
     */
    public function setUp()
    {
        $this->basket = new Basket();
    }

    /**
     * getter and setter test for amountTotalNet
     */
    public function testAmountTotalNet()
    {
        $value = 20;

        $this->basket->setAmountTotalNet($value);
        $this->assertSame($value, $this->basket->getAmountTotalNet());
    }

    /**
     * getter and setter test for amountTotalVat
     */
    public function testAmountTotalVat()
    {
        $value = 7;

        $this->basket->setAmountTotalVat($value);
        $this->assertSame($value, $this->basket->getAmountTotalVat());
    }

    /**
     * Unit test for the BasketItemReferenceId
     */
    public function testBasketItemReferenceId()
    {
        $referenceId = 'heidelpay-basketitem-test';

        $item = new BasketItem();
        $item->setBasketItemReferenceId($referenceId);
        $this->basket->addBasketItem($item);

        $this->assertNotNull($this->basket->getBasketItemByReferenceId($referenceId));
        $this->assertNull($this->basket->getBasketItemByReferenceId('invalid-id'));

        $this->assertEquals($this->basket, $this->basket->deleteBasketItemByReferenceId($referenceId));
        $this->assertNull($this->basket->getBasketItemByReferenceId($referenceId));

        $this->expectException(InvalidBasketitemIdException::class);
        $this->basket->deleteBasketItemByReferenceId('invalid-id');
    }

    /**
     * test basket reference id
     */
    public function testBasketReferenceId()
    {
        $value = '26343294';

        $this->basket->setBasketReferenceId($value);
        $this->assertEquals($value, $this->basket->getBasketReferenceId());
    }

    /**
     * test currency code
     */
    public function testCurrencyCode()
    {
        $value = 'EUR';

        $this->basket->setCurrencyCode($value);
        $this->assertEquals($value, $this->basket->getCurrencyCode());
    }

    /**
     * test item count
     */
    public function testItemCount()
    {
        $this->assertEquals(0, $this->basket->getItemCount());

        $item = new BasketItem();
        $this->basket->addBasketItem($item);
        $this->assertEquals(1, $this->basket->getItemCount());

        $this->basket->addBasketItem($item);
        $this->assertEquals(2, $this->basket->getItemCount());

        // delect one item from object
        $this->basket->deleteBasketItemByPosition(1);
        $this->assertEquals(1, $this->basket->getItemCount());
    }

    /**
     * test basket note
     */
    public function testNote()
    {
        $value = 'Customer basket';

        $this->basket->setNote($value);
        $this->assertEquals($value, $this->basket->getNote());
    }

    /**
     * Unit test for Voucher amount
     */
    public function testVoucherAmount()
    {
        $voucherAmount = 2500;

        $this->assertNull($this->basket->getVoucherAmount());

        $this->basket->setVoucherAmount($voucherAmount);
        $this->assertEquals($voucherAmount, $this->basket->getVoucherAmount());
    }

    /**
     * Unit test for Voucher Id
     */
    public function testVoucherId()
    {
        $voucherId = 'HAPPYNEWYEAR';

        $this->assertNull($this->basket->getVoucherId());

        $this->basket->setVoucherId($voucherId);
        $this->assertEquals($voucherId, $this->basket->getVoucherId());
    }

    /**
     * Unit test for JSON
     */
    public function testJsonSerializable()
    {
        $this->assertNotEmpty($this->basket->jsonSerialize());

        $this->assertArrayHasKey('amountTotalNet', $this->basket->jsonSerialize());
        $this->assertArrayHasKey('amountTotalVat', $this->basket->jsonSerialize());
        $this->assertArrayHasKey('amountTotalDiscount', $this->basket->jsonSerialize());
        $this->assertArrayHasKey('basketReferenceId', $this->basket->jsonSerialize());
        $this->assertArrayHasKey('currencyCode', $this->basket->jsonSerialize());
        $this->assertArrayHasKey('itemCount', $this->basket->jsonSerialize());
        $this->assertArrayHasKey('note', $this->basket->jsonSerialize());
        $this->assertArrayHasKey('basketItems', $this->basket->jsonSerialize());

        $this->assertNotEmpty($this->basket->toJson());
    }

    /**
     * test for adding and removing BasketItems
     */
    public function testAddAndDeleteBasketItems()
    {
        $item = new BasketItem();
        $item2 = new BasketItem();
        $item3 = new BasketItem();

        $this->basket->addBasketItem($item);
        $this->basket->addBasketItem($item2);
        $this->basket->addBasketItem($item3);

        $this->assertEquals(3, $this->basket->getItemCount());

        // test for single item object
        $this->assertNotNull($this->basket->getBasketItemByPosition(1), 'Object does not contain an item');

        // test update item object by id
        $title = 'fish and chips';
        $this->basket->updateBasketItem($item->setTitle($title), 1);
        $this->assertEquals($title, $this->basket->getBasketItemByPosition(1)->getTitle());
        $this->assertEquals($title, $this->basket->getBasketItems()[0]->getTitle());

        // test delete item object form basket
        $this->basket->deleteBasketItemByPosition(1);
        $this->assertNotNull($this->basket->getBasketItemByPosition(2), 'More then one item has been deleted');

        $result = $this->basket->getBasketItemByPosition(1) ? true : false;
        $this->assertFalse($result, 'Item object has not been removed from basket');
        $this->assertNull($this->basket->getBasketItemByPosition(1));

        $this->expectException(InvalidBasketitemPositionException::class);
        $this->basket->getBasketItemByPosition(0);
        $this->basket->deleteBasketItemByPosition(-1);
        $this->basket->deleteBasketItemByPosition(0);
        $this->basket->deleteBasketItemByPosition(42);
    }

    public function testAddItemsToBasketWithAutoUpdate()
    {
        $amountNet = 8100;
        $amountVat = 1900;
        $amountDiscount = 500;

        $item = new BasketItem();
        $item->setAmountNet($amountNet);
        $item->setAmountVat($amountVat);
        $item->setAmountDiscount($amountDiscount);

        // add the BasketItem with $position = null (so the item's position in the Basket will
        // be determined in the method) and $autoUpdate = true (so the Basket's amounts
        // will be updated according to the BasketItem's amounts
        $this->basket->addBasketItem($item, null, true);
        $this->assertEquals($amountNet, $this->basket->getAmountTotalNet());
        $this->assertEquals($amountVat, $this->basket->getAmountTotalVat());
        $this->assertEquals($amountDiscount, $this->basket->getAmountTotalDiscount());
    }

    /**
     * Test for the case when a BasketItem is added, amounts are changed and updated in the Basket.
     */
    public function testUpdateBasketWithoutAutoUpdate()
    {
        $firstAmountNet = 190;
        $firstAmountGross = 1000;

        $secondAmountNet = 380;
        $secondAmountGross = 2000;

        $item = new BasketItem();
        $item->setAmountNet($firstAmountNet);
        $item->setAmountGross($firstAmountGross);
        $item->setPosition(1);

        $this->basket->addBasketItem($item);

        $this->assertEquals($firstAmountGross, $this->basket->getBasketItemByPosition(1)->getAmountGross());
        $this->assertEquals($firstAmountNet, $this->basket->getBasketItemByPosition(1)->getAmountNet());

        // autoUpdate in addBasketItem is false, so we expect the basketTotalNet amount to be still 0.
        $this->assertEquals(0, $this->basket->getAmountTotalNet());

        $item->setAmountGross($secondAmountGross);
        $item->setAmountNet($secondAmountNet);

        // update the BasketItem ($position does not need to be provided, since $item already has a position of 1)
        // and verify that the BasketItem in the Basket has been updated.
        $this->basket->updateBasketItem($item);
        $this->assertEquals($secondAmountGross, $this->basket->getBasketItemByPosition(1)->getAmountGross());
        $this->assertEquals($secondAmountNet, $this->basket->getBasketItemByPosition(1)->getAmountNet());

        // autoUpdate in updateBasketItem is false, so we expect the basketTotalNet amount to be still 0.
        $this->assertEquals(0, $this->basket->getAmountTotalNet());

        // throw an exception when an item with no position reference is tried to be updated in the basket.
        $this->expectException(InvalidBasketitemPositionException::class);
        $item3 = new BasketItem();
        $this->basket->updateBasketItem($item3);
    }

    public function testUpdateBasketWithAutoUpdate()
    {
        $amountNet = 8100;
        $amountVat = 1900;
        $amountDiscount = 500;

        $updatedAmountNet = 4000;
        $updatedAmountVat = 950;
        $updatedAmountDiscount = 250;

        $moreUpdatedAmountNet = 5000;
        $moreUpdatedAmountVat = 1250;
        $moreUpdatedAmountDiscount = 300;

        $item = new BasketItem();
        $item->setAmountNet($amountNet);
        $item->setAmountVat($amountVat);
        $item->setAmountDiscount($amountDiscount);

        // add the BasketItem with $position = null (so the item's position in the Basket will
        // be determined in the method) and $autoUpdate = true (so the Basket's amounts
        // will be updated according to the BasketItem's amounts
        $this->basket->addBasketItem($item, null, true);
        $this->assertEquals($amountNet, $this->basket->getAmountTotalNet());
        $this->assertEquals($amountVat, $this->basket->getAmountTotalVat());
        $this->assertEquals($amountDiscount, $this->basket->getAmountTotalDiscount());

        // decrease the amounts and update the item in the Basket
        $item->setAmountNet($updatedAmountNet);
        $item->setAmountVat($updatedAmountVat);
        $item->setAmountDiscount($updatedAmountDiscount);

        // update the BasketItem (tests decreasing of all amounts)
        $this->basket->updateBasketItem($item, 1, true);
        $this->assertEquals($updatedAmountNet, $this->basket->getAmountTotalNet());
        $this->assertEquals($updatedAmountVat, $this->basket->getAmountTotalVat());
        $this->assertEquals($updatedAmountDiscount, $this->basket->getAmountTotalDiscount());

        $item->setAmountNet($moreUpdatedAmountNet);
        $item->setAmountVat($moreUpdatedAmountVat);
        $item->setAmountDiscount($moreUpdatedAmountDiscount);

        $this->basket->updateBasketItem($item, 1, true);

        $this->assertEquals($moreUpdatedAmountNet, $this->basket->getAmountTotalNet());
        $this->assertEquals($moreUpdatedAmountVat, $this->basket->getAmountTotalVat());
        $this->assertEquals($moreUpdatedAmountDiscount, $this->basket->getAmountTotalDiscount());
    }

    /**
     * Unit test for magic getters and setters
     */
    public function testMagicGetterCases()
    {
        $testValue = 'Test';

        // ensure that the property is null.
        $this->assertNull($this->basket->note);

        // set test property and access it.
        $this->basket->setNote($testValue);
        $this->assertEquals($testValue, $this->basket->note);

        // access invalid property and get null.
        $this->assertNull($this->basket->test);
    }

    /**
     * Test the implementation of the __isset magic method.
     */
    public function testIssetMagicMethodCases()
    {
        // amountTotalNet is present, but null
        $this->assertFalse(isset($this->basket->amountTotalNet));

        // notExisting is not a property of Basket
        $this->assertFalse(isset($this->basket->notExisting));

        // set the amountTotalNet to ensure isset will return true now.
        $this->basket->setAmountTotalNet(100);
        $this->assertTrue(isset($this->basket->amountTotalNet));
    }

    /**
     * Try to update a basket item on a position where an item is not
     * existing (yet), so we expect an exception to be thrown.
     */
    public function testThrowExceptionWhenUpdatingANonExistingBasketitem()
    {
        $item = new BasketItem();
        $item->setTitle('Fish & Chips');
        $item->setDescription('Tasty!');

        $this->expectException(InvalidBasketitemPositionException::class);
        $this->basket->updateBasketItem($item, 2);
    }

    /**
     * When trying to delete a BasketItem with a position
     * of 0 or less, we expect an exception to be thrown.
     */
    public function testThrowExceptionWhenDeletingAPositionBelowOrEqualsZero()
    {
        $this->expectException(InvalidBasketitemPositionException::class);
        $this->expectExceptionMessage('BasketItem position cannot be equal or less than 0.');
        $this->basket->deleteBasketItemByPosition(0);
    }

    /**
     * When trying to delete a BasketItem with a position
     * of 0 or less, we expect an exception to be thrown.
     */
    public function testThrowExceptionWhenDeletingAPositionWithoutAnItem()
    {
        $positionToBeDeleted = 1;

        $this->expectException(InvalidBasketitemPositionException::class);
        $this->expectExceptionMessage('Basket item on position ' . $positionToBeDeleted . ' does not exist.');
        $this->basket->deleteBasketItemByPosition($positionToBeDeleted);
    }

    /**
     * When trying to delete a BasketItem by a given referenceId,
     * we expect an exception to be thrown.
     */
    public function testThrowExceptionWhenDeletingANonExistingReferenceIdBasketItem()
    {
        $referenceIdToBeDeleted = 'ABC-123';

        $this->expectException(InvalidBasketitemIdException::class);
        $this->expectExceptionMessage('Basket item with refereceId ' . $referenceIdToBeDeleted . ' does not exist.');
        $this->basket->deleteBasketItemByReferenceId($referenceIdToBeDeleted);
    }
}
