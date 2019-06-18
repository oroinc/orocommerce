<?php

namespace Oro\Bundle\CatalogBundle\Api\Processor;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Tree\TreeListener;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\AddParentEntityIdToQuery;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Adds restriction by the primary entity identifier
 * for breadcrumbCategories subresource of Category entity.
 */
class AddParentEntityIdToCategoryQuery implements ProcessorInterface
{
    /** @var TreeListener */
    private $treeListener;

    /** @var ManagerRegistry */
    private $doctrine;

    /**
     * @param TreeListener    $treeListener
     * @param ManagerRegistry $doctrine
     */
    public function __construct(TreeListener $treeListener, ManagerRegistry $doctrine)
    {
        $this->treeListener = $treeListener;
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SubresourceContext $context */

        $query = $context->getQuery();
        if (!$query instanceof QueryBuilder) {
            // unsupported query
            return;
        }

        $config = $this->treeListener->getConfiguration(
            $this->doctrine->getManagerForClass(Category::class),
            Category::class
        );
        $rightFieldName = $config['right'];
        $leftFieldName = $config['left'];
        $rootAlias = QueryBuilderUtil::getSingleRootAlias($query, false);

        $query
            ->innerJoin(
                Category::class,
                'parent',
                Join::WITH,
                QueryBuilderUtil::sprintf(
                    '%1$s.%2$s < parent.%2$s AND %1$s.%3$s > parent.%3$s',
                    $rootAlias,
                    $leftFieldName,
                    $rightFieldName
                )
            )
            ->where('parent.id = :' . AddParentEntityIdToQuery::PARENT_ENTITY_ID_QUERY_PARAM_NAME)
            ->setParameter(AddParentEntityIdToQuery::PARENT_ENTITY_ID_QUERY_PARAM_NAME, $context->getParentId())
            ->orderBy(QueryBuilderUtil::getField($rootAlias, $leftFieldName));
    }
}
