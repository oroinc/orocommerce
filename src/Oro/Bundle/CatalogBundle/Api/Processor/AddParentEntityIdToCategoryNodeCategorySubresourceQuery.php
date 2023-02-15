<?php

namespace Oro\Bundle\CatalogBundle\Api\Processor;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\AddParentEntityIdToQuery;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Adds a restriction by the primary entity identifier to the ORM QueryBuilder
 * that is used to load data for "category" sub-resource of a master catalog tree node.
 */
class AddParentEntityIdToCategoryNodeCategorySubresourceQuery implements ProcessorInterface
{
    private EntityIdHelper $entityIdHelper;

    public function __construct(EntityIdHelper $entityIdHelper)
    {
        $this->entityIdHelper = $entityIdHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var SubresourceContext $context */

        $query = $context->getQuery();
        if (!$query instanceof QueryBuilder) {
            // unsupported query
            return;
        }

        $this->entityIdHelper->applyEntityIdentifierRestriction(
            $query,
            $context->getParentId(),
            $context->getParentMetadata(),
            QueryBuilderUtil::getSingleRootAlias($query),
            AddParentEntityIdToQuery::PARENT_ENTITY_ID_QUERY_PARAM_NAME
        );
    }
}
