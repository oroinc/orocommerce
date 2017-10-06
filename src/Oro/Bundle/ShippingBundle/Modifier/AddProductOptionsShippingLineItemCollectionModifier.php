<?php

namespace Oro\Bundle\ShippingBundle\Modifier;

use Oro\Bundle\ShippingBundle\Collection\ProductShippingOptionsGroupedByProductAndUnitCollection;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Factory\ShippingLineItemBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Factory\ShippingLineItemCollectionFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Entity\Repository\ProductShippingOptionsRepository;

class AddProductOptionsShippingLineItemCollectionModifier implements ShippingLineItemCollectionModifierInterface
{
    /**
     * @var ShippingLineItemCollectionFactoryInterface
     */
    private $collectionFactory;

    /**
     * @var ProductShippingOptionsRepository
     */
    private $optionsRepository;

    /**
     * @var ShippingLineItemBuilderFactoryInterface
     */
    private $lineItemBuilderFactory;

    /**
     * @param ShippingLineItemCollectionFactoryInterface $collectionFactory
     * @param ProductShippingOptionsRepository           $optionsRepository
     * @param ShippingLineItemBuilderFactoryInterface    $lineItemBuilderFactory
     */
    public function __construct(
        ShippingLineItemCollectionFactoryInterface $collectionFactory,
        ProductShippingOptionsRepository $optionsRepository,
        ShippingLineItemBuilderFactoryInterface $lineItemBuilderFactory
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->optionsRepository = $optionsRepository;
        $this->lineItemBuilderFactory = $lineItemBuilderFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function modify(ShippingLineItemCollectionInterface $lineItems): ShippingLineItemCollectionInterface
    {
        $options = $this->getShippingOptionsGroupedByProductAndUnit($lineItems);

        $newLineItems = [];
        /** @var ShippingLineItemInterface $item */
        foreach ($lineItems as $item) {
            $product = $item->getProduct();
            if (!$product) {
                $newLineItems[] = $item;

                continue;
            }

            $newLineItems[] = $this->createLineItemWithShippingOptions(
                $item,
                $options->get($product->getId(), $item->getProductUnitCode())
            );
        }

        return $this->collectionFactory->createShippingLineItemCollection($newLineItems);
    }

    /**
     * @param ShippingLineItemInterface   $lineItem
     * @param ProductShippingOptions|null $shippingOption
     *
     * @return ShippingLineItemInterface
     */
    private function createLineItemWithShippingOptions(
        ShippingLineItemInterface $lineItem,
        ProductShippingOptions $shippingOption = null
    ): ShippingLineItemInterface {
        $builder = $this->lineItemBuilderFactory->createBuilder(
            $lineItem->getProductUnit(),
            $lineItem->getProductUnitCode(),
            $lineItem->getQuantity(),
            $lineItem->getProductHolder()
        );

        if ($shippingOption && $shippingOption->getDimensions()) {
            $builder->setDimensions($shippingOption->getDimensions());
        }
        if ($shippingOption && $shippingOption->getWeight()) {
            $builder->setWeight($shippingOption->getWeight());
        }
        if ($lineItem->getPrice()) {
            $builder->setPrice($lineItem->getPrice());
        }
        if ($lineItem->getProduct()) {
            $builder->setProduct($lineItem->getProduct());
            $builder->setProductSku($lineItem->getProductSku());
        }

        return $builder->getResult();
    }

    /**
     * @param ShippingLineItemCollectionInterface $lineItems
     *
     * @return ProductShippingOptionsGroupedByProductAndUnitCollection
     */
    private function getShippingOptionsGroupedByProductAndUnit(
        ShippingLineItemCollectionInterface $lineItems
    ): ProductShippingOptionsGroupedByProductAndUnitCollection {
        $info = $this->getProductAndUnitInfo($lineItems);
        $options = $this->optionsRepository->findByProductsAndProductUnits(
            array_column($info, 'product'),
            array_column($info, 'productUnit')
        );

        $result = new ProductShippingOptionsGroupedByProductAndUnitCollection();
        foreach ($options as $option) {
            $result->add($option);
        }

        return $result;
    }

    /**
     * @param ShippingLineItemCollectionInterface $lineItems
     *
     * @return array
     */
    private function getProductAndUnitInfo(ShippingLineItemCollectionInterface $lineItems): array
    {
        $productsInfo = [];

        /** @var ShippingLineItemInterface $item */
        foreach ($lineItems as $item) {
            $productsInfo[] = [
                'product' => $item->getProduct(),
                'productUnit' => $item->getProductUnit(),
            ];
        }

        return $productsInfo;
    }
}
