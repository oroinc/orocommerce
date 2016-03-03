<?php

namespace OroB2B\Bundle\PricingBundle\Form;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;

class PriceListWithPriorityCollectionHandler
{
    /**
     * @var bool
     */
    protected $hasChanges = false;

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
        $this->removeDeleted($existing, $submitted);
        $this->persistNew($existing, $submitted, $website, $targetEntity);
        if (!$this->hasChanges && 0 !== count($existing)) {
            $this->hasChanges = $this->checkCollectionUpdates($existing);
        }

        return $this->hasChanges;
    }

    /**
     * @param array $existing
     * @return bool
     */
    protected function checkCollectionUpdates(array $existing)
    {
        $manager = $this->doctrineHelper->getEntityManager(ClassUtils::getClass(current($existing)));
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
     */
    protected function removeDeleted(array $existing, array $submitted)
    {
        if (count($existing) === 0) {
            return;
        }

        $manager = $this->doctrineHelper->getEntityManager(current($existing));
        foreach ($existing as $relation) {
            if (!in_array($relation, $submitted)) {
                $manager->remove($relation);
                $this->hasChanges = true;
            }
        }
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
     * @param $targetEntity
     */
    protected function persistNew(array $existing, array $submitted, Website $website, $targetEntity)
    {
        if (count($submitted) === 0) {
            return;
        }

        $manager = $this->doctrineHelper->getEntityManager(current($submitted));
        foreach ($submitted as $relation) {
            if (!in_array($relation, $existing)) {
                $this->setTargetEntity($relation, $targetEntity);
                $relation->setWebsite($website);
                $manager->persist($relation);
                $this->hasChanges = true;
            }
        }
    }
}
