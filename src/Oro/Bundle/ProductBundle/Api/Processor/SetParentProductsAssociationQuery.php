<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds a query for "parentProducts" association of Product entity.
 */
class SetParentProductsAssociationQuery implements ProcessorInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        /** @var EntityDefinitionConfig $definition */
        $definition = $context->getResult();
        $parentProductsField = $definition->getField('parentProducts');
        if (null !== $parentProductsField
            && !$parentProductsField->isExcluded()
            && null === $parentProductsField->getAssociationQuery()
        ) {
            $parentProductsField->setAssociationQuery(
                $this->doctrineHelper
                    ->createQueryBuilder(Product::class, 'e')
                    ->innerJoin('e.parentVariantLinks', 'links')
                    ->innerJoin('links.parentProduct', 'r')
            );
        }
    }
}
