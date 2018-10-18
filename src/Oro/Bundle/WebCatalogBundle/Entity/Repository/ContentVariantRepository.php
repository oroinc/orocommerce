<?php

namespace Oro\Bundle\WebCatalogBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
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
}
