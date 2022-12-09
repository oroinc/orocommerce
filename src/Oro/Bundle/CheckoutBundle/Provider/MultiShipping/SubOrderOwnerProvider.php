<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItemsGrouping\GroupLineItemsByConfiguredFields;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;

/**
 * Get USER owner for child order according to the logic based on configured grouped field values.
 */
class SubOrderOwnerProvider
{
    private PropertyAccessorInterface $propertyAccessor;
    private OwnershipMetadataProviderInterface $metadataProvider;

    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        OwnershipMetadataProviderInterface $metadataProvider
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->metadataProvider = $metadataProvider;
    }

    public function getOwner(ArrayCollection $lineItems, string $path): User
    {
        /** @var CheckoutLineItem $lineItem */
        $lineItem = $lineItems->first();
        $owner = null;
        if ($lineItem) {
            $ownerSource = $this->getOwnerSource($lineItem, $path);
            $owner = $this->getUserOwnerFallbackValue($ownerSource);
        }

        if (null === $owner) {
            throw new \LogicException('Unable to determine order owner');
        }

        return $owner;
    }

    /**
     * Use line item product as default owner source in case grouped field is not an object.
     *
     * @param CheckoutLineItem $lineItem
     * @param string $fieldPath
     * @return User|Product|null
     */
    private function getOwnerSource(CheckoutLineItem $lineItem, string $fieldPath): ?object
    {
        if ($fieldPath === GroupLineItemsByConfiguredFields::OTHER_ITEMS_KEY) {
            return $this->getDefaultSource($lineItem);
        }

        // Extract value path
        $paths = explode(':', $fieldPath);
        $fieldValue = $this->propertyAccessor->getValue($lineItem, $paths[0]);

        if (is_object($fieldValue)) {
            return $fieldValue;
        }

        return $this->getDefaultSource($lineItem);
    }

    /**
     * Try to get User as owner because Order has USER type ownership.
     *
     * @param $object
     * @return User|null
     */
    private function getUserOwnerFallbackValue($object): ?User
    {
        $owner = $this->getOwnerValue($object);

        if ($owner instanceof User) {
            return $owner;
        }

        if ($owner instanceof BusinessUnit || $owner instanceof Organization) {
            return $owner->getUsers()->count() ? $owner->getUsers()->first() : null;
        }

        return null;
    }

    private function getOwnerValue(object $object): ?object
    {
        // Return incoming object if it is ownership entity.
        if ($this->isOwnershipEntity($object)) {
            return $object;
        }

        $ownershipMetadata = $this->metadataProvider->getMetadata(ClassUtils::getRealClass($object));
        if ($ownershipMetadata->hasOwner()) {
            return $this->propertyAccessor->getValue($object, $ownershipMetadata->getOwnerFieldName());
        }

        return null;
    }

    private function isOwnershipEntity(object $entity): bool
    {
        return $entity instanceof BusinessUnit || $entity instanceof Organization || $entity instanceof User;
    }

    private function getDefaultSource(CheckoutLineItem $lineItem): object
    {
        return $lineItem->getProduct() ?? $lineItem->getCheckout();
    }
}
