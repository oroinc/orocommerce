<?php

namespace Oro\Bundle\PricingBundle\Form;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;

class PriceListWithPriorityCollectionHandler
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param PropertyAccessor $propertyAccessor
     */
    public function __construct(DoctrineHelper $doctrineHelper, PropertyAccessor $propertyAccessor)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param array|BasePriceListRelation[] $submitted
     * @param array|BasePriceListRelation[] $existing
     * @param object $targetEntity
     * @param Website $website
     * @return bool
     */
    public function handleChanges(array $submitted, array $existing, $targetEntity, Website $website)
    {
        $hasChanges = $this->removeDeleted($existing, $submitted);
        $hasChanges = $this->persistNew($existing, $submitted, $website, $targetEntity) || $hasChanges;
        if (!$hasChanges && 0 !== count($existing)) {
            $hasChanges = $this->checkCollectionUpdates($existing);
        }

        return $hasChanges;
    }

    /**
     * @param array $existing
     * @return bool
     */
    protected function checkCollectionUpdates(array $existing)
    {
        $manager = $this->doctrineHelper->getEntityManager(current($existing));
        $unitOfWork = $manager->getUnitOfWork();
        $unitOfWork->computeChangeSets();

        foreach ($existing as $relation) {
            if ($unitOfWork->isScheduledForUpdate($relation)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array|BasePriceListRelation[] $existing
     * @param array|BasePriceListRelation[] $submitted
     * @return bool
     */
    protected function removeDeleted(array $existing, array $submitted)
    {
        $hasChanges = false;
        $manager = current($existing) ? $this->doctrineHelper->getEntityManager(current($existing)) : null;
        foreach ($existing as $relation) {
            if (!in_array($relation, $submitted, true)) {
                $manager->remove($relation);
                $hasChanges = true;
            }
        }

        return $hasChanges;
    }

    /**
     * @param BasePriceListRelation $relation
     * @param object|mixed $targetEntity
     */
    protected function setTargetEntity(BasePriceListRelation $relation, $targetEntity)
    {
        $manager = $this->doctrineHelper->getEntityManager($relation);
        $meta = $manager->getClassMetadata(ClassUtils::getClass($relation));
        $associations = $meta->getAssociationsByTargetClass(ClassUtils::getClass($targetEntity));
        foreach ($associations as $association) {
            $this->propertyAccessor->setValue($relation, $association['fieldName'], $targetEntity);
        }
    }

    /**
     * @param array|BasePriceListRelation[] $existing
     * @param array|BasePriceListRelation[] $submitted
     * @param Website $website
     * @param object $targetEntity
     * @return bool
     */
    protected function persistNew(array $existing, array $submitted, Website $website, $targetEntity)
    {
        $hasChanges = false;
        $manager = current($submitted) ? $this->doctrineHelper->getEntityManager(current($submitted)) : null;
        foreach ($submitted as $relation) {
            if (!in_array($relation, $existing, true)) {
                $this->setTargetEntity($relation, $targetEntity);
                $relation->setWebsite($website);
                $manager->persist($relation);
                $hasChanges = true;
            }
        }

        return $hasChanges;
    }
}
