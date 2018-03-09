<?php

namespace Heidelpay\Tests\PhpBasketApi\Unit\Object;

use Heidelpay\PhpBasketApi\Exception\InvalidBasketitemPositionException;
use Heidelpay\PhpBasketApi\Object\BasketItem;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the BasketItem Objects
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/php-basket-api
 *
 * @author Stephano Vogel <development@heidelpay.com>
 *
 * @package heidelpay\php-basket-api\tests\unit\object
 */
class BasketItemTest extends TestCase
{
    /**
     * @var BasketItem the testing fixture
     */
    protected $basketItem;

    /**
     * Sets up the BasketItem fixture
     */
    public function setUp()
    {
        $this->basketItem = new BasketItem();
    }

    /**
     * Unit test for the position
     */
    public function testPosition()
    {
        $position3 = 3;
        $position1 = 1;

        $this->assertNull($this->basketItem->getPosition());

        $this->basketItem->setPosition($position3);
        $this->assertEquals($position3, $this->basketItem->getPosition());

        $this->basketItem->setPosition($position1);
        $this->assertEquals($position1, $this->basketItem->getPosition());

        // throw an exception, when position is <= 0
        $this->expectException(InvalidBasketitemPositionException::class);
        $this->basketItem->setPosition(0);
    }

    /**
     * Unit test for the Basketitem Reference Id
     */
    public function testBasketItemReferenceId()
    {
        $referenceId = '136d24be-12b0-7d076a9e';

        $this->assertNull($this->basketItem->getReferenceId());

        $this->basketItem->setBasketItemReferenceId($referenceId);
        $this->assertEquals($referenceId, $this->basketItem->getReferenceId());
    }

    /**
     * Unit test for the unit
     */
    public function testUnit()
    {
        $unit = 'Pc.';
        $unit2 = 'Stk.';

        $this->assertNull($this->basketItem->getUnit());

        $this->basketItem->setUnit($unit);
        $this->assertEquals($unit, $this->basketItem->getUnit());

        $this->basketItem->setUnit($unit2);
        $this->assertEquals($unit2, $this->basketItem->getUnit());
    }

    /**
     * Unit test for the quantity
     */
    public function testQuantity()
    {
        $quantity = 3;
        $quantity2 = 2;

        $this->assertNull($this->basketItem->getQuantity());

        $this->basketItem->setQuantity($quantity);
        $this->assertEquals($quantity, $this->basketItem->getQuantity());

        $this->basketItem->setQuantity($quantity2);
        $this->assertEquals($quantity2, $this->basketItem->getQuantity());
    }

    /**
     * Unit test for the discount amount
     */
    public function testAmountDiscount()
    {
        $amountDiscount0 = 0;
        $amountDiscount20 = 20;

        $this->assertNull($this->basketItem->getAmountDiscount());

        $this->basketItem->setAmountDiscount($amountDiscount0);
        $this->assertEquals($amountDiscount0, $this->basketItem->getAmountDiscount());

        $this->basketItem->setAmountDiscount($amountDiscount20);
        $this->assertEquals($amountDiscount20, $this->basketItem->getAmountDiscount());
    }

    /**
     * Unit test for the vat
     */
    public function testVat()
    {
        $vat19 = 19;
        $vat7 = 7;

        $this->assertNull($this->basketItem->getVat());

        $this->basketItem->setVat($vat19);
        $this->assertEquals($vat19, $this->basketItem->getVat());

        $this->basketItem->setVat($vat7);
        $this->assertEquals($vat7, $this->basketItem->getVat());
    }

    /**
     * Unit test for the amount per unit
     */
    public function testAmountPerUnit()
    {
        $amountPerUnit750 = 750;
        $amountPerUnit7999 = 7999;

        $this->assertNull($this->basketItem->getAmountPerUnit());

        $this->basketItem->setAmountPerUnit($amountPerUnit750);
        $this->assertEquals($amountPerUnit750, $this->basketItem->getAmountPerUnit());

        $this->basketItem->setAmountPerUnit($amountPerUnit7999);
        $this->assertEquals($amountPerUnit7999, $this->basketItem->getAmountPerUnit());
    }

    /**
     * Unit test for the net amount
     */
    public function testAmountNet()
    {
        $amountNet630 = 630;
        $amountNet6722 = 6722;

        $this->assertNull($this->basketItem->getAmountNet());

        $this->basketItem->setAmountNet($amountNet630);
        $this->assertEquals($amountNet630, $this->basketItem->getAmountNet());

        $this->basketItem->setAmountNet($amountNet6722);
        $this->assertEquals($amountNet6722, $this->basketItem->getAmountNet());
    }

    /**
     * Unit test for the gross amount
     */
    public function testAmountGross()
    {
        $amountGross750 = 750;
        $amountGross7999 = 7999;

        $this->assertNull($this->basketItem->getAmountGross());

        $this->basketItem->setAmountGross($amountGross750);
        $this->assertEquals($amountGross750, $this->basketItem->getAmountGross());

        $this->basketItem->setAmountGross($amountGross7999);
        $this->assertEquals($amountGross7999, $this->basketItem->getAmountGross());
    }

    /**
     * Unit test for the vat amount
     */
    public function testAmountVat()
    {
        $amountVat120 = 120;
        $amountVat1277 = 1277;

        $this->assertNull($this->basketItem->getAmountVat());

        $this->basketItem->setAmountVat($amountVat120);
        $this->assertEquals($amountVat120, $this->basketItem->getAmountVat());

        $this->basketItem->setAmountVat($amountVat1277);
        $this->assertEquals($amountVat1277, $this->basketItem->getAmountVat());
    }

    /**
     * Unit test for the article id
     */
    public function testArticleId()
    {
        $articleId = '223302316';

        $this->assertNull($this->basketItem->getArticleId());

        $this->basketItem->setArticleId($articleId);
        $this->assertEquals($articleId, $this->basketItem->getArticleId());
    }

    /**
     * Unit test for the type
     */
    public function testType()
    {
        $typeShipping = 'shipping';
        $typeGoods = 'goods';

        $this->assertNull($this->basketItem->getType());

        $this->basketItem->setType($typeShipping);
        $this->assertEquals($typeShipping, $this->basketItem->getType());

        $this->basketItem->setType($typeGoods);
        $this->assertEquals($typeGoods, $this->basketItem->getType());
    }

    /**
     * Unit test for the title
     */
    public function testTitle()
    {
        $titleShipping = 'Shipping';
        $titleHeadphone = 'Wireless RF Headphone';

        $this->assertNull($this->basketItem->getTitle());

        $this->basketItem->setTitle($titleShipping);
        $this->assertEquals($titleShipping, $this->basketItem->getTitle());

        $this->basketItem->setTitle($titleHeadphone);
        $this->assertEquals($titleHeadphone, $this->basketItem->getTitle());
    }

    /**
     * Unit test for the description
     */
    public function testDescription()
    {
        $descriptionEmpty = '';
        $descriptionHeadphone = 'Wireless RF Headphone, Black';

        $this->assertNull($this->basketItem->getDescription());

        $this->basketItem->setDescription($descriptionEmpty);
        $this->assertEquals('', $this->basketItem->getDescription());
        $this->assertEmpty($this->basketItem->getDescription());

        $this->basketItem->setDescription($descriptionHeadphone);
        $this->assertEquals($descriptionHeadphone, $this->basketItem->getDescription());
    }

    /**
     * Unit test for the image url
     */
    public function testImageUrl()
    {
        $imageUrlEmpty = '';
        $imageUrlPlaceHoldIt = 'https://placehold.it/236566083.jpg';

        $this->assertNull($this->basketItem->getImageUrl());

        $this->basketItem->setImageUrl($imageUrlEmpty);
        $this->assertEquals($imageUrlEmpty, $this->basketItem->getImageUrl());

        $this->basketItem->setImageUrl($imageUrlPlaceHoldIt);
        $this->assertEquals($imageUrlPlaceHoldIt, $this->basketItem->getImageUrl());
    }

    /**
     * Unit test for the Channel
     */
    public function testChannel()
    {
        $channel = 'test';

        $this->assertNull($this->basketItem->getChannel());

        $this->basketItem->setChannel($channel);
        $this->assertEquals($channel, $this->basketItem->getChannel());
    }

    /**
     * Unit test for the Transaction-Id
     */
    public function testTransactionId()
    {
        $transactionId = '312451asfasb12';

        $this->assertNull($this->basketItem->getTransactionId());

        $this->basketItem->setTransactionId($transactionId);
        $this->assertEquals($transactionId, $this->basketItem->getTransactionId());
    }

    /**
     * Unit test for the usage
     */
    public function testUsage()
    {
        $usage = 'Test item for unit testing';

        $this->assertNull($this->basketItem->getUsage());

        $this->basketItem->setUsage($usage);
        $this->assertEquals($usage, $this->basketItem->getUsage());
    }

    /**
     * Unit test for the commission rate
     */
    public function testCommissionRate()
    {
        $rate = 5.50;

        $this->assertNull($this->basketItem->getCommissionRate());

        $this->basketItem->setCommissionRate($rate);
        $this->assertEquals($rate, $this->basketItem->getCommissionRate());
    }

    /**
     * Unit test for the Voucher amount
     */
    public function testVoucherAmount()
    {
        $voucherAmount = 2500;

        $this->assertNull($this->basketItem->getVoucherAmount());

        $this->basketItem->setVoucherAmount($voucherAmount);
        $this->assertEquals($voucherAmount, $this->basketItem->getVoucherAmount());
    }

    /**
     * Unit test for the Voucher Id
     */
    public function testVoucherId()
    {
        $voucherId = 'FREESHIPPING';

        $this->assertNull($this->basketItem->getVoucherId());

        $this->basketItem->setVoucherId($voucherId);
        $this->assertEquals($voucherId, $this->basketItem->getVoucherId());
    }

    /**
     * Unit test for the article category
     */
    public function testArticleCategory()
    {
        $articleCategory = 'Goods';

        $this->assertNull($this->basketItem->getArticleCategory());

        $this->basketItem->setArticleCategory($articleCategory);
        $this->assertEquals($articleCategory, $this->basketItem->getArticleCategory());
    }

    /**
     * Unit test for magic getters and setters
     */
    public function testMagicGetterCases()
    {
        $testValue = 'Test';

        // ensure that the property is null.
        $this->assertNull($this->basketItem->type);

        // set test property and access it.
        $this->basketItem->setType($testValue);
        $this->assertEquals($testValue, $this->basketItem->type);

        // access invalid property and get null.
        $this->assertNull($this->basketItem->invalidProperty);
    }

    /**
     * Test the implementation of the __set & __isset magic methods
     */
    public function testIssetMagicMethodCases()
    {
        // amountNet is present, but null
        $this->assertFalse(isset($this->basketItem->amountNet));

        $this->assertFalse(isset($this->basketItem->notExistingProperty));

        // set the amountNet via magic setter to ensure isset will return true now.
        $this->basketItem->amountNet = 100;
        $this->assertTrue(isset($this->basketItem->amountNet));
    }

    /**
     * Unit test for JSON
     */
    public function testJsonSerializable()
    {
        $this->assertNotEmpty($this->basketItem->jsonSerialize());

        $this->assertArrayHasKey('position', $this->basketItem->jsonSerialize());
        $this->assertArrayHasKey('basketItemReferenceId', $this->basketItem->jsonSerialize());
        $this->assertArrayHasKey('articleId', $this->basketItem->jsonSerialize());
        $this->assertArrayHasKey('title', $this->basketItem->jsonSerialize());
        $this->assertArrayHasKey('description', $this->basketItem->jsonSerialize());
        $this->assertArrayHasKey('amountGross', $this->basketItem->jsonSerialize());
        $this->assertArrayHasKey('amountNet', $this->basketItem->jsonSerialize());
        $this->assertArrayHasKey('amountPerUnit', $this->basketItem->jsonSerialize());
        $this->assertArrayHasKey('amountVat', $this->basketItem->jsonSerialize());
        $this->assertArrayHasKey('amountDiscount', $this->basketItem->jsonSerialize());
        $this->assertArrayHasKey('unit', $this->basketItem->jsonSerialize());
        $this->assertArrayHasKey('quantity', $this->basketItem->jsonSerialize());
        $this->assertArrayHasKey('vat', $this->basketItem->jsonSerialize());
        $this->assertArrayHasKey('type', $this->basketItem->jsonSerialize());
        $this->assertArrayHasKey('imageUrl', $this->basketItem->jsonSerialize());

        $this->assertNotEmpty($this->basketItem->toJson());
    }

    /**
     * Unit test for a BasketItem with marketplace usage.
     *
     * @depends testJsonSerializable
     */
    public function testJsonSerializableAsMarketplaceItem()
    {
        $this->basketItem->setIsMarketplaceItem();

        $this->assertTrue($this->basketItem->isMarketplaceItem());

        $this->assertArrayHasKey('channel', $this->basketItem->jsonSerialize());
        $this->assertArrayHasKey('commissionRate', $this->basketItem->jsonSerialize());
        $this->assertArrayHasKey('transactionId', $this->basketItem->jsonSerialize());
        $this->assertArrayHasKey('usage', $this->basketItem->jsonSerialize());

        $this->assertNotEmpty($this->basketItem->toJson());
    }
}
