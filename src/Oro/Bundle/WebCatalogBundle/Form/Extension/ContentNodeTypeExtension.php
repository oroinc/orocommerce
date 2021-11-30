<?php

namespace Oro\Bundle\WebCatalogBundle\Form\Extension;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Form\Type\ContentNodeType;
use Oro\Component\WebCatalog\ContentVariantEntityProviderInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;

/**
 * This extension to fix any content variants' organization.
 */
class ContentNodeTypeExtension extends AbstractTypeExtension
{
    private ContentVariantTypeRegistry $typeRegistry;

    private OwnershipMetadataProviderInterface $metadataProvider;

    private DoctrineHelper $doctrineHelper;

    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        ContentVariantTypeRegistry $typeRegistry,
        OwnershipMetadataProviderInterface $metadataProvider,
        DoctrineHelper $doctrineHelper,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->typeRegistry = $typeRegistry;
        $this->metadataProvider = $metadataProvider;
        $this->doctrineHelper = $doctrineHelper;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [ContentNodeType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 255);
    }

    public function onPostSubmit(FormEvent $event): void
    {
        $node = $event->getData();
        if ($node instanceof ContentNode) {
            $organization = $node->getWebCatalog() ? $node->getWebCatalog()->getOrganization() : null;
            if (!is_null($organization)) {
                $this->updateOrganization($node->getContentVariants(), $organization);
            }
        }
    }

    private function updateOrganization(iterable $collection, OrganizationInterface $organization): void
    {
        foreach ($collection as $contentVariant) {
            $type = $this->typeRegistry->getContentVariantTypeByContentVariant($contentVariant);
            if (!$type instanceof ContentVariantEntityProviderInterface) {
                return;
            }

            $entity = $type->getAttachedEntity($contentVariant);
            if ($entity instanceof Collection) {
                foreach ($entity as $item) {
                    $this->setOrganization($item, $organization);
                }
            } else {
                $this->setOrganization($entity, $organization);
            }
        }
    }

    private function setOrganization($entity, OrganizationInterface $organization): void
    {
        if ($this->doctrineHelper->getSingleEntityIdentifier($entity)) {
            return;
        }

        $metadata = $this->metadataProvider->getMetadata(ClassUtils::getRealClass($entity));
        if (!$metadata->hasOwner()) {
            return;
        }

        switch ($metadata->getOwnerType()) {
            case OwnershipMetadata::OWNER_TYPE_BUSINESS_UNIT:
            case OwnershipMetadata::OWNER_TYPE_USER:
                $this->setEntityValue($entity, $metadata->getOrganizationFieldName(), $organization);

                break;
            case OwnershipMetadata::OWNER_TYPE_ORGANIZATION:
                $this->setEntityValue($entity, $metadata->getOwnerFieldName(), $organization);

                break;
        }
    }

    /**
     * @param object $object
     * @param string $property
     * @param object $value
     */
    private function setEntityValue($object, $property, $value): void
    {
        if ($this->propertyAccessor->isWritable($object, $property)) {
            $this->propertyAccessor->setValue($object, $property, $value);
        }
    }
}
