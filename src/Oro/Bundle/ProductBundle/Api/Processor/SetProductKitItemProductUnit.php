<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Service\ProductKitItemProductUnitChecker;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Sets the product unit from the product kit primary unit precision as the default value for
 * the {@see ProductKitItem::$productUnit} if it is empty.
 */
class SetProductKitItemProductUnit implements ProcessorInterface
{
    use LoggerAwareTrait;

    private ProductKitItemProductUnitChecker $productUnitChecker;

    public function __construct(ProductKitItemProductUnitChecker $productUnitChecker)
    {
        $this->productUnitChecker = $productUnitChecker;
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        /** @var ProductKitItem $productKitItem */
        $productKitItem = $context->getData();

        $productKitItemUnit = $productKitItem->getProductUnit();
        if ($productKitItemUnit !== null) {
            // Skips further execution as product unit is already set.
            return;
        }

        $productKitUnit = $productKitItem->getProductKit()?->getPrimaryUnitPrecision()->getUnit();
        if ($productKitUnit === null) {
            // Skips further execution as product unit is not set and cannot be set from product kit.
            return;
        }

        if ($this->productUnitChecker
            ->isProductUnitEligible($productKitUnit->getCode(), $productKitItem->getProducts())) {
            $logMessage = '$productUnit is not specified for ProductKitItem, trying to use '
                . 'product unit "{product_unit}" from the product kit primary unit precision';

            $productKitItem->setProductUnit($productKitUnit);
        } else {
            $logMessage = '$productUnit is not specified for ProductKitItem, but product unit "{product_unit}"'
                . ' from the product kit primary unit precision cannot be used because it is not present'
                . ' in each product unit precisions collection of the ProductKitItem products';
        }

        $this->logger->debug(
            $logMessage,
            [
                'product_kit_item' => $productKitItem,
                'product_kit_unit' => $productKitUnit->getCode(),
            ]
        );
    }
}
