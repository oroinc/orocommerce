<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;

/**
 * This class manually deletes Slugs for ContentVariant`s when ContentVariant`s upper linked entity are deleted.
 *
 * Note, that ContentVariant itself is deleted by RDBMS because of 'onDelete=CASCADE' on its linked entity relation.
 * But Slugs isn't auto deleted because of many-to-many relation between ContentVariant`s & its Slugs.
 */
class ContentVariantSlugsRemoveListener
{
    /** @var null|array */
    private $contentVariantLinkedEntities;

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();

        $entitiesToDelete = $em->getUnitOfWork()->getScheduledEntityDeletions();
        if (!$entitiesToDelete) {
            return;
        }

        $linkedEntities = $this->getContentVariantLinkedEntitiesClasses($em);
        if (!$linkedEntities) {
            return;
        }

        $criteria = $this->createSlugsDeletionCriteria($entitiesToDelete, $linkedEntities);
        if ($criteria) {
            $repository = $em->getRepository(ContentVariant::class);

            foreach ($repository->getSlugIdsByCriteria($criteria) as $slugId) {
                $em->remove(
                    $em->getReference(Slug::class, $slugId)
                );
            }
        }
    }

    /**
     * @param array $entitiesToDelete
     * @param array $linkedEntities
     * @return array
     */
    private function createSlugsDeletionCriteria(array $entitiesToDelete, array $linkedEntities)
    {
        $criteria = [];

        foreach ($entitiesToDelete as $entity) {
            $className = ClassUtils::getClass($entity);

            if (isset($linkedEntities[$className])) {
                foreach ($linkedEntities[$className] as $fieldName) {
                    $criteria[$fieldName][] = $entity;
                }
            }
        }

        return $criteria;
    }

    /**
     * @param EntityManager $em
     * @return array as ['linkedEntityClassName' => ['fieldName1', 'fieldName2]]
     */
    private function getContentVariantLinkedEntitiesClasses(EntityManager $em)
    {
        if ($this->contentVariantLinkedEntities === null) {
            $this->contentVariantLinkedEntities = [];

            foreach ($em->getClassMetadata(ContentVariant::class)->getAssociationMappings() as $mapping) {
                if (!empty($mapping['isOwningSide']) &&
                    !empty($mapping['type']) &&
                    $mapping['type'] === ClassMetadata::MANY_TO_ONE
                ) {
                    $this->contentVariantLinkedEntities[$mapping['targetEntity']][] = $mapping['fieldName'];
                }
            }
        }

        return $this->contentVariantLinkedEntities;
    }
}
