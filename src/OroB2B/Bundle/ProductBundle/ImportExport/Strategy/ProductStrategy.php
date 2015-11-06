<?php

namespace OroB2B\Bundle\ProductBundle\ImportExport\Strategy;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use Oro\Bundle\UserBundle\Entity\User;
use OroB2B\Bundle\FallbackBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductStrategy extends LocalizedFallbackValueAwareStrategy
{
    /** @var OwnershipMetadataProvider */
    protected $ownershipMetadataProvider;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var BusinessUnit */
    protected $owner;

    /**
     * @param mixed $ownershipMetadataProvider
     */
    public function setOwnershipMetadataProvider($ownershipMetadataProvider)
    {
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
    }

    /**
     * @param SecurityFacade $securityFacade
     */
    public function setSecurityFacade($securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param Product $entity
     * @return Product
     */
    protected function afterProcessEntity($entity)
    {
        $this->populateOwner($entity);

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

        $businessUnit = $user->getOwner();
        $ownerField = $this->ownershipMetadataProvider->getMetadata(ClassUtils::getClass($entity))->getOwnerFieldName();

        $this->fieldHelper->setObjectValue($entity, $ownerField, $businessUnit);
    }
}
