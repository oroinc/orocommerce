<?php

namespace Oro\Bundle\WebCatalogBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;

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
}
