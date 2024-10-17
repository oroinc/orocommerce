<?php

namespace Oro\Bundle\PricingBundle\Api;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\PricingBundle\Api\Model\ProductKitItemPrice;
use Oro\Bundle\PricingBundle\Api\Model\ProductKitPrice;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\ProductKit\ProductPrice\ProductKitItemPriceInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductPrice\ProductKitPriceDTO;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * The mapper to map data to the product kit prices.
 */
class ProductKitPriceMapper
{
    public static function mapDataToOrderLineItem(array $data): OrderLineItem
    {
        $orderLineItem = new OrderLineItem();
        $orderLineItem->setProduct($data['product']);
        $orderLineItem->setCurrency($data['currency']);
        $orderLineItem->setProductUnit($data['unit']);
        $orderLineItem->setQuantity($data['quantity']);

        foreach ($data['kitItems'] as $kitItem) {
            $orderKitLineItem = new OrderProductKitItemLineItem();
            $orderKitLineItem->setKitItem($kitItem['kitItem']);
            $orderKitLineItem->setProduct($kitItem['product']);
            $orderKitLineItem->setProductUnit($kitItem['kitItem']?->getProductUnit());
            $orderKitLineItem->setQuantity($kitItem['quantity']);
            $orderKitLineItem->setCurrency($data['currency']);

            $orderLineItem->addKitItemLineItem($orderKitLineItem);
        }

        return $orderLineItem;
    }

    public static function mapMatchedPricesToProductKitPrices(
        array $matchedProductPrices,
        Website $website,
        Customer $customer
    ): array {
        $productKitPrices = [];
        /** @var ProductKitPriceDTO $productPrice */
        foreach ($matchedProductPrices as $productPrice) {
            if (!$productPrice) {
                continue;
            }

            $productKitPrice = new ProductKitPrice(...static::collectPriceFields($productPrice, $website, $customer));
            foreach ($productPrice->getKitItemPrices() as $kitItemPrice) {
                $productKitPrice->addKitItemPrice(
                    new ProductKitItemPrice(...static::collectPriceFields($kitItemPrice, $website, $customer))
                );
            }

            $productKitPrices[] = $productKitPrice;
        }

        return $productKitPrices;
    }

    private static function collectPriceFields(
        ProductPriceDTO $productPrice,
        Website $website,
        Customer $customer
    ): array {
        $fields = [
            $customer->getId(),
            $website->getId(),
            $productPrice->getProduct()->getId(),
            $productPrice->getPrice()->getCurrency(),
            $productPrice->getQuantity(),
            $productPrice->getPrice()->getValue(),
            $productPrice->getUnit()->getCode(),
        ];

        if ($productPrice instanceof ProductKitItemPriceInterface) {
            $fields[] = $productPrice->getKitItem()->getId();
        }

        return $fields;
    }
}
