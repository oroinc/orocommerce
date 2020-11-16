<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGPropertiesType;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

/**
 * Provides information about WYSIWYG fields for a specific entity.
 */
class WYSIWYGFieldsProvider
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var ConfigManager */
    private $configManager;

    /**
     * @param ManagerRegistry $doctrine
     * @param ConfigManager   $configManager
     */
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
            $metadata = $em->getClassMetadata($entityClass);
            $fieldNames = $metadata->getFieldNames();
            foreach ($fieldNames as $fieldName) {
                if (WYSIWYGType::TYPE === $metadata->getTypeOfField($fieldName)) {
                    $wysiwygFields[] = $fieldName;
                }
            }
        }

        return $wysiwygFields;
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
            if ($this->configManager->hasConfig($entityClass, $fieldName)
                && $this->configManager->getFieldConfig('attribute', $entityClass, $fieldName)->is('is_attribute')
            ) {
                $wysiwygAttributes[] = $fieldName;
            }
        }

        return $wysiwygAttributes;
    }

    /**
     * Gets the name of "style" additional field for the given WYSIWYG field.
     *
     * @param string $entityClass
     * @param string $wysiwygFieldName
     *
     * @return string
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
     *
     * @param string $entityClass
     * @param string $wysiwygFieldName
     *
     * @return string
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

    /**
     * @param string $entityClass
     * @param string $wysiwygFieldName
     * @param string $wysiwygAdditionalFieldSuffix
     * @param string $wysiwygAdditionalFieldType
     * @param string $wysiwygAdditionalFieldDescription
     *
     * @return string
     */
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
        if (WYSIWYGType::TYPE !== $metadata->getTypeOfField($wysiwygFieldName)) {
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

    /**
     * @param ClassMetadata $metadata
     * @param string        $fieldName
     * @param string        $fieldType
     *
     * @return bool
     */
    private function isWysiwygAdditionalField(ClassMetadata $metadata, string $fieldName, string $fieldType): bool
    {
        return
            $metadata->hasField($fieldName)
            && $metadata->getTypeOfField($fieldName) === $fieldType;
    }

    /**
     * Notes: should be the same as {@see \Symfony\Component\PropertyAccess\PropertyAccessor::camelize}.
     *
     * @param string $string
     *
     * @return string
     */
    private function camelize(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
    }
}
