<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGPropertiesType;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

/**
 * Provides information about WYSIWYG fields for a specific entity.
 */
class WYSIWYGFieldsProvider
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var ConfigManager */
    private $configManager;

    public function __construct(ManagerRegistry $doctrine, ConfigManager $configManager)
    {
        $this->doctrine = $doctrine;
        $this->configManager = $configManager;
    }

    /**
     * Gets names of all WYSIWYG fields for the given entity.
     *
     * @param string $entityClass
     *
     * @return string[]
     */
    public function getWysiwygFields(string $entityClass): array
    {
        $wysiwygFields = [];
        $em = $this->doctrine->getManagerForClass($entityClass);
        if ($em instanceof EntityManagerInterface) {
            if ($this->configManager->hasConfig($entityClass)) {
                /** @var FieldConfigId[] $fieldConfigIds */
                $fieldConfigIds = $this->configManager->getIds('extend', $entityClass, true);
                foreach ($fieldConfigIds as $fieldConfigId) {
                    if (WYSIWYGType::TYPE === $fieldConfigId->getFieldType()) {
                        $wysiwygFields[] = $fieldConfigId->getFieldName();
                    }
                }
            } else {
                $metadata = $em->getClassMetadata($entityClass);
                $fieldNames = $metadata->getFieldNames();
                foreach ($fieldNames as $fieldName) {
                    if (WYSIWYGType::TYPE === $metadata->getTypeOfField($fieldName)) {
                        $wysiwygFields[] = $fieldName;
                    }
                }
            }
        }

        return $wysiwygFields;
    }

    /**
     * Checks whether the given field is a serialized WYSIWYG field.
     */
    public function isSerializedWysiwygField(string $entityClass, string $fieldName): bool
    {
        $em = $this->doctrine->getManagerForClass($entityClass);
        if (!$em instanceof EntityManagerInterface) {
            return false;
        }
        if (!$this->configManager->hasConfig($entityClass, $fieldName)) {
            return false;
        }

        $fieldConfig = $this->configManager->getFieldConfig('extend', $entityClass, $fieldName);

        return $fieldConfig->is('is_serialized');
    }

    /**
     * Gets names of all WYSIWYG attributes for the given entity.
     *
     * @param string $entityClass
     *
     * @return string[]
     */
    public function getWysiwygAttributes(string $entityClass): array
    {
        $wysiwygAttributes = [];
        $wysiwygFields = $this->getWysiwygFields($entityClass);
        foreach ($wysiwygFields as $fieldName) {
            if ($this->isWysiwygAttribute($entityClass, $fieldName)) {
                $wysiwygAttributes[] = $fieldName;
            }
        }

        return $wysiwygAttributes;
    }

    /**
     * Checks whether the given field is an WYSIWYG attributes.
     */
    public function isWysiwygAttribute(string $entityClass, string $fieldName): bool
    {
        return
            $this->configManager->hasConfig($entityClass, $fieldName)
            && $this->configManager->getFieldConfig('attribute', $entityClass, $fieldName)->is('is_attribute');
    }

    /**
     * Gets the name of "style" additional field for the given WYSIWYG field.
     */
    public function getWysiwygStyleField(string $entityClass, string $wysiwygFieldName): string
    {
        return $this->getWysiwygAdditionalField(
            $entityClass,
            $wysiwygFieldName,
            WYSIWYGStyleType::TYPE_SUFFIX,
            WYSIWYGStyleType::TYPE,
            'style'
        );
    }

    /**
     * Gets the name of "properties" additional field for the given WYSIWYG field.
     */
    public function getWysiwygPropertiesField(string $entityClass, string $wysiwygFieldName): string
    {
        return $this->getWysiwygAdditionalField(
            $entityClass,
            $wysiwygFieldName,
            WYSIWYGPropertiesType::TYPE_SUFFIX,
            WYSIWYGPropertiesType::TYPE,
            'properties'
        );
    }

    private function getWysiwygAdditionalField(
        string $entityClass,
        string $wysiwygFieldName,
        string $wysiwygAdditionalFieldSuffix,
        string $wysiwygAdditionalFieldType,
        string $wysiwygAdditionalFieldDescription
    ): string {
        $em = $this->doctrine->getManagerForClass($entityClass);
        if (!$em instanceof EntityManagerInterface) {
            throw new \InvalidArgumentException(sprintf('The class "%s" is non manageable entity.', $entityClass));
        }
        $metadata = $em->getClassMetadata($entityClass);
        if (WYSIWYGType::TYPE !== $this->getTypeOfField($metadata, $wysiwygFieldName)) {
            throw new \InvalidArgumentException(sprintf(
                'The field "%s::%s" is not WYSIWYG field.',
                $entityClass,
                $wysiwygFieldName
            ));
        }

        $wysiwygAdditionalField = $wysiwygFieldName . $wysiwygAdditionalFieldSuffix;
        if ($this->isWysiwygAdditionalField($metadata, $wysiwygAdditionalField, $wysiwygAdditionalFieldType)) {
            return $wysiwygAdditionalField;
        }

        $anotherWysiwygAdditionalField = $wysiwygFieldName . $this->camelize($wysiwygAdditionalFieldSuffix);
        if ($anotherWysiwygAdditionalField !== $wysiwygAdditionalField
            && $this->isWysiwygAdditionalField($metadata, $anotherWysiwygAdditionalField, $wysiwygAdditionalFieldType)
        ) {
            return $anotherWysiwygAdditionalField;
        }

        throw new \InvalidArgumentException(sprintf(
            'The "%s" field for the "%s::%s" WYSIWYG field was not found.',
            $wysiwygAdditionalFieldDescription,
            $entityClass,
            $wysiwygFieldName
        ));
    }

    private function isWysiwygAdditionalField(ClassMetadata $metadata, string $fieldName, string $fieldType): bool
    {
        return $this->getTypeOfField($metadata, $fieldName) === $fieldType;
    }

    private function getTypeOfField(ClassMetadata $metadata, string $fieldName): ?string
    {
        $entityClass = $metadata->getName();
        if ($metadata->hasField($fieldName)) {
            return $metadata->getTypeOfField($fieldName);
        }
        if (!$this->configManager->hasConfig($entityClass, $fieldName)) {
            return null;
        }

        /** @var FieldConfigId $fieldConfigId */
        $fieldConfigId = $this->configManager->getId('extend', $entityClass, $fieldName);

        return $fieldConfigId->getFieldType();
    }

    /**
     * Notes: should be the same as {@see \Symfony\Component\PropertyAccess\PropertyAccessor::camelize}.
     */
    private function camelize(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
    }
}
