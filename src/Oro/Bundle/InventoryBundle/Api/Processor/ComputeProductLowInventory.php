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
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var LowInventoryProvider */
    private $lowInventoryProvider;

    /**
     * @param DoctrineHelper       $doctrineHelper
     * @param LowInventoryProvider $lowInventoryProvider
     */
    public function __construct(DoctrineHelper $doctrineHelper, LowInventoryProvider $lowInventoryProvider)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->lowInventoryProvider = $lowInventoryProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getResult();
        if (!is_array($data) || empty($data)) {
            return;
        }

        $lowInventoryFieldName = $context->getResultFieldName('lowInventory');
        if ($context->isFieldRequested($lowInventoryFieldName, $data)) {
            $product = $this->doctrineHelper->getEntity(Product::class, $data['id']);
            $data[$lowInventoryFieldName] = $this->lowInventoryProvider->isLowInventoryProduct($product);
            $context->setResult($data);
        }
    }
}
