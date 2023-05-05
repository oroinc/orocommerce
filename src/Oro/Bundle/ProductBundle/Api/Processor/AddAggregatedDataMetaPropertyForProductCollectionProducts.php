<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Metadata\MetaAttributeMetadata;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds "aggregatedData" meta property
 * to "products" association of ProductCollection entity.
 */
class AddAggregatedDataMetaPropertyForProductCollectionProducts implements ProcessorInterface
{
    private const PRODUCTS_ASSOCIATION = 'products';
    private const AGGREGATED_DATA_PROPERTY = 'aggregatedData';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $entityMetadata = $context->getMetadata();
        if (null === $entityMetadata) {
            return;
        }

        $association = $entityMetadata->getAssociation(self::PRODUCTS_ASSOCIATION);
        if (null === $association) {
            return;
        }

        $association->addRelationshipMetaProperty(
            new MetaAttributeMetadata(self::AGGREGATED_DATA_PROPERTY, DataType::OBJECT)
        );
    }
}
