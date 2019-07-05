<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\FilterFieldConfig;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\EntityConfigBundle\Attribute\AttributeConfigurationProvider;
use Oro\Bundle\EntityConfigBundle\Attribute\AttributeTypeRegistry;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\SearchAttributeTypeInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Configures product attributes for ProductSearch entity:
 * * adds mapping for all filterable custom product attributes for "searchQuery" filter
 * * adds sorters for all sortable custom product attributes
 * By performance reasons these actions are done in one processor.
 */
class ConfigureProductSearchAttributes implements ProcessorInterface
{
    /** @var AttributeManager */
    private $attributeManager;

    /** @var AttributeTypeRegistry */
    private $attributeTypeRegistry;

    /** @var AttributeConfigurationProvider */
    private $configurationProvider;

    /**
     * @param AttributeManager               $attributeManager
     * @param AttributeTypeRegistry          $attributeTypeRegistry
     * @param AttributeConfigurationProvider $configurationProvider
     */
    public function __construct(
        AttributeManager $attributeManager,
        AttributeTypeRegistry $attributeTypeRegistry,
        AttributeConfigurationProvider $configurationProvider
    ) {
        $this->attributeManager = $attributeManager;
        $this->attributeTypeRegistry = $attributeTypeRegistry;
        $this->configurationProvider = $configurationProvider;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $searchFilterMapping = null;
        $searchFilter = $context->getConfigOfFilters()->getField('searchQuery');
        if (null !== $searchFilter) {
            $searchFilterMapping = $this->getSearchFilterMapping($searchFilter);
        }

        $sorters = $context->getConfigOfSorters();

        $attributes = $this->attributeManager->getAttributesByClass(Product::class);
        foreach ($attributes as $attribute) {
            $attributeType = $this->getAttributeType($attribute);
            if (null === $attributeType) {
                continue;
            }

            $fieldName = $attribute->getFieldName();

            if (!isset($searchFilterMapping[$fieldName])
                && $this->isAttributeFilterable($attributeType, $attribute)
            ) {
                $filterableFieldName = $this->getFilterableFieldName($attributeType, $attribute);
                if ($filterableFieldName && $filterableFieldName !== $fieldName) {
                    $searchFilterMapping[$fieldName] = $filterableFieldName;
                }
            }

            if (!$sorters->hasField($fieldName)
                && $this->isAttributeSortable($attributeType, $attribute)
            ) {
                $sorters->addField($fieldName)
                    ->setPropertyPath(sprintf(
                        '%s.%s',
                        $attributeType->getSorterStorageFieldType(),
                        $attributeType->getSortableFieldName($attribute)
                    ));
            }
        }

        if (null !== $searchFilter) {
            $this->setSearchFilterMapping($searchFilter, $searchFilterMapping);
        }
    }

    /**
     * @param FieldConfigModel $attribute
     *
     * @return SearchAttributeTypeInterface|null
     */
    private function getAttributeType(FieldConfigModel $attribute): ?SearchAttributeTypeInterface
    {
        if (!$this->configurationProvider->isAttributeCustom($attribute)
            || !$this->configurationProvider->isAttributeActive($attribute)
        ) {
            return null;
        }

        $attributeType = $this->attributeTypeRegistry->getAttributeType($attribute);
        if (!$attributeType instanceof SearchAttributeTypeInterface) {
            return null;
        }

        return $attributeType;
    }

    /**
     * @param SearchAttributeTypeInterface $attributeType
     * @param FieldConfigModel             $attribute
     *
     * @return bool
     */
    private function isAttributeFilterable(
        SearchAttributeTypeInterface $attributeType,
        FieldConfigModel $attribute
    ): bool {
        return
            $attributeType->isFilterable($attribute)
            && $this->configurationProvider->isAttributeFilterable($attribute);
    }

    /**
     * @param SearchAttributeTypeInterface $attributeType
     * @param FieldConfigModel             $attribute
     *
     * @return bool
     */
    private function isAttributeSortable(
        SearchAttributeTypeInterface $attributeType,
        FieldConfigModel $attribute
    ): bool {
        return
            $attributeType->isSortable($attribute)
            && $this->configurationProvider->isAttributeSortable($attribute);
    }

    /**
     * @param SearchAttributeTypeInterface $attributeType
     * @param FieldConfigModel             $attribute
     *
     * @return string|null
     */
    private function getFilterableFieldName(
        SearchAttributeTypeInterface $attributeType,
        FieldConfigModel $attribute
    ): ?string {
        $names = $attributeType->getFilterableFieldNames($attribute);
        if (!isset($names[SearchAttributeTypeInterface::VALUE_MAIN])) {
            return null;
        }

        return $names[SearchAttributeTypeInterface::VALUE_MAIN];
    }

    /**
     * @param SearchAttributeTypeInterface $attributeType
     *
     * @return string|null
     */
    private function getFilterableFieldType(SearchAttributeTypeInterface $attributeType): ?string
    {
        $types = $attributeType->getFilterStorageFieldTypes();
        if (!isset($types[SearchAttributeTypeInterface::VALUE_MAIN])) {
            return null;
        }

        return $types[SearchAttributeTypeInterface::VALUE_MAIN];
    }

    /**
     * @param FilterFieldConfig $searchFilter
     *
     * @return array
     */
    private function getSearchFilterMapping(FilterFieldConfig $searchFilter): array
    {
        $options = $searchFilter->getOptions();

        return $options['field_mappings'] ?? [];
    }

    /**
     * @param FilterFieldConfig $searchFilter
     * @param array             $mapping
     */
    private function setSearchFilterMapping(FilterFieldConfig $searchFilter, array $mapping): void
    {
        $options = $searchFilter->getOptions();
        $options['field_mappings'] = $mapping;
        $searchFilter->setOptions($options);
    }
}
