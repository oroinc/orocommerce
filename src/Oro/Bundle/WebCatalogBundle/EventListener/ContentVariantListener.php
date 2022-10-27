<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Component\WebCatalog\ContentVariantEntityProviderInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Security\Acl\Util\ClassUtils;

/**
 * Sets owner same to web catalog to entities created by Content Variants.
 */
class ContentVariantListener
{
    /**
     * @var ContentVariantTypeRegistry
     */
    private $typeRegistry;

    /**
     * @var OwnershipMetadataProviderInterface
     */
    private $metadataProvider;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    public function __construct(
        ContentVariantTypeRegistry $typeRegistry,
        OwnershipMetadataProviderInterface $metadataProvider,
        DoctrineHelper $doctrineHelper
    ) {
        $this->typeRegistry = $typeRegistry;
        $this->metadataProvider = $metadataProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    public function prePersist(ContentVariant $contentVariant)
    {
        $this->fillOwnershipForNewEntities($contentVariant);
    }

    public function preUpdate(ContentVariant $contentVariant)
    {
        $this->fillOwnershipForNewEntities($contentVariant);
    }

    private function fillOwnershipForNewEntities(ContentVariant $contentVariant): void
    {
        if (!$contentVariant->getNode()) {
            return;
        }

        $type = $this->typeRegistry->getContentVariantTypeByContentVariant($contentVariant);
        if (!$type instanceof ContentVariantEntityProviderInterface) {
            return;
        }

        $entity = $type->getAttachedEntity($contentVariant);
        $businessUnit = $contentVariant->getNode()->getWebCatalog()->getOwner();
        $organization = $contentVariant->getNode()->getWebCatalog()->getOrganization();

        if ($entity instanceof Collection) {
            foreach ($entity as $item) {
                $this->setEntityOwner($item, $businessUnit, $organization);
            }
        } else {
            $this->setEntityOwner($entity, $businessUnit, $organization);
        }
    }

    /**
     * @param object $object
     * @param string $property
     * @param object $value
     */
    private function setValue($object, $property, $value): void
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        try {
            $this->propertyAccessor->setValue($object, $property, $value);
        } catch (NoSuchPropertyException $e) {
            try {
                $reflectionClass = new \ReflectionClass($object);
                $reflectionProperty = $reflectionClass->getProperty($property);
                $reflectionProperty->setAccessible(true);

                $reflectionProperty->setValue($object);
            } catch (\ReflectionException $ex) {
            }
        }
    }

    /**
     * @param object $entity
     * @param BusinessUnit|null $businessUnit
     * @param OrganizationInterface|null $organization
     */
    private function setEntityOwner(
        $entity,
        ?BusinessUnit $businessUnit,
        ?OrganizationInterface $organization
    ): void {
        // Skip processing if organization not passed
        if (!$organization) {
            return;
        }

        // Set owner only to new entities created with Content Variant
        if ($this->doctrineHelper->getSingleEntityIdentifier($entity)) {
            return;
        }

        // Skip entities without ownership
        $metadata = $this->metadataProvider->getMetadata(ClassUtils::getRealClass($entity));
        if (!$metadata->hasOwner()) {
            return;
        }

        switch ($metadata->getOwnerType()) {
            case OwnershipMetadata::OWNER_TYPE_BUSINESS_UNIT:
                if ($businessUnit) {
                    $this->setValue($entity, $metadata->getOwnerFieldName(), $businessUnit);
                }
                $this->setValue($entity, $metadata->getOrganizationFieldName(), $organization);

                break;
            case OwnershipMetadata::OWNER_TYPE_USER:
                $this->setValue($entity, $metadata->getOrganizationFieldName(), $organization);

                break;
            case OwnershipMetadata::OWNER_TYPE_ORGANIZATION:
                $this->setValue($entity, $metadata->getOwnerFieldName(), $organization);

                break;
        }
    }
}
