<?php

namespace OroB2B\Bundle\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use OroB2B\Bundle\PricingBundle\Provider\ProductPriceProvider;
use OroB2B\Bundle\PricingBundle\Model\ProductPriceCriteria;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;

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
            $tierPrices = $this->getProductPriceProvider()->getPriceByPriceListIdAndProductIds(
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

        $productsPriceCriteria = $order->getLineItems()->filter(
            function (OrderLineItem $lineItem) {
                return $lineItem->getProduct() && $lineItem->getProductUnit() && $lineItem->getQuantity();
            }
        )->map(
            function (OrderLineItem $lineItem) use ($order) {
                return new ProductPriceCriteria(
                    $lineItem->getProduct(),
                    $lineItem->getProductUnit(),
                    $lineItem->getQuantity(),
                    $order->getCurrency()
                );
            }
        );

        if ($productsPriceCriteria) {
            $matchedPrices = $this->getProductPriceProvider()->getMatchedPrices(
                $productsPriceCriteria->toArray(),
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
     * @return BasePriceList
     */
    protected function getPriceList(Order $order)
    {
        return $this->get('orob2b_pricing.model.price_list_tree_handler')
            ->getPriceList($order->getAccount(), $order->getWebsite());
    }

    /**
     * @return ProductPriceProvider
     */
    protected function getProductPriceProvider()
    {
        return $this->get('orob2b_pricing.provider.combined_product_price');
    }
}
