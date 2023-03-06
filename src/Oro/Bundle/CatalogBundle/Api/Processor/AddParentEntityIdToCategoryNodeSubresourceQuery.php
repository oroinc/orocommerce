<?php

namespace Oro\Bundle\CatalogBundle\Api\Processor;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\AddParentEntityIdToQuery;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Adds a restriction by the primary entity identifier to the ORM QueryBuilder
 * that is used to load data for a specific sub-resource of a master catalog tree node.
 */
class AddParentEntityIdToCategoryNodeSubresourceQuery implements ProcessorInterface
{
    private EntityIdHelper $entityIdHelper;
    private string $entityAssociationName;

    public function __construct(EntityIdHelper $entityIdHelper, string $entityAssociationName)
    {
        $this->entityIdHelper = $entityIdHelper;
        $this->entityAssociationName = $entityAssociationName;
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

        $parentJoinAlias = 'parent_entity';
        $query->innerJoin(
            Category::class,
            $parentJoinAlias,
            Join::WITH,
            QueryBuilderUtil::sprintf(
                '%s.%s = %s',
                $parentJoinAlias,
                $this->entityAssociationName,
                QueryBuilderUtil::getSingleRootAlias($query)
            )
        );
        $this->entityIdHelper->applyEntityIdentifierRestriction(
            $query,
            $context->getParentId(),
            $context->getParentMetadata(),
            $parentJoinAlias,
            AddParentEntityIdToQuery::PARENT_ENTITY_ID_QUERY_PARAM_NAME
        );
    }
}
