<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Metadata\MetaAttributeMetadata;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds "aggregatedData" meta property to "products" association of ProductCollection entity.
 */
class AddAggregatedDataMetaPropertyForProductCollectionProducts implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $entityMetadata = $context->getMetadata();
        if (null === $entityMetadata) {
            return;
        }

        $association = $entityMetadata->getAssociation('products');
        if (null === $association) {
            return;
        }

        $association->addRelationshipMetaProperty(
            new MetaAttributeMetadata('aggregatedData', DataType::OBJECT)
        );
    }
}
