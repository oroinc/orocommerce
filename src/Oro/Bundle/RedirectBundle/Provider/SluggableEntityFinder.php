<?php

namespace Oro\Bundle\RedirectBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SlugAwareInterface;

/**
 * Provides a method to find sluggable entity by slug.
 */
class SluggableEntityFinder
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Finds a sluggable entity of the specified type by the given slug.
     */
    public function findEntityBySlug(string $entityClass, Slug $slug): ?SlugAwareInterface
    {
        $qb = $this->doctrine->getManagerForClass($entityClass)->createQueryBuilder();
        $qb
            ->select('e')
            ->from($entityClass, 'e')
            ->where($qb->expr()->isMemberOf(':slug', 'e.slugs'))
            ->setParameter('slug', $slug);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
