<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentEntityConfigProviderInterface;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGPropertiesType;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;

/**
 * Overrides base provider to add extra condition for wysiwyg_style field type when resolving field config.
 */
class AttachmentEntityConfigProvider implements AttachmentEntityConfigProviderInterface
{
    private const NOT_CONFIGURABLE_TYPES = [
        WYSIWYGStyleType::TYPE => WYSIWYGStyleType::TYPE_SUFFIX,
        WYSIWYGPropertiesType::TYPE => WYSIWYGPropertiesType::TYPE_SUFFIX,
    ];

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var AttachmentEntityConfigProviderInterface */
    private $innerAttachmentEntityConfigProvider;

    public function __construct(
        ManagerRegistry $doctrine,
        AttachmentEntityConfigProviderInterface $innerAttachmentEntityConfigProvider
    ) {
        $this->doctrine = $doctrine;
        $this->innerAttachmentEntityConfigProvider = $innerAttachmentEntityConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldConfig(string $entityClass, string $fieldName): ?ConfigInterface
    {
        if (!$entityClass) {
            return null;
        }

        $entityManager = $this->doctrine->getManagerForClass($entityClass);
        if (!$entityManager) {
            return null;
        }

        $classMetadata = $entityManager->getClassMetadata($entityClass);

        foreach (self::NOT_CONFIGURABLE_TYPES as $notConfigurableType => $notConfigurableTypeSuffix) {
            if ($classMetadata->getTypeOfField($fieldName) === $notConfigurableType) {
                // Field type is not configurable, so use main wysiwyg field instead.
                $fieldName = substr_replace($fieldName, '', -strlen($notConfigurableTypeSuffix));
            }
        }

        return $this->innerAttachmentEntityConfigProvider->getFieldConfig($entityClass, $fieldName);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityConfig(string $entityClass): ?ConfigInterface
    {
        return $this->innerAttachmentEntityConfigProvider->getEntityConfig($entityClass);
    }
}
