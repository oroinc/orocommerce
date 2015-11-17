<?php

namespace OroB2B\Bundle\ProductBundle\ImportExport\Strategy;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\FallbackBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductVariantLink;
use OroB2B\Bundle\ProductBundle\ImportExport\Event\ProductStrategyEvent;

class ProductStrategy extends LocalizedFallbackValueAwareStrategy
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var BusinessUnit */
    protected $owner;

    /**
     * @var string
     */
    protected $variantLinkClass;

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
     * @param Product $entity
     * @return Product
     */
    protected function beforeProcessEntity($entity)
    {
        $event = new ProductStrategyEvent($entity, $this->context->getValue('itemData'));
        $this->eventDispatcher->dispatch(ProductStrategyEvent::PROCESS_BEFORE, $event);

        $existingEntity = $this->findExistingEntity($entity);
        if (!$existingEntity) {
            return parent::beforeProcessEntity($entity);
        }

        $this->resolveVariantLinkIdentifier($existingEntity, $entity);

        return parent::beforeProcessEntity($entity);
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
     * @param Product $existingEntity
     * @param Product $entity
     * @throws \Exception
     */
    protected function resolveVariantLinkIdentifier($existingEntity, $entity)
    {
        $fields = $this->fieldHelper->getRelations(ClassUtils::getClass($existingEntity), true);
        foreach ($fields as $field) {
            if ($this->isVariantLinkValue($field)) {
                $variantLinks = $this->filterVariantCollections(
                    $this->fieldHelper->getObjectValue($entity, $field['name'])
                );
                $this->fieldHelper->setObjectValue($entity, 'variantLinks', $variantLinks);
            }
        }
    }

    /**
     * @param Collection $variantLinks
     * @return Collection|void
     */
    protected function filterVariantCollections(Collection $variantLinks)
    {
        if ($variantLinks->isEmpty()) {
            return $variantLinks;
        }

        return $variantLinks
            ->filter(
                function (ProductVariantLink $productVariantLink) {
                    $identifier = $this->databaseHelper->getIdentifier($productVariantLink);
                    if ($identifier) {
                        return true;
                    }

                    $fields = $this->fieldHelper->getRelations(ClassUtils::getClass($productVariantLink), true);
                    foreach ($fields as $field) {
                        $childEntity = $this->fieldHelper->getObjectValue($productVariantLink, $field['name']);
                        $childIdentifier = $this->databaseHelper->getIdentifier($childEntity);
                        if ($childIdentifier) {
                            continue;
                        }

                        $existingChildEntity = $this->findExistingEntity($childEntity);
                        if (!$existingChildEntity) {
                            $this->context->incrementErrorEntriesCount();
                            $this->strategyHelper->addValidationErrors(['error'], $this->context);
                            return false;
                        }
                        $this->fieldHelper->setObjectValue($childEntity, 'id', $existingChildEntity->getId());
                    }

                    return true;
                }
            );
    }

    /**
     * @param $field
     * @return bool
     */
    protected function isVariantLinkValue($field)
    {
        return $this->fieldHelper->isRelation($field)
        && is_a($field['related_entity_name'], $this->variantLinkClass, true);
    }
}
