<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\FilterFieldConfig;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\EntityConfigBundle\Attribute\AttributeConfigurationProviderInterface;
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
    private AttributeManager $attributeManager;
    private AttributeTypeRegistry $attributeTypeRegistry;
    private AttributeConfigurationProviderInterface $configurationProvider;

    public function __construct(
        AttributeManager $attributeManager,
        AttributeTypeRegistry $attributeTypeRegistry,
        AttributeConfigurationProviderInterface $configurationProvider
    ) {
        $this->attributeManager = $attributeManager;
        $this->attributeTypeRegistry = $attributeTypeRegistry;
        $this->configurationProvider = $configurationProvider;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function process(ContextInterface $context): void
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
                        $attributeType->getSorterStorageFieldType($attribute),
                        $attributeType->getSortableFieldName($attribute)
                    ));
            }
        }

        if (null !== $searchFilter) {
            $this->setSearchFilterMapping($searchFilter, $searchFilterMapping);
        }
    }

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

    private function isAttributeFilterable(
        SearchAttributeTypeInterface $attributeType,
        FieldConfigModel $attribute
    ): bool {
        return
            $attributeType->isFilterable($attribute)
            && $this->configurationProvider->isAttributeFilterable($attribute);
    }

    private function isAttributeSortable(
        SearchAttributeTypeInterface $attributeType,
        FieldConfigModel $attribute
    ): bool {
        return
            $attributeType->isSortable($attribute)
            && $this->configurationProvider->isAttributeSortable($attribute);
    }

    private function getFilterableFieldName(
        SearchAttributeTypeInterface $attributeType,
        FieldConfigModel $attribute
    ): ?string {
        $names = $attributeType->getFilterableFieldNames($attribute);

        return $names[SearchAttributeTypeInterface::VALUE_MAIN] ?? null;
    }

    private function getSearchFilterMapping(FilterFieldConfig $searchFilter): array
    {
        $options = $searchFilter->getOptions();

        return $options['field_mappings'] ?? [];
    }

    private function setSearchFilterMapping(FilterFieldConfig $searchFilter, array $mapping): void
    {
        $options = $searchFilter->getOptions();
        $options['field_mappings'] = $mapping;
        $searchFilter->setOptions($options);
    }
}
