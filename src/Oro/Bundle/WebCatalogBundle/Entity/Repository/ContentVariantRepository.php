<?php

namespace Oro\Bundle\WebCatalogBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Entity repository for Oro\Bundle\WebCatalogBundle\Entity\ContentVariant class
 */
class ContentVariantRepository extends EntityRepository
{
    /**
     * @param Slug $slug
     * @return ContentVariant
     */
    public function findVariantBySlug(Slug $slug)
    {
        $qb = $this->createQueryBuilder('variant');
        $qb->join('variant.slugs', 'slug')
            ->where($qb->expr()->eq('slug', ':slug'))
            ->setParameter('slug', $slug);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param array $criteria as ['<fieldName1>' => ['<id1>', '<id2>, ...], '<fieldName2>' => [<entity1>, ...], ...]
     * @return array
     */
    public function getSlugIdsByCriteria(array $criteria)
    {
        $qb = $this->createQueryBuilder('cv')
            ->select('slug.id')
            ->innerJoin('cv.slugs', 'slug');

        foreach ($criteria as $columnName => $entities) {
            QueryBuilderUtil::checkIdentifier($columnName);

            $qb
                ->orWhere(
                    $qb->expr()->in(\sprintf('cv.%s', $columnName), \sprintf(':%s', $columnName))
                )
                ->setParameter($columnName, $entities);
        }

        return array_column($qb->getQuery()->getArrayResult(), 'id');
    }

    /**
     * @param int $nodeId
     * @param ScopeCriteria $criteria
     * @param string $variantType
     * @return int[] ['<nodeId>' => '<variantId>', ...]
     */
    public function findChildrenVariantIds(int $nodeId, ScopeCriteria $criteria, string $variantType): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('variant.id as v_id, node.id as n_id')
            ->from(ContentNode::class, 'node')
            ->leftJoin('node.scopes', 'node_scopes', Join::WITH)
            ->innerJoin('node.contentVariants', 'variant')
            ->innerJoin('variant.scopes', 'variant_scope')
            ->where(
                $qb->expr()->eq('IDENTITY(node.parentNode)', ':parentNodeId'),
                $qb->expr()->eq('variant.type', ':variantType'),
                $qb->expr()->orX(
                    $qb->expr()->eq('node.parentScopeUsed', ':parentScopeUsed'),
                    $qb->expr()->isNotNull('node_scopes.id')
                )
            )
            ->setParameter('parentScopeUsed', true)
            ->setParameter('variantType', $variantType)
            ->setParameter('parentNodeId', $nodeId);

        $criteria->applyToJoinWithPriority($qb, 'node_scopes');
        $criteria->applyWhereWithPriority($qb, 'variant_scope');

        $ids = [];
        foreach ($qb->getQuery()->getArrayResult() as $row) {
            if (!isset($ids[$row['n_id']])) {
                $ids[$row['n_id']] = $row['v_id'];
            }
        }

        return $ids;
    }

    public function findOneBySlug(Slug $slug): ?ContentVariant
    {
        $qb = $this->createQueryBuilder('c');
        $qb
            ->where($qb->expr()->isMemberOf(':slug', 'c.slugs'))
            ->setParameter('slug', $slug);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
