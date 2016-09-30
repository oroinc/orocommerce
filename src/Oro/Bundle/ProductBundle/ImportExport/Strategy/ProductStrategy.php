<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Strategy;

use Oro\Bundle\LocaleBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductStrategyEvent;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

class ProductStrategy extends LocalizedFallbackValueAwareStrategy
{
    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var BusinessUnit
     */
    protected $owner;

    /**
     * @var string
     */
    protected $variantLinkClass;

    /**
     * @var string
     */
    protected $unitPrecisionClass;

    /**
     * @var Product
     */
    protected $product;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function setSecurityFacade($securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param string $variantLinkClass
     */
    public function setVariantLinkClass($variantLinkClass)
    {
        $this->variantLinkClass = $variantLinkClass;
    }

    /**
     * @param string $unitPrecisionClass
     */
    public function setUnitPrecisionClass($unitPrecisionClass)
    {
        $this->unitPrecisionClass = $unitPrecisionClass;
    }

    /**
     * @param Product $entity
     * @return Product
     */
    protected function beforeProcessEntity($entity)
    {
        $data = $this->context->getValue('itemData');

        if (array_key_exists('additionalUnitPrecisions', $data)) {
            $data['unitPrecisions'] = $data['additionalUnitPrecisions'];
            unset($data['additionalUnitPrecisions']);
        }

        $this->context->setValue('itemData', $data);
        $event = new ProductStrategyEvent($entity, $this->context->getValue('itemData'));
        $this->eventDispatcher->dispatch(ProductStrategyEvent::PROCESS_BEFORE, $event);

        $processedEntity = parent::beforeProcessEntity($entity);
        if ($processedEntity instanceof Product) {
            $this->product = $processedEntity;
        }
        return $processedEntity;
    }

    /**
     * @param object           $entity
     * @param bool             $isFullData
     * @param bool             $isPersistNew
     * @param mixed|array|null $itemData
     * @param array            $searchContext
     * @param bool             $entityIsRelation
     *
     * @return null|object
     */
    protected function processEntity(
        $entity,
        $isFullData = false,
        $isPersistNew = false,
        $itemData = null,
        array $searchContext = [],
        $entityIsRelation = false
    ) {
        /*if ($entity instanceof $this->unitPrecisionClass) {
            $this->product = $entity->getProduct();
        }*/
        return parent::processEntity($entity, $isFullData, $isPersistNew, $itemData, $searchContext, $entityIsRelation);
    }

    /**
     * @param Product $entity
     * @return Product
     */
    protected function afterProcessEntity($entity)
    {
        $this->populateOwner($entity);

        $event = new ProductStrategyEvent($entity, $this->context->getValue('itemData'));
        $this->eventDispatcher->dispatch(ProductStrategyEvent::PROCESS_AFTER, $event);

        return parent::afterProcessEntity($entity);
    }

    /**
     * @param Product $entity
     */
    protected function populateOwner(Product $entity)
    {
        if (false === $this->owner) {
            return;
        }

        if ($this->owner) {
            $entity->setOwner($this->owner);

            return;
        }

        /** @var User $user */
        $user = $this->securityFacade->getLoggedUser();
        if (!$user) {
            $this->owner = false;

            return;
        }

        $this->owner = $this->databaseHelper->getEntityReference($user->getOwner());

        $entity->setOwner($this->owner);
    }

    /**
     * {@inheritdoc}
     */
    protected function findEntityByIdentityValues($entityName, array $identityValues)
    {
        if (is_a($entityName, $this->unitPrecisionClass, true)) {
            $identityValues['product'] = clone $this->product;
        }
        if (is_a($entityName, $this->variantLinkClass, true)) {
            $newIdentityValues = [];
            foreach ($identityValues as $entityFieldName => $entity) {
                if ($this->databaseHelper->getIdentifier($entity)) {
                    $newIdentityValues[$entityFieldName] = $entity;
                } else {
                    $existingEntity = $this->findExistingEntity($entity);

                    if (!$existingEntity) {
                        return null;
                    }

                    $newIdentityValues[$entityFieldName] = $existingEntity;
                }
            }
            $identityValues = $newIdentityValues;
        }

        return parent::findEntityByIdentityValues($entityName, $identityValues);
    }
}
