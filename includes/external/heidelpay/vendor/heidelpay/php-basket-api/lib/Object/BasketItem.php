<?php

namespace Heidelpay\PhpBasketApi\Object;

use Heidelpay\PhpBasketApi\Exception\InvalidBasketitemPositionException;

/**
 * heidelpay BasketItem
 *
 * BasketItem object representation for the heidelpay Basket API
 *
 * @version 1.2
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/php-basket-api
 *
 * @author Jens Richter <development@heidelpay.com>
 * @author Stephano Vogel <development@heidelpay.com>
 *
 * @package heidelpay\php-basket-api\Object
 */
class BasketItem extends AbstractObject
{
    /**
     * @var int $position (optional) The position of the item in the Basket
     */
    protected $position;

    /**
     * @var string $basketItemReferenceId A unique reference id for the BasketItem with a maximum length of 255
     */
    protected $basketItemReferenceId;

    /**
     * @var string $unit (optional) The unit description of the item e.g. "Stk." with a maximum length of 255
     */
    protected $unit;

    /**
     * @var int $quantity The quantity of the basket item (mandatory)
     */
    protected $quantity;

    /**
     * @var int $amountDiscount The discount amount for the basket item (optinal)
     */
    protected $amountDiscount;

    /**
     * @var int $vat The vat value for the basket item in percent (conditional)
     */
    protected $vat;

    /**
     * @var int $amountGross The gross amount (conditional), means amountNet + amountVat.
     */
    protected $amountGross;

    /**
     * @var int $amountVat The vat amount, this value could be 0 if the vat value is 0 (conditional)
     */
    protected $amountVat;

    /**
     * @var int $amountPerUnit The amount per unit (mandatory)
     */
    protected $amountPerUnit;

    /**
     * @var int $amountNet This value could be the same value as the gross amount if the vat value is 0
     */
    protected $amountNet;

    /**
     * @var string $articleId (optional) The shop article id for the basket item with a maximum length of 255
     */
    protected $articleId;

    /**
     * @var string $type (optional) The type of the basket item, e.g. "goods", "shipment", "voucher" or "digital" with
     *             a maximum length of 255
     */
    protected $type;

    /**
     * @var string $title The title of the BasketItem with a maximum length of 255
     */
    protected $title;

    /**
     * @var string $description (optional) A description for the basket item with a maximum length of 255
     */
    protected $description;

    /**
     * @var string $imageUrl (optional) An image url e.g. https://placehold.it/32x32 with a maximum length of 255
     */
    protected $imageUrl;

    /**
     * @var string $channel (cond. mandatory) The booking channel on which the item has to be booked (Marketplace)
     */
    protected $channel;

    /**
     * @var string $transactionId (optional) A unique identifier with a maximum length of 255
     */
    protected $transactionId;

    /**
     * @var string $usage (optional) A description for the BasketItem with a maximum length of 255
     */
    protected $usage;

    /**
     * @var float $commissionRate (optional) The commission rate for the marketplace in % with 2 decimal places
     */
    protected $commissionRate;

    /**
     * @var int $voucherAmount (optional) Voucher amount to be applied on the current BasketItem
     */
    protected $voucherAmount;

    /**
     * @var string $voucherId (optional) Voucher ID for the current BasketItem with a maximum length of 255
     */
    protected $voucherId;

    /**
     * @var string $articleCategory
     */
    protected $articleCategory;

    /**
     * @var array $mandatory An array containing attributes that are mandatory for every BasketItem
     */
    protected static $mandatory = [
        'basketitemReferenceId',
        'quantity',
        'amountPerUnit',
        'amountNet',
        'title'
    ];

    /**
     * @var bool $isMarketplace If the BasketItem is used for a marketplace.
     */
    private $isMarketplaceItem;

    /**
     * BasketItem constructor.
     *
     * @param bool $isMarketplace Determines if the BasketItem is used for a marketplace
     */
    public function __construct($isMarketplace = false)
    {
        $this->isMarketplaceItem = $isMarketplace;
    }

    /**
     * Sets the discount amount.
     *
     * @param int $value
     *
     * @return $this
     */
    public function setAmountDiscount($value)
    {
        $this->amountDiscount = $value;
        return $this;
    }

    /**
     * Sets the gross amount.
     *
     * @param int $value
     *
     * @return $this
     */
    public function setAmountGross($value)
    {
        $this->amountGross = $value;
        return $this;
    }

    /**
     * Sets the net amount.
     *
     * @param int $value
     *
     * @return $this
     */
    public function setAmountNet($value)
    {
        $this->amountNet = $value;
        return $this;
    }

    /**
     * Sets the amount per unit.
     *
     * @param int $value
     *
     * @return $this
     */
    public function setAmountPerUnit($value)
    {
        $this->amountPerUnit = $value;
        return $this;
    }

    /**
     * Sets the vat amount.
     *
     * @param int $value
     *
     * @return $this
     */
    public function setAmountVat($value)
    {
        $this->amountVat = $value;
        return $this;
    }

    /**
     * Sets the article id.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setArticleId($value)
    {
        $this->articleId = $value;
        return $this;
    }

    /**
     * Sets the BasketItem reference id.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setBasketItemReferenceId($value)
    {
        $this->basketItemReferenceId = $value;
        return $this;
    }

    /**
     * Sets the description.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setDescription($value)
    {
        $this->description = $value;
        return $this;
    }

    /**
     * Image url setter
     *
     * If possible provide a https source - http images could be blocked due to
     * browser securtiy restrictions.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setImageUrl($value)
    {
        $this->imageUrl = $value;
        return $this;
    }

    /**
     * Sets the position.
     *
     * @param int $position
     *
     * @throws InvalidBasketitemPositionException
     *
     * @return $this
     */
    public function setPosition($position)
    {
        if ($position <= 0) {
            throw new InvalidBasketitemPositionException('BasketItem position cannot be equal or less than 0.');
        }

        $this->position = $position;
        return $this;
    }

    /**
     * Sets the quantity.
     *
     * @param int $value
     *
     * @return $this
     */
    public function setQuantity($value)
    {
        $this->quantity = $value;
        return $this;
    }

    /**
     * Sets the title.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setTitle($value)
    {
        $this->title = $value;
        return $this;
    }

    /**
     * Sets the type.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setType($value)
    {
        $this->type = $value;
        return $this;
    }

    /**
     * Sets the unit.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setUnit($value)
    {
        $this->unit = $value;
        return $this;
    }

    /**
     * Sets the vat.
     *
     * @param int $value
     *
     * @return $this
     */
    public function setVat($value)
    {
        $this->vat = $value;
        return $this;
    }

    /**
     * Sets the marketplace channel.
     *
     * @param string $channel
     *
     * @return $this
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
        return $this;
    }

    /**
     * Sets the transaction id.
     *
     * @param string $transactionId
     *
     * @return $this
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    /**
     * Sets the usage.
     *
     * @param string $usage
     *
     * @return $this
     */
    public function setUsage($usage)
    {
        $this->usage = $usage;
        return $this;
    }

    /**
     * Sets the commission rate.
     *
     * @param float $commissionRate
     *
     * @return $this
     */
    public function setCommissionRate($commissionRate)
    {
        $this->commissionRate = $commissionRate;
        return $this;
    }

    /**
     * Sets the voucher amount.
     *
     * @param int $voucherAmount
     *
     * @return $this
     */
    public function setVoucherAmount($voucherAmount)
    {
        $this->voucherAmount = $voucherAmount;
        return $this;
    }

    /**
     * Sets the voucher id.
     *
     * @param string $voucherId
     *
     * @return $this
     */
    public function setVoucherId($voucherId)
    {
        $this->voucherId = $voucherId;
        return $this;
    }

    /**
     * Returns the position.
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Returns the reference id.
     *
     * @return string
     */
    public function getReferenceId()
    {
        return $this->basketItemReferenceId;
    }

    /**
     * Returns the unit.
     *
     * @return string
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Returns the discount amount.
     *
     * @return int
     */
    public function getAmountDiscount()
    {
        return $this->amountDiscount;
    }

    /**
     * Returns the vat.
     *
     * @return int
     */
    public function getVat()
    {
        return $this->vat;
    }

    /**
     * Returns the gross amount.
     *
     * @return int
     */
    public function getAmountGross()
    {
        return $this->amountGross;
    }

    /**
     * Returns the vat amount.
     *
     * @return int
     */
    public function getAmountVat()
    {
        return $this->amountVat;
    }

    /**
     * Returns the amount per unit.
     *
     * @return int
     */
    public function getAmountPerUnit()
    {
        return $this->amountPerUnit;
    }

    /**
     * Returns the net amount.
     *
     * @return int
     */
    public function getAmountNet()
    {
        return $this->amountNet;
    }

    /**
     * Returns the article id.
     *
     * @return string
     */
    public function getArticleId()
    {
        return $this->articleId;
    }

    /**
     * Returns the type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns the title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Returns the description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Returns the image url.
     *
     * @return string
     */
    public function getImageUrl()
    {
        return $this->imageUrl;
    }

    /**
     * Returns the marketplace channel.
     *
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * Returns the transaction id.
     *
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * Returns the usage.
     *
     * @return string
     */
    public function getUsage()
    {
        return $this->usage;
    }

    /**
     * Returns the commission rate.
     *
     * @return float
     */
    public function getCommissionRate()
    {
        return $this->commissionRate;
    }

    /**
     * Returns the voucher amount.
     *
     * @return int
     */
    public function getVoucherAmount()
    {
        return $this->voucherAmount;
    }

    /**
     * Returns the voucher id.
     *
     * @return string
     */
    public function getVoucherId()
    {
        return $this->voucherId;
    }

    /**
     * @todo property is yet undocumented in the Integration_Guide (v1.2)!
     *
     * @return string
     */
    public function getArticleCategory()
    {
        return $this->articleCategory;
    }

    /**
     * @todo property is yet undocumented in the Integration_Guide (v1.2)!
     *
     * @param string $articleCategory
     *
     * @return $this
     */
    public function setArticleCategory($articleCategory)
    {
        $this->articleCategory = $articleCategory;
        return $this;
    }

    /**
     * Returns if the BasketItem is used for marketplace purposes.
     *
     * @return bool
     */
    public function isMarketplaceItem()
    {
        return $this->isMarketplaceItem;
    }

    /**
     * Sets the boolean that determines if the BasketItem is used for a marketplace.
     *
     * @param bool $isMarketplaceItem
     */
    public function setIsMarketplaceItem($isMarketplaceItem = true)
    {
        $this->isMarketplaceItem = $isMarketplaceItem;
    }

    /**
     * Returns an array that is used for the JSON representation when using json_encode or toJson().
     *
     * @return array
     */
    public function jsonSerialize()
    {
        // TODO: add articleCategory if documented and ready to release.
        $result = [
            'position' => $this->position,
            'basketItemReferenceId' => $this->basketItemReferenceId,
            'articleId' => $this->articleId,
            'title' => $this->title,
            'description' => $this->description,
            'amountGross' => $this->amountGross,
            'amountNet' => $this->amountNet,
            'amountPerUnit' => $this->amountPerUnit,
            'amountVat' => $this->amountVat,
            'amountDiscount' => $this->amountDiscount,
            'unit' => $this->unit,
            'quantity' => $this->quantity,
            'vat' => $this->vat,
            'type' => $this->type,
            'imageUrl' => $this->imageUrl,
            'voucherAmount' => $this->voucherAmount,
            'voucherId' => $this->voucherId,
        ];

        if ($this->isMarketplaceItem()) {
            $result = array_merge($result, [
                'channel' => $this->channel,
                'commissionRate' => $this->commissionRate,
                'transactionId' => $this->transactionId,
                'usage' => $this->usage,
            ]);
        }

        return $result;
    }

    /**
     * Magic getter for properties
     *
     * @param $field
     *
     * @return null
     */
    public function __get($field)
    {
        if (property_exists($this, $field)) {
            return $this->$field;
        }

        return null;
    }

    /**
     * Magic setter in favor of parsing.
     *
     * @param $field
     * @param $value
     */
    public function __set($field, $value)
    {
        if (property_exists($this, $field)) {
            $this->$field = $value;
        }
    }

    /**
     * Isset implementation for the __set method
     *
     * @param $field
     *
     * @return bool
     */
    public function __isset($field)
    {
        if (!property_exists($this, $field)) {
            return false;
        }

        return $this->$field !== null && !empty($this->$field);
    }
}
