<?php

namespace Oro\Bundle\InventoryBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "lowInventory" field for Product entity.
 */
class ComputeProductLowInventory implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private LowInventoryProvider $lowInventoryProvider;

    public function __construct(DoctrineHelper $doctrineHelper, LowInventoryProvider $lowInventoryProvider)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->lowInventoryProvider = $lowInventoryProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        $lowInventoryFieldName = $context->getResultFieldName('lowInventory');
        if (!$context->isFieldRequested($lowInventoryFieldName, $data)) {
            return;
        }

        $idFieldName = $context->getResultFieldName('id');
        if (!$idFieldName) {
            return;
        }

        $data[$lowInventoryFieldName] = $this->lowInventoryProvider->isLowInventoryProduct(
            $this->doctrineHelper->getEntity(Product::class, $data[$idFieldName])
        );
        $context->setData($data);
    }
}
