<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Service\ProductKitItemProductUnitChecker;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Psr\Log\LoggerInterface;

/**
 * Sets the product unit from the product kit primary unit precision as the default value for
 * the {@see ProductKitItem::$productUnit} if it is empty.
 */
class SetProductKitItemProductUnit implements ProcessorInterface
{
    private ProductKitItemProductUnitChecker $productUnitChecker;
    private LoggerInterface $logger;

    public function __construct(ProductKitItemProductUnitChecker $productUnitChecker, LoggerInterface $logger)
    {
        $this->productUnitChecker = $productUnitChecker;
        $this->logger = $logger;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        /** @var ProductKitItem $productKitItem */
        $productKitItem = $context->getData();

        $productKitItemUnit = $productKitItem->getProductUnit();
        if (null !== $productKitItemUnit) {
            // product unit is already set
            return;
        }

        $productKitUnit = $productKitItem->getProductKit()?->getPrimaryUnitPrecision()->getUnit();
        if (null === $productKitUnit) {
            // product unit is not set and cannot be set from product kit
            return;
        }

        if ($this->productUnitChecker->isProductUnitEligible(
            $productKitUnit->getCode(),
            $productKitItem->getProducts()
        )) {
            $productKitItem->setProductUnit($productKitUnit);
            $logMessage = '$productUnit is not specified for ProductKitItem, trying to use '
                . 'product unit "{product_kit_unit}" from the product kit primary unit precision';
        } else {
            $logMessage = '$productUnit is not specified for ProductKitItem, but product unit "{product_kit_unit}"'
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
