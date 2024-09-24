<?php

namespace Oro\Bundle\PaymentBundle\Context\LineItem\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\PaymentBundle\Context\PaymentKitItemLineItem;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemPriceAwareInterface;

/**
 * Creates:
 *  - instance of {@see PaymentKitItemLineItem} by {@see ProductKitItemLineItemPriceAwareInterface};
 *  - collection of {@see PaymentKitItemLineItem} by iterable {@see ProductKitItemLineItemPriceAwareInterface}.
 */
class PaymentKitItemLineItemFromProductKitItemLineItemFactory implements
    PaymentKitItemLineItemFromProductKitItemLineItemFactoryInterface
{
    #[\Override]
    public function create(ProductKitItemLineItemPriceAwareInterface $productKitItemLineItem): PaymentKitItemLineItem
    {
        return (new PaymentKitItemLineItem(
            $productKitItemLineItem->getProductUnit(),
            $productKitItemLineItem->getQuantity(),
            $productKitItemLineItem
        ))
            ->setProduct($productKitItemLineItem->getProduct())
            ->setProductSku($productKitItemLineItem->getProductSku())
            ->setPrice($productKitItemLineItem->getPrice())
            ->setKitItem($productKitItemLineItem->getKitItem())
            ->setSortOrder($productKitItemLineItem->getSortOrder());
    }

    #[\Override]
    public function createCollection(iterable $productKitItemLineItems): Collection
    {
        $paymentKitItemLineItems = [];
        foreach ($productKitItemLineItems as $productKitItemLineItem) {
            $paymentKitItemLineItems[] = $this->create($productKitItemLineItem);
        }

        return new ArrayCollection($paymentKitItemLineItems);
    }
}
