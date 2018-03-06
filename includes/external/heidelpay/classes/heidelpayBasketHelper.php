<?php
/**
 * Basket helper class
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  https://dev.heidelpay.de/modified/
 *
 * @package  heidelpay
 * @subpackage modified
 * @category modified
 */

require_once(DIR_FS_EXTERNAL . 'heidelpay/vendor/autoload.php');

use Heidelpay\PhpBasketApi\Object\Authentication;
use Heidelpay\PhpBasketApi\Object\Basket;
use Heidelpay\PhpBasketApi\Object\BasketItem;
use Heidelpay\PhpBasketApi\Request;

class heidelpayBasketHelper
{
    /**
     * Create a basket and send it to hPP.
     * If the process of sending the basket to hPP was successful the response will contain a BASKET.ID which can be
     *
     * added as a parameter to the transaction.
     * @param order $order
     * @return \Heidelpay\PhpBasketApi\Response
     */
    public static function sendBasketFromOrder(order $order, $payMethod)
    {
        $authentication = new Authentication(
            constant('MODULE_PAYMENT_HP' . $payMethod . '_USER_LOGIN'),
            constant('MODULE_PAYMENT_HP' . $payMethod . '_USER_PWD'),
            constant('MODULE_PAYMENT_HP' . $payMethod . '_SECURITY_SENDER')
        );
        $basket = new Basket();
        $request = new Request($authentication, $basket);

        $basket->setCurrencyCode($order->info['currency']);
        $basket->setBasketReferenceId($order->info['order_id']);
        $vatMax = 0;

        // add all products to basket
        foreach ($order->products as $product) {
            $item = new BasketItem();
            self::mapToProduct($product, $item);
            //save the highest vat for shipment and coupons
            if ($item->getVat() > $vatMax) {
                $vatMax = (int)$item->getVat();
            }
            $item->setBasketItemReferenceId($basket->getItemCount() + 1);
            $basket->addBasketItem($item, null, true);
        }

        // add coupons and shipping to the basket
        foreach ($order->totals as $total) {
            // find the shipping from totals using the class key
            if (!empty($total['class']) && $total['class'] === 'ot_shipping') {
                $item = new BasketItem();
                $item->setVat($vatMax);
                self::mapToTotals($total, $item);
                $item->setType('shipment');
                $item->setBasketItemReferenceId($basket->getItemCount() + 1);
                $basket->addBasketItem($item, null, true);
            }

            // find coupons and discounts from totals using class keys
            $discountClasses = array(
                'ot_coupon',
                'ot_gv',
                'ot_discount'
            );

            if (!empty($total['class'])) {
                $class = $total['class'];
                if (in_array($class, $discountClasses)) {
                    $item = new BasketItem();
                    $item->setVat($vatMax);
                    self::mapToTotals($total, $item);
                    $item->setType('goods');
                    $item->setBasketItemReferenceId($basket->getItemCount() + 1);
                    $basket->addBasketItem($item, null, true);
                }
            }
        }
        return $request->addNewBasket();
    }

    /**
     * Map a product from an order to parameters of basket-api.
     * The AmountVat is calculated based on Vat and AmountGross
     *
     * @param array $product
     * @param BasketItem $item
     * @return BasketItem
     */
    private static function mapToProduct(array $product, BasketItem $item)
    {
        $item->setTitle($product['name']);
        $item->setBasketItemReferenceId($product['id']);
        $item->setUnit(!empty($product['unit']) ? $product['unit'] : '');
        $item->setQuantity($product['quantity']);
        $item->setArticleId($product['id']);
        $item->setVat((int)$product['tax']);
        $item->setType('goods');
        $item->setAmountPerUnit(self::calcPrice($product['price']));
        $item->setAmountGross(self::calcPrice($product['final_price']));

        $item->setAmountVat((int)(bcmul($item->getAmountGross(), bcdiv($item->getVat(), 100, 2))));
        $item->setAmountNet($item->getAmountGross() - $item->getAmountVat());

        $item->setAmountDiscount(self::calcDiscount($product['final_price'], $product['discount']));
        return $item;
    }

    /**
     * Convert an Amount to its smallest unit.
     * @param $price
     * @param int $factor
     * @return int
     */
    private static function calcPrice($price, $factor = 100)
    {
        return (int)bcmul($price, $factor);
    }

    /**
     * Calculate the discount amount given.
     * Uses the final price and the percentage discount to calculate back the amount
     *
     * @param $finalPrice
     * @param $discount
     * @return int
     */
    private static function calcDiscount($finalPrice, $discount)
    {
        $finalPrice = self::calcPrice($finalPrice);
        $percentage = bcdiv(100 - $discount, 100, 4);
        return (int)(bcdiv($finalPrice, $percentage) - $finalPrice);
    }

    /**
     * Map a product from an order to parameters of basket-api.
     * The AmountVat is calculated based on Vat and AmountGross
     *
     * @param array $total contain totals of the order
     * @param BasketItem $item
     */
    private static function mapToTotals(array $total, BasketItem $item)
    {
        $item->setBasketItemReferenceId($total['orders_total_id']);
        $item->setTitle($total['title']);
        $item->setQuantity('1');
        $item->setArticleId(str_replace('ot_', '', $total['class']) . $total['orders_id']);
        $item->setAmountGross(self::calcPrice($total['value']));
        $item->setAmountPerUnit(self::calcPrice($total['value']));

        $item->setAmountVat((int)(self::calcPrice($total['value']) * bcdiv((string)$item->getVat(), (string)100, 2)));
        $item->setAmountNet(self::calcPrice($total['value']) - $item->getAmountVat());
    }
}
