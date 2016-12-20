<?php

namespace Oro\Bundle\WebCatalogBundle\Entity\Repository;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr\Join;

use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

/**
 * @method $this persistAsFirstChild($node)
 * @method $this persistAsFirstChildOf($node, $parent)
 * @method $this persistAsLastChild($node)
 * @method $this persistAsLastChildOf($node, $parent)
 * @method $this persistAsNextSibling($node)
 * @method $this persistAsNextSiblingOf($node, $sibling)
 * @method $this persistAsPrevSibling($node)
 * @method $this persistAsPrevSiblingOf($node, $sibling)
 */
class ContentNodeRepository extends NestedTreeRepository
{
    /**
     * @param WebCatalog $webCatalog
     * @return ContentNode
     */
    public function getRootNodeByWebCatalog(WebCatalog $webCatalog)
    {
        $qb = $this->getRootNodesQueryBuilder();
        $qb->andWhere(
            $qb->expr()->eq('node.webCatalog', ':webCatalog')
        );
        $qb->setParameter('webCatalog', $webCatalog);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param WebCatalog $webCatalog
     * @return QueryBuilder
     */
    public function getContentVariantQueryBuilder(WebCatalog $webCatalog)
    {
        $qb = $this->createQueryBuilder('node');
        $qb->select('node.id as nodeId')
            ->innerJoin(
                ContentVariant::class,
                'variant',
                Join::WITH,
                $qb->expr()->isMemberOf('variant', 'node.contentVariants')
            )
            ->andWhere('node.webCatalog = :webCatalog')
            ->setParameter('webCatalog', $webCatalog);

        return $qb;
    }

    /**
     * @param array $ids
     * @return ContentNode[]
     */
    public function getNodesByIds(array $ids)
    {
        $qb = $this->createQueryBuilder('node');
        $qb->andWhere('node.id IN (:ids)')
            ->setParameter('ids', $ids);

        return $qb->getQuery()->getResult();
    }
}
