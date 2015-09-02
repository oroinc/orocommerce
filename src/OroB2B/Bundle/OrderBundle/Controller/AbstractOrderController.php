<?php

namespace OroB2B\Bundle\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Model\ProductUnitQuantity;

abstract class AbstractOrderController extends Controller
{
    /**
     * @return OrderAddressSecurityProvider
     */
    protected function getOrderAddressSecurityProvider()
    {
        return $this->get('orob2b_order.order.provider.order_address_security');
    }

    /**
     * @param Order $order
     * @return array
     */
    protected function getTierPrices(Order $order)
    {
        $tierPrices = [];

        $productIds = $order->getLineItems()->filter(
            function (OrderLineItem $lineItem) {
                return $lineItem->getProduct() !== null;
            }
        )->map(
            function (OrderLineItem $lineItem) {
                return $lineItem->getProduct()->getId();
            }
        );

        if ($productIds) {
            $tierPrices = $this->get('orob2b_pricing.provider.product_price')->getPriceByPriceListIdAndProductIds(
                $this->getPriceList($order)->getId(),
                $productIds->toArray(),
                $order->getCurrency()
            );
        }

        return $tierPrices;
    }

    /**
     * @param Order $order
     * @return array
     */
    protected function getMatchedPrices(Order $order)
    {
        $matchedPrices = [];

        $productUnitQuantities = $order->getLineItems()->filter(
            function (OrderLineItem $lineItem) {
                return $lineItem->getProduct() && $lineItem->getProductUnit() && $lineItem->getQuantity();
            }
        )->map(
            function (OrderLineItem $lineItem) {
                return new ProductUnitQuantity(
                    $lineItem->getProduct(),
                    $lineItem->getProductUnit(),
                    $lineItem->getQuantity()
                );
            }
        );

        if ($productUnitQuantities) {
            $matchedPrices = $this->get('orob2b_pricing.provider.product_price')->getMatchedPrices(
                $productUnitQuantities->toArray(),
                $order->getCurrency(),
                $this->getPriceList($order)
            );
        }

        /** @var Price $price */
        foreach ($matchedPrices as &$price) {
            if ($price) {
                $price = [
                    'value' => $price->getValue(),
                    'currency' => $price->getCurrency()
                ];
            }
        }

        return $matchedPrices;
    }

    /**
     * @param Order $order
     * @return PriceList
     */
    protected function getPriceList(Order $order)
    {
        $priceList = null;
        if ($this->getUser() instanceof User) {
            $priceList = $order->getPriceList();
        }
        if (!$priceList) {
            $priceList = $this->get('orob2b_pricing.model.frontend.price_list_request_handler')->getPriceList();
        }
        return $priceList;
    }
}
