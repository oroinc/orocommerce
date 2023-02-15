<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds a query for "variantProducts" association of Product entity.
 */
class SetVariantProductsAssociationQuery implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        $variantProductsField = $definition->getField('variantProducts');
        if (null !== $variantProductsField
            && !$variantProductsField->isExcluded()
            && null === $variantProductsField->getAssociationQuery()
        ) {
            $variantProductsField->setAssociationQuery(
                $this->doctrineHelper
                    ->createQueryBuilder(Product::class, 'e')
                    ->innerJoin('e.variantLinks', 'links')
                    ->innerJoin('links.product', 'r')
            );
        }
    }
}
