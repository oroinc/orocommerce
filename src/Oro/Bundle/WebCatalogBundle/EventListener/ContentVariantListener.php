<?php
declare(strict_types=1);

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
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;

/**
 * Sets owner same to web catalog to entities created by Content Variants.
 */
class ContentVariantListener
{
    private ContentVariantTypeRegistry         $typeRegistry;
    private OwnershipMetadataProviderInterface $metadataProvider;
    private DoctrineHelper                     $doctrineHelper;
    private PropertyAccessorInterface          $propertyAccessor;

    public function __construct(
        ContentVariantTypeRegistry $typeRegistry,
        OwnershipMetadataProviderInterface $metadataProvider,
        DoctrineHelper $doctrineHelper,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->typeRegistry     = $typeRegistry;
        $this->metadataProvider = $metadataProvider;
        $this->doctrineHelper   = $doctrineHelper;
        $this->propertyAccessor = $propertyAccessor;
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

    private function setValue(object $object, string $property, object $value): void
    {
        $this->propertyAccessor->setValue($object, $property, $value);
    }

    /**
     * @param object $entity
     * @param BusinessUnit|null $businessUnit
     * @param OrganizationInterface|null $organization
     */
    private function setEntityOwner(
        object $entity,
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
