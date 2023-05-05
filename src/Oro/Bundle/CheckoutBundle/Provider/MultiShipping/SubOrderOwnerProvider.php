<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItemsGrouping\GroupLineItemsByConfiguredFields;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;

/**
 * Provides an user that should be used as an owner for child order.
 */
class SubOrderOwnerProvider implements SubOrderOwnerProviderInterface
{
    private SubOrderOrganizationProviderInterface $subOrderOrganizationProvider;
    private ConfigManager $configManager;
    private PropertyAccessorInterface $propertyAccessor;
    private OwnershipMetadataProviderInterface $metadataProvider;
    private ManagerRegistry $doctrine;
    private array $memoryCache = [];

    public function __construct(
        SubOrderOrganizationProviderInterface $subOrderOrganizationProvider,
        ConfigManager $configManager,
        PropertyAccessorInterface $propertyAccessor,
        OwnershipMetadataProviderInterface $metadataProvider,
        ManagerRegistry $doctrine
    ) {
        $this->subOrderOrganizationProvider = $subOrderOrganizationProvider;
        $this->configManager = $configManager;
        $this->propertyAccessor = $propertyAccessor;
        $this->metadataProvider = $metadataProvider;
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritDoc}
     */
    public function getOwner(Collection $lineItems, string $groupingPath): User
    {
        /** @var CheckoutLineItem|false $lineItem */
        $lineItem = $lineItems->first();

        $owner = null;
        if ($lineItem) {
            $ownerSource = $this->getOwnerSource($lineItem, $groupingPath);
            if ($ownerSource instanceof User) {
                $owner = $ownerSource;
            } else {
                $owner = $this->getConfiguredOwner(
                    $this->subOrderOrganizationProvider->getOrganization($lineItems, $groupingPath)
                );
                if (null === $owner) {
                    $owner = $this->determineOwner($ownerSource);
                }
            }
        }
        if (null === $owner) {
            throw new \LogicException('Unable to determine order owner.');
        }

        return $owner;
    }

    private function getOwnerSource(CheckoutLineItem $lineItem, string $groupingPath): ?object
    {
        $propertyPath = null;
        if (GroupLineItemsByConfiguredFields::OTHER_ITEMS_KEY !== $groupingPath) {
            $paths = explode(':', $groupingPath, 2);
            $propertyPath = $paths[0];
        }

        if ($propertyPath) {
            $fieldValue = $this->propertyAccessor->getValue($lineItem, $propertyPath);
            if (\is_object($fieldValue)) {
                return $fieldValue;
            }
        }

        return $this->getDefaultSource($lineItem);
    }

    private function determineOwner(object $ownerSource): ?User
    {
        $owner = $ownerSource instanceof BusinessUnit || $ownerSource instanceof Organization
            ? $ownerSource
            : $this->getObjectOwner($ownerSource);
        if ($owner instanceof User) {
            return $owner;
        }
        if ($owner instanceof BusinessUnit) {
            return $this->getBusinessUnitUser($owner);
        }
        if ($owner instanceof Organization) {
            return $this->getOrganizationUser($owner);
        }

        return null;
    }

    private function getObjectOwner(object $object): ?object
    {
        $ownershipMetadata = $this->metadataProvider->getMetadata(ClassUtils::getRealClass($object));
        if (!$ownershipMetadata->hasOwner()) {
            return null;
        }

        return $this->propertyAccessor->getValue($object, $ownershipMetadata->getOwnerFieldName());
    }

    private function getDefaultSource(CheckoutLineItem $lineItem): object
    {
        return $lineItem->getProduct() ?? $lineItem->getCheckout();
    }

    private function getBusinessUnitUser(BusinessUnit $businessUnit): ?User
    {
        $cacheKey = 'bu:' . $businessUnit->getId();
        if (\array_key_exists($cacheKey, $this->memoryCache)) {
            return $this->memoryCache[$cacheKey];
        }

        $user = $this->getUser('businessUnits', $businessUnit->getId());
        $this->memoryCache[$cacheKey] = $user;

        return $user;
    }

    private function getOrganizationUser(Organization $organization): ?User
    {
        $cacheKey = 'org:' . $organization->getId();
        if (\array_key_exists($cacheKey, $this->memoryCache)) {
            return $this->memoryCache[$cacheKey];
        }

        $user = $this->getUser('organizations', $organization->getId());
        $this->memoryCache[$cacheKey] = $user;

        return $user;
    }

    private function getUser(string $associationName, int $ownerId): ?User
    {
        $user = $this->getUserQueryBuilder($associationName, $ownerId)
            ->andWhere('u.enabled = :enabled')
            ->setParameter('enabled', true)
            ->getQuery()
            ->getOneOrNullResult();
        if (null === $user) {
            $user = $this->getUserQueryBuilder($associationName, $ownerId)
                ->getQuery()
                ->getOneOrNullResult();
        }

        return $user;
    }

    private function getUserQueryBuilder(string $associationName, int $ownerId): QueryBuilder
    {
        return $this->getUserEntityManager()
            ->createQueryBuilder()
            ->from(User::class, 'u')
            ->select('u')
            ->where(':ownerId MEMBER OF u.' . $associationName)
            ->setParameter('ownerId', $ownerId)
            ->setMaxResults(1)
            ->orderBy('u.id');
    }

    private function getConfiguredOwner(Organization $organization): ?User
    {
        $configuredOwnerId = $this->configManager->get(
            'oro_order.order_creation_new_order_owner',
            false,
            false,
            $organization
        );
        if (!$configuredOwnerId) {
            return null;
        }

        return $this->getUserEntityManager()->getReference(User::class, $configuredOwnerId);
    }

    private function getUserEntityManager(): EntityManagerInterface
    {
        return $this->doctrine->getManagerForClass(User::class);
    }
}
