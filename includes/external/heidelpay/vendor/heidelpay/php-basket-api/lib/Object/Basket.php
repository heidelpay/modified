<?php

namespace Heidelpay\PhpBasketApi\Object;

use Heidelpay\PhpBasketApi\Exception\InvalidBasketitemIdException;
use Heidelpay\PhpBasketApi\Exception\InvalidBasketitemPositionException;

/**
 * heidelpay Basket
 *
 * Basket object representation for the heidelpay Basket API
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
class Basket extends AbstractObject
{
    /**
     * @var int $amountTotalNet The total amount of the whole basket without Tax in the smallest unit of the currency
     */
    protected $amountTotalNet;

    /**
     * @var int $amountTotalVat The total amount of the Vat in smallest unit of the currency
     */
    protected $amountTotalVat;

    /**
     * @var int $amountTotalDiscount The total discount amount of the whole basket in the smallest unit of the currency
     */
    protected $amountTotalDiscount;

    /**
     * @var BasketItem[] $basketItems An array containing BasketItems
     */
    protected $basketItems = [];

    /**
     * @var string $basketReferenceId A basket reference ID sent from the shop backend with a maximum length of 255
     */
    protected $basketReferenceId;

    /**
     * @var string $currencyCode The currency code in ISO 4217 format with 3 characters
     */
    protected $currencyCode;

    /**
     * @var string $note A note sent from the application with a maximum length of 3900 characters
     */
    protected $note;

    /**
     * @var int $voucherAmount Voucher amount to be applied on a whole basket
     */
    protected $voucherAmount;

    /**
     * @var string $voucherId A Voucher ID for a whole basket
     */
    protected $voucherId;

    /**
     * @var array $mandatory An array of attributes that are mandatory for the Basket
     */
    protected static $mandatory = [
        'amountTotalNet',
        'currencyCode',
        'basketItems'
    ];

    /**
     * Returns the total discount.
     *
     * @return int
     */
    public function getAmountTotalDiscount()
    {
        return $this->amountTotalDiscount;
    }

    /**
     * Sets the total discount amount.
     *
     * @param int $amountTotalDiscount
     *
     * @return $this
     */
    public function setAmountTotalDiscount($amountTotalDiscount)
    {
        $this->amountTotalDiscount = $amountTotalDiscount;

        return $this;
    }

    /**
     * Returns the total net amount.
     *
     * @return int
     */
    public function getAmountTotalNet()
    {
        return $this->amountTotalNet;
    }

    /**
     * Sets the total net amount.
     *
     * @param int $value
     *
     * @return $this
     */
    public function setAmountTotalNet($value)
    {
        $this->amountTotalNet = $value;
        return $this;
    }

    /**
     * Returns the total vat amount.
     *
     * @return int
     */
    public function getAmountTotalVat()
    {
        return $this->amountTotalVat;
    }

    /**
     * Sets the total vat amount.
     *
     * @param int $value
     *
     * @return $this
     */
    public function setAmountTotalVat($value)
    {
        $this->amountTotalVat = $value;
        return $this;
    }

    /**
     * Returns the Basket reference id.
     *
     * @return string basketReferenceId
     */
    public function getBasketReferenceId()
    {
        return $this->basketReferenceId;
    }

    /**
     * Sets the Basket reference id.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setBasketReferenceId($value)
    {
        $this->basketReferenceId = $value;
        return $this;
    }

    /**
     * Returns the currency code.
     *
     * @return string currency code
     */
    public function getCurrencyCode()
    {
        return $this->currencyCode;
    }

    /**
     * Sets the currency code.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setCurrencyCode($value)
    {
        $this->currencyCode = $value;
        return $this;
    }

    /**
     * Returns the amount of BasketItems.
     *
     * @return int
     */
    public function getItemCount()
    {
        return count($this->basketItems);
    }

    /**
     * Returns the Basket note.
     *
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Sets the Basket note.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setNote($value)
    {
        $this->note = $value;
        return $this;
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
     * Returns the voucher ID.
     *
     * @return string
     */
    public function getVoucherId()
    {
        return $this->voucherId;
    }

    /**
     * Sets the voucher ID.
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
     * Returns the array of all BasketItems.
     *
     * @return BasketItem[]
     */
    public function getBasketItems()
    {
        return $this->basketItems;
    }

    /**
     * Returns a BasketItem at the given position.
     *
     * @param int $position
     *
     * @throws InvalidBasketitemPositionException
     *
     * @return BasketItem|null
     */
    public function getBasketItemByPosition($position)
    {
        if ($position <= 0) {
            throw new InvalidBasketitemPositionException('BasketItem position cannot be equal or less than 0.');
        }

        if (array_key_exists($position - 1, $this->basketItems)) {
            return $this->basketItems[$position - 1];
        }

        return null;
    }

    /**
     * Returns a BasketItem by it's reference id.
     *
     * @param string $referenceId
     *
     * @return BasketItem|null
     */
    public function getBasketItemByReferenceId($referenceId)
    {
        foreach ($this->getBasketItems() as $basketItem) {
            if ($basketItem->getReferenceId() === $referenceId) {
                return $basketItem;
            }
        }

        return null;
    }

    /**
     * Returns the 'real' position of a BasketItem in the array, where 'real' means
     * the array equivalent of a position (0 array => 1 Basket position)
     *
     * @param int $position
     *
     * @throws InvalidBasketitemPositionException
     *
     * @return BasketItem
     */
    private function getBasketItemByRealPosition($position)
    {
        if (isset($this->basketItems[$position])) {
            return $this->basketItems[$position];
        }

        throw new InvalidBasketitemPositionException('BasketItem position ' . $position . ' is invalid.');
    }

    /**
     * Adds a BasketItem to the Basket.
     *
     * @param BasketItem $item       The BasketItem to be added
     * @param int|null   $position   The position where the item should be placed (optional)
     * @param bool       $autoUpdate If the BasketItem amounts can be added to the Basket automatically
     *
     * @throws InvalidBasketitemPositionException
     *
     * @return $this
     */
    public function addBasketItem(BasketItem $item, $position = null, $autoUpdate = false)
    {
        // do this to avoid having copies or references.
        $newItem = clone $item;

        $realPosition = !$position ? $this->getBasketItemPosition($newItem, $position) : $position;

        if ($autoUpdate) {
            $this->addBasketItemAmountsToBasket($newItem);
        }

        if ($realPosition === null) {
            $newItem->setPosition($this->getItemCount() + 1);
        }

        $this->basketItems[$realPosition] = $newItem;
        sort($this->basketItems);

        return $this;
    }

    /**
     * Updates the object at the given index.
     *
     * @param BasketItem $item       the item to be set
     * @param int        $position   The position of the BasketItem
     * @param bool       $autoUpdate If Basket amounts should be updated automatically
     *
     * @throws InvalidBasketitemPositionException
     *
     * @return $this
     */
    public function updateBasketItem(BasketItem $item, $position = null, $autoUpdate = false)
    {
        $updateItem = clone $item;

        $realPosition = $this->getBasketItemPosition($updateItem, $position, true);

        if ($realPosition === null) {
            throw new InvalidBasketitemPositionException('BasketItem has no position, and no position was given.');
        }

        if (array_key_exists($realPosition, $this->basketItems)) {
            if ($autoUpdate) {
                $oldItem = $this->getBasketItemByRealPosition($realPosition);
                $this->updateAmountBalances($oldItem, $updateItem);
            }

            $this->basketItems[$realPosition] = $updateItem;
            return $this;
        }

        throw new InvalidBasketitemPositionException('Basket item with id ' . $position . ' does not exist.');
    }

    /**
     * Removes an item of the basket at the given position.
     *
     * @param int  $position   the basket index of the item
     * @param bool $autoUpdate decrease Basket amounts by BasketItem amounts
     *
     * @throws InvalidBasketitemPositionException
     *
     * @return $this
     */
    public function deleteBasketItemByPosition($position, $autoUpdate = true)
    {
        if ($position <= 0) {
            throw new InvalidBasketitemPositionException('BasketItem position cannot be equal or less than 0.');
        }

        if (array_key_exists($position - 1, $this->basketItems)) {
            if ($autoUpdate) {
                $this->decreaseBasketItemAmountsFromBasket($this->getBasketItemByPosition($position));
            }

            unset($this->basketItems[$position - 1]);
            return $this;
        }

        throw new InvalidBasketitemPositionException('Basket item on position ' . $position . ' does not exist.');
    }

    /**
     * Deletes a BasketItem by it's reference id.
     *
     * @param string $referenceId
     * @param bool   $autoUpdate  decrease Basket amounts by BasketItem amounts
     *
     * @return $this
     *
     * @throws InvalidBasketitemIdException
     */
    public function deleteBasketItemByReferenceId($referenceId, $autoUpdate = true)
    {
        foreach ($this->getBasketItems() as $basketItem) {
            if ($basketItem->getReferenceId() === $referenceId) {
                if ($autoUpdate) {
                    $this->decreaseBasketItemAmountsFromBasket($basketItem);
                }

                unset($this->basketItems[$basketItem->getPosition() - 1]);
                return $this;
            }
        }

        throw new InvalidBasketitemIdException('Basket item with refereceId ' . $referenceId . ' does not exist.');
    }

    /**
     * Returns an array that is used for the JSON representation when using json_encode or toJson().
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'amountTotalNet' => $this->amountTotalNet,
            'amountTotalVat' => $this->amountTotalVat,
            'amountTotalDiscount' => $this->amountTotalDiscount,
            'basketReferenceId' => $this->basketReferenceId,
            'currencyCode' => $this->currencyCode,
            'voucherAmount' => $this->voucherAmount,
            'voucherId' => $this->voucherId,
            'itemCount' => $this->getItemCount(),
            'note' => $this->note,
            'basketItems' => array_values($this->basketItems)
        ];
    }

    /**
     * Determines the position of the BasketItem in the BasketItem array.
     *
     * @param BasketItem $item     The BasketItem containing the changes
     * @param int|null   $position The position where a BasketItem should be updated
     * @param bool       $doUpdate If the position is needed for an update
     *
     * @return int|null
     */
    private function getBasketItemPosition(BasketItem $item, $position = null, $doUpdate = false)
    {
        $result = null;

        // in case the item position is not null and > 0
        if (is_numeric($item->getPosition()) && $item->getPosition() > 0) {
            $result = $item->getPosition();
        }

        // in case the position is not null and > 0
        if (is_numeric($position) && $position > 0) {
            $result = $position - 1;
        }

        // if an item already exists on the determined position, just increase the result number...
        if ($result !== null && isset($this->basketItems[$result])) {
            ++$result;
        }

        // getBasketItemPosition determines a position for a new item, so if
        // we do an update, we'll decrease the found position by one.
        if ($doUpdate && $result !== null && is_numeric($result)) {
            --$result;
        }

        return $result;
    }

    /**
     * Increases the total discount amount.
     *
     * @param int $amount
     */
    public function addAmountTotalDiscount($amount)
    {
        $this->amountTotalDiscount += $amount;
    }

    /**
     * Decreases the total discount amount.
     *
     * @param int $amount
     */
    public function decreaseAmountTotalDiscount($amount)
    {
        $this->amountTotalDiscount -= $amount;
    }

    /**
     * Increases the total net amount.
     *
     * @param int $amount
     */
    public function addAmountTotalNet($amount)
    {
        $this->amountTotalNet += $amount;
    }

    /**
     * Decreases the total net amount.
     *
     * @param int $amount
     */
    public function decreaseAmountTotalNet($amount)
    {
        $this->amountTotalNet -= $amount;
    }

    /**
     * Increases the total vat amount.
     *
     * @param int $amount
     */
    public function addAmountTotalVat($amount)
    {
        $this->amountTotalVat += $amount;
    }

    /**
     * Decreases the total vat amount.
     *
     * @param int $amount
     */
    public function decreaseAmountTotalVat($amount)
    {
        $this->amountTotalVat -= $amount;
    }

    /**
     * Increases the Basket amounts by the BasketItem amounts.
     *
     * @param BasketItem $basketItem
     */
    private function addBasketItemAmountsToBasket(BasketItem $basketItem)
    {
        $this->addAmountTotalDiscount($basketItem->getAmountDiscount());
        $this->addAmountTotalNet($basketItem->getAmountNet());
        $this->addAmountTotalVat($basketItem->getAmountVat());
    }

    /**
     * Decreases the Basket amounts by the BasketItem amounts.
     *
     * @param BasketItem $basketItem
     */
    private function decreaseBasketItemAmountsFromBasket(BasketItem $basketItem)
    {
        $this->decreaseAmountTotalDiscount($basketItem->getAmountDiscount());
        $this->decreaseAmountTotalNet($basketItem->getAmountNet());
        $this->decreaseAmountTotalVat($basketItem->getAmountVat());
    }

    /**
     * Updates the Basket balances according to the differences
     * of an updated BasketItem and it's predecessor.
     *
     * @param BasketItem $oldItem
     * @param BasketItem $newItem
     */
    private function updateAmountBalances(BasketItem $oldItem, BasketItem $newItem)
    {
        $discountBalance = $newItem->getAmountDiscount() - $oldItem->getAmountDiscount();
        if ($discountBalance !== 0) {
            $this->addAmountTotalDiscount($discountBalance);
        }

        $netBalance = $newItem->getAmountNet() - $oldItem->getAmountNet();
        if ($netBalance !== 0) {
            $this->addAmountTotalNet($netBalance);
        }

        $vatBalance = $newItem->getAmountVat() - $oldItem->getAmountVat();
        if ($vatBalance !== 0) {
            $this->addAmountTotalVat($vatBalance);
        }
    }

    /**
     * Magic getter for properties.
     *
     * @param $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
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
