<?php

namespace Oro\Bundle\RedirectBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\RedirectBundle\Event\RestrictSlugIncrementEvent;

/**
 * Slug URLs should not be unique for draft entities
 */
class DraftRestrictSlugIncrementListener
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param RestrictSlugIncrementEvent $event
     */
    public function onRestrictSlugIncrementEvent(RestrictSlugIncrementEvent $event): void
    {
        $entity = $event->getEntity();
        if (!$entity instanceof DraftableInterface) {
            return;
        }

        $queryBuilder = $event->getQueryBuilder();
        if ($entity->getDraftUuid()) {
            // Slug URLs should not be unique for draft entities
            $queryBuilder->andWhere('1 = 0');
        } else {
            // Slug URLs should be unique only across non-drafts
            $entityClass = ClassUtils::getClass($entity);
            $mapping = $this->entityManager->getClassMetadata($entityClass)->getAssociationMapping('slugs');

            $subQueryBuilder = $this->entityManager->getRepository($entityClass)->createQueryBuilder('entity');
            $subQueryBuilder
                ->select('entitySlug.id')
                ->innerJoin(sprintf('entity.%s', $mapping['fieldName']), 'entitySlug')
                ->where($subQueryBuilder->expr()->isNull('entity.draftUuid'));

            $queryBuilder->andWhere(
                $queryBuilder->expr()->in('slug', $subQueryBuilder->getDQL())
            );
        }
    }
}
