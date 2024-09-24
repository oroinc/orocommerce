<?php

namespace Oro\Bundle\ShippingBundle\Context\LineItem\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\ShippingLineItemOptionsModifier;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;

/**
 * Creates:
 *  - instance of {@see ShippingLineItem} by {@see ProductLineItemInterface};
 *  - collection of {@see ShippingLineItem} by iterable {@see ProductLineItemInterface}.
 */
class ShippingLineItemFromProductLineItemFactory implements ShippingLineItemFromProductLineItemFactoryInterface
{
    public function __construct(
        private ShippingKitItemLineItemFromProductKitItemLineItemFactoryInterface $shippingKitItemLineItemFactory,
        protected ShippingLineItemOptionsModifier $shippingLineItemOptionsModifier
    ) {
    }

    #[\Override]
    public function create(ProductLineItemInterface $productLineItem): ShippingLineItem
    {
        $this->shippingLineItemOptionsModifier->loadShippingOptions([$productLineItem]);

        $shippingLineItem = $this->createShippingLineItem($productLineItem);

        $this->shippingLineItemOptionsModifier->clear();

        return $shippingLineItem;
    }

    /**
     * @param iterable<ProductLineItemInterface> $productLineItems
     *
     * @return Collection<ShippingLineItem>
     */
    #[\Override]
    public function createCollection(iterable $productLineItems): Collection
    {
        $this->shippingLineItemOptionsModifier->loadShippingOptions($productLineItems);

        $shippingLineItems = [];
        foreach ($productLineItems as $productLineItem) {
            $shippingLineItems[] = $this->createShippingLineItem($productLineItem);
        }

        $this->shippingLineItemOptionsModifier->clear();

        return new ArrayCollection($shippingLineItems);
    }

    /**
     * @param ProductLineItemInterface $productLineItem
     *  [
     *      product id => [
     *          product unit code => [
     *              'dimensionsHeight' => float,
     *              'dimensionsLength' => float,
     *              'dimensionsWidth' => float,
     *              'dimensionsUnit' => string,
     *              'weightUnit' => string,
     *              'weightValue' => float,
     *              'code' => string,
     *          ],
     *          ...
     *      ],
     *      ...
     *  ]
     *
     * Example:
     *  [
     *      1 => [
     *          'item' => [
     *              'dimensionsHeight' => 1.0,
     *              'dimensionsLength' => 1.0,
     *              'dimensionsWidth' => 1.0,
     *              'dimensionsUnit' => 'inch',
     *              'weightUnit' => 'lbs',
     *              'weightValue' => 1.0,
     *              'code' => 'item',
     *          ],
     *          ...
     *      ],
     *      ...
     *  ]
     *
     * @return ShippingLineItem
     */
    protected function createShippingLineItem(ProductLineItemInterface $productLineItem): ShippingLineItem
    {
        $product = $productLineItem->getProduct();

        $shippingLineItem = (new ShippingLineItem(
            $productLineItem->getProductUnit(),
            $productLineItem->getQuantity(),
            $productLineItem
        ))
            ->setProduct($product)
            ->setProductSku($productLineItem->getProductSku());

        if ($productLineItem instanceof PriceAwareInterface) {
            $shippingLineItem->setPrice($productLineItem->getPrice());
        }

        if ($productLineItem instanceof ProductKitItemLineItemsAwareInterface) {
            $shippingLineItem->setChecksum($productLineItem->getChecksum())
                ->setKitItemLineItems(
                    $this->shippingKitItemLineItemFactory->createCollection(
                        $productLineItem->getKitItemLineItems()
                    )
                );
        }

        $this->shippingLineItemOptionsModifier->modifyLineItemWithShippingOptions($shippingLineItem);

        return $shippingLineItem;
    }
}
