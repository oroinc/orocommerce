<?php

namespace Oro\Bundle\FixedProductShippingBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FixedProductShippingBundle\Migrations\Data\ORM\LoadPriceAttributePriceListData;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Provider\PriceAttributePricesProvider;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;

/**
 * Calculate shipping cost value for 'Fixed Product Shipping' integration.
 */
class ShippingCostProvider
{
    private PriceAttributePricesProvider $pricesProvider;
    private ManagerRegistry $registry;

    public function __construct(PriceAttributePricesProvider $pricesProvider, ManagerRegistry $registry)
    {
        $this->pricesProvider = $pricesProvider;
        $this->registry = $registry;
    }

    /**
     * @param ShippingLineItemCollectionInterface $lineItems
     * @param string $currency
     *
     * @return float
     */
    public function getCalculatedProductShippingCost(
        ShippingLineItemCollectionInterface $lineItems,
        string $currency
    ): float {
        $shippingCost = 0.0;
        $priceList = $this->getPriceListShippingCostAttribute();
        if (!$priceList) {
            return $shippingCost;
        }

        foreach ($lineItems as $lineItem) {
            $attribute = $this->pricesProvider->getPricesWithUnitAndCurrencies($priceList, $lineItem->getProduct());
            $unitCode = $lineItem->getProductUnitCode();

            if (!isset($attribute[$unitCode][$currency])) {
                continue;
            }

            /** @var PriceAttributeProductPrice $productPrice */
            $productPrice = $attribute[$unitCode][$currency];
            $price = $productPrice->getPrice()->getValue();

            $shippingCost += (float)$price * $lineItem->getQuantity();
        }

        return $shippingCost;
    }

    private function getPriceListShippingCostAttribute(): ?PriceAttributePriceList
    {
        return $this->registry
            ->getRepository(PriceAttributePriceList::class)
            ->findOneBy(['name' => LoadPriceAttributePriceListData::SHIPPING_COST_NAME]);
    }
}
