<?php

namespace Oro\Bundle\InventoryBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Request\ValueTransformer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "upcoming" and "availabilityDate" fields for Product entity.
 */
class ComputeProductAvailability implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private UpcomingProductProvider $upcomingProductProvider;
    private ValueTransformer $valueTransformer;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        UpcomingProductProvider $upcomingProductProvider,
        ValueTransformer $valueTransformer
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->upcomingProductProvider = $upcomingProductProvider;
        $this->valueTransformer = $valueTransformer;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        $upcomingFieldName = $context->getResultFieldName('upcoming');
        $availabilityDateFieldName = $context->getResultFieldName('availabilityDate');
        $isUpcomingFieldRequested = $context->isFieldRequested($upcomingFieldName, $data);
        $isAvailabilityDateFieldRequested = $context->isFieldRequested($availabilityDateFieldName, $data);

        if (!$isUpcomingFieldRequested && !$isAvailabilityDateFieldRequested) {
            return;
        }

        $idFieldName = $context->getResultFieldName('id');
        if (!$idFieldName) {
            return;
        }

        $product = $this->doctrineHelper->getEntity(Product::class, $data[$idFieldName]);
        $isUpcoming = $this->upcomingProductProvider->isUpcoming($product);
        if ($isUpcomingFieldRequested) {
            $data[$upcomingFieldName] = $isUpcoming;
        }

        if ($isUpcoming && $isAvailabilityDateFieldRequested) {
            $availabilityDate = $this->upcomingProductProvider->getAvailabilityDate($product);
            if ($availabilityDate) {
                $availabilityDate = $this->valueTransformer->transformFieldValue(
                    $availabilityDate,
                    $context->getConfig()->getField($availabilityDateFieldName)->toArray(true),
                    $context->getNormalizationContext()
                );
            }
            $data[$availabilityDateFieldName] = $availabilityDate;
        }

        $context->setData($data);
    }
}
