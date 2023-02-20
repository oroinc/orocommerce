<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\AddParentEntityIdToQuery;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Builds ORM QueryBuilder object that will be used to get "parentProducts" association
 * for Product entity for "get_relationship" and "get_subresource" actions.
 */
class BuildParentProductsSubresourceQuery implements ProcessorInterface
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
        /** @var SubresourceContext $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $idFieldName = $this->getIdentifierFieldName($context->getConfig());
        $query = $this->doctrineHelper
            ->createQueryBuilder(Product::class, 'e')
            ->innerJoin(ProductVariantLink::class, 'links', Join::WITH, 'links.parentProduct = e')
            ->setParameter(AddParentEntityIdToQuery::PARENT_ENTITY_ID_QUERY_PARAM_NAME, $context->getParentId());
        if ('id' === $idFieldName) {
            $query->where('links.product = :' . AddParentEntityIdToQuery::PARENT_ENTITY_ID_QUERY_PARAM_NAME);
        } else {
            $query
                ->innerJoin('links.product', 'product')
                ->where(sprintf(
                    'product.%s = :%s',
                    $idFieldName,
                    AddParentEntityIdToQuery::PARENT_ENTITY_ID_QUERY_PARAM_NAME
                ));
        }

        $context->setQuery($query);
    }

    private function getIdentifierFieldName(EntityDefinitionConfig $config): string
    {
        $idFieldNames = $config->getIdentifierFieldNames();
        $idFieldName = reset($idFieldNames);

        return $config->getField($idFieldName)->getPropertyPath($idFieldName);
    }
}
