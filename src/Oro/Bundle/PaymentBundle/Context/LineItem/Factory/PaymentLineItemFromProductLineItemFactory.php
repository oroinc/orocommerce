<?php

namespace Oro\Bundle\PaymentBundle\Context\LineItem\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Creates:
 *  - instance of {@see PaymentLineItem} by {@see ProductLineItemInterface};
 *  - collection of {@see PaymentLineItem} by iterable {@see ProductLineItemInterface}.
 */
class PaymentLineItemFromProductLineItemFactory implements PaymentLineItemFromProductLineItemFactoryInterface
{
    private PaymentKitItemLineItemFromProductKitItemLineItemFactoryInterface $paymentKitItemLineItemFactory;

    public function __construct(
        PaymentKitItemLineItemFromProductKitItemLineItemFactoryInterface $paymentKitItemLineItemFactory
    ) {
        $this->paymentKitItemLineItemFactory = $paymentKitItemLineItemFactory;
    }

    #[\Override]
    public function create(ProductLineItemInterface $productLineItem): PaymentLineItem
    {
        $paymentLineItem = (new PaymentLineItem(
            $productLineItem->getProductUnit(),
            $productLineItem->getQuantity(),
            $productLineItem
        ))
            ->setProduct($productLineItem->getProduct())
            ->setProductSku($productLineItem->getProductSku());

        if ($productLineItem instanceof PriceAwareInterface) {
            $paymentLineItem->setPrice($productLineItem->getPrice());
        }

        if ($productLineItem instanceof ProductKitItemLineItemsAwareInterface) {
            $paymentLineItem->setChecksum($productLineItem->getChecksum())
                ->setKitItemLineItems(
                    $this->paymentKitItemLineItemFactory->createCollection(
                        $productLineItem->getKitItemLineItems()
                    )
                );
        }

        return $paymentLineItem;
    }

    /**
     * @param iterable<ProductLineItemInterface> $productLineItems
     *
     * @return Collection<PaymentLineItem>
     */
    #[\Override]
    public function createCollection(iterable $productLineItems): Collection
    {
        $paymentLineItems = [];
        foreach ($productLineItems as $productLineItem) {
            $paymentLineItems[] = $this->create($productLineItem);
        }

        return new ArrayCollection($paymentLineItems);
    }
}
