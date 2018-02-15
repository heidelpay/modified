<?php
/**
 * Created by PhpStorm.
 * User: David.Owusu
 * Date: 15.02.2018
 * Time: 16:35
 */
require_once(DIR_FS_EXTERNAL . 'heidelpay/vendor/autoload.php');

use Heidelpay\PhpBasketApi\Object\Authentication;
use Heidelpay\PhpBasketApi\Object\Basket;
use Heidelpay\PhpBasketApi\Object\BasketItem;
use Heidelpay\PhpBasketApi\Request;

class heidelpayBasketHelper
{

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

        $shipping = null;

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
            // find coupons and discounts from totals using class key
            if (!empty($total['class'])
                && ($total['class'] === 'ot_coupon'
                    OR $total['class'] === 'ot_gv')) {
                $item = new BasketItem();
                $item->setVat($vatMax);
                self::mapToTotals($total, $item);
                $item->setType('goods');
                $item->setBasketItemReferenceId($basket->getItemCount() + 1);
                $basket->addBasketItem($item, null, true);
            }
        }
        return $request->addNewBasket();
    }

    /**
     * Map a product from an order to parameters for the basket-api
     * @param array $product
     * @param BasketItem $item
     * @return BasketItem
     */
    public static function mapToProduct(array $product, BasketItem $item)
    {
        $item->setTitle($product['name']);
        $item->setBasketItemReferenceId($product['id']);
        $item->setUnit(!empty($product['unit']) ? $product['unit'] : '');
        $item->setQuantity($product['quantity']);
        $item->setArticleId($product['id']);
        $item->setVat((int)$product['tax']);
        $item->setType('goods');
        $item->setAmountPerUnit((int)(bcmul($product['price'], 100)));
        $item->setAmountGross((int)(bcmul($product['final_price'], 100)));
        $item->setAmountVat((int)(bcmul($item->getAmountGross(), bcdiv($item->getVat(), 100, 2))));
        $item->setAmountNet(($item->getAmountGross() - $item->getAmountVat()));

        $item->setAmountDiscount((int)($product['discount_made']));
        return $item;
    }

    /**
     * @param array $total contain totals of the order
     * @param BasketItem $item
     */
    public static function mapToTotals(array $total, BasketItem $item)
    {
        $item->setBasketItemReferenceId($total['orders_total_id']);
        $item->setTitle($total['title']);
        $item->setQuantity('1');
        $item->setArticleId(str_replace('ot_', '', $total['class']) . $total['orders_id']);
        $item->setAmountGross((int)(bcmul($total['value'], 100)));
        $item->setAmountVat((int)($total['value'] * 100 * bcdiv((string)$item->getVat(), (string)100, 2)));
        $item->setAmountNet((int)($total['value'] * 100 - $item->getAmountVat()));
        $item->setAmountPerUnit((int)(bcmul($total['value'], 100)));
    }
}