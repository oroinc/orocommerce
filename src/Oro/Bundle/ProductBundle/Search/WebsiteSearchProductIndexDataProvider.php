<?php

namespace Oro\Bundle\ProductBundle\Search;

use Oro\Bundle\EntityConfigBundle\Attribute\AttributeConfigurationProvider;
use Oro\Bundle\EntityConfigBundle\Attribute\AttributeTypeRegistry;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\SearchableAttributeTypeInterface;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexDataProvider;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class WebsiteSearchProductIndexDataProvider implements ProductIndexDataProviderInterface
{
    /** @var AttributeTypeRegistry */
    protected $attributeTypeRegistry;

    /** @var AttributeConfigurationProvider */
    protected $configurationProvider;

    /** @var ProductIndexFieldsProvider */
    protected $indexFieldsProvider;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /**
     * @param AttributeTypeRegistry $attributeTypeRegistry
     * @param AttributeConfigurationProvider $configurationProvider
     * @param ProductIndexFieldsProvider $indexFieldsProvider
     * @param PropertyAccessor $propertyAccessor
     */
    public function __construct(
        AttributeTypeRegistry $attributeTypeRegistry,
        AttributeConfigurationProvider $configurationProvider,
        ProductIndexFieldsProvider $indexFieldsProvider,
        PropertyAccessor $propertyAccessor
    ) {
        $this->attributeTypeRegistry = $attributeTypeRegistry;
        $this->configurationProvider = $configurationProvider;
        $this->indexFieldsProvider = $indexFieldsProvider;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexData(Product $product, FieldConfigModel $attribute, array $localizations)
    {
        $data = [];

        $attributeType = $this->getAttributeType($attribute);
        if ($attributeType) {
            if ($attributeType->isLocalizable($attribute)) {
                $data = array_reduce(
                    array_map(
                        function (Localization $localization) use ($product, $attribute, $attributeType) {
                            return $this->getFields($product, $attribute, $attributeType, $localization);
                        },
                        $localizations
                    ),
                    'array_merge',
                    []
                );
            } else {
                $data = $this->getFields($product, $attribute, $attributeType);
            }
        }

        return $data;
    }

    /**
     * @param Product $product
     * @param FieldConfigModel $attribute
     * @param SearchableAttributeTypeInterface $attributeType
     * @param Localization|null $localization
     *
     * @return array|ProductIndexDataModel[]
     */
    protected function getFields(
        Product $product,
        FieldConfigModel $attribute,
        SearchableAttributeTypeInterface $attributeType,
        Localization $localization = null
    ) {
        $fields = [];
        $originalValue = $this->propertyAccessor->getValue($product, $attribute->getFieldName());

        $isForceIndexed = $this->indexFieldsProvider->isForceIndexed($attribute->getFieldName());
        $placeholders = $localization ? [LocalizationIdPlaceholder::NAME => $localization->getId()] : [];
        $isLocalized = $localization !== null;

        $filterFieldName = null;
        if ($this->isFilterable($attribute, $attributeType, $isForceIndexed)) {
            $filterFieldName = $attributeType->getFilterableFieldName($attribute);

            $fields = $this->addToFields(
                $fields,
                $filterFieldName,
                $attributeType->getFilterableValue($attribute, $originalValue, $localization),
                $placeholders,
                $isLocalized,
                false
            );
        }

        if ($this->isSortable($attribute, $attributeType, $isForceIndexed)) {
            $sortFieldName = $attributeType->getSortableFieldName($attribute);

            if ($filterFieldName !== $sortFieldName) {
                $fields = $this->addToFields(
                    $fields,
                    $sortFieldName,
                    $attributeType->getSortableValue($attribute, $originalValue, $localization),
                    $placeholders,
                    $isLocalized,
                    false
                );
            }
        }

        if ($this->configurationProvider->isAttributeSearchable($attribute)) {
            $fields = $this->addToFields(
                $fields,
                $isLocalized ? IndexDataProvider::ALL_TEXT_L10N_FIELD : IndexDataProvider::ALL_TEXT_FIELD,
                $attributeType->getSearchableValue($attribute, $originalValue, $localization),
                $placeholders,
                $isLocalized,
                true
            );
        }

        return $fields;
    }

    /**
     * @param array $fields
     * @param string $fieldName
     * @param array|string $fieldValue
     * @param array $placeholders
     * @param bool $localized
     * @param bool $searchable
     * @return array
     */
    private function addToFields(array $fields, $fieldName, $fieldValue, array $placeholders, $localized, $searchable)
    {
        if (!is_array($fieldValue)) {
            $fieldValue = [$fieldName => $fieldValue];
        }

        foreach ($fieldValue as $key => $value) {
            $fields[] = new ProductIndexDataModel(
                $key,
                $value,
                $placeholders,
                $localized,
                $searchable
            );
        }

        return $fields;
    }

    /**
     * @param FieldConfigModel $attribute
     *
     * @return null|SearchableAttributeTypeInterface
     */
    protected function getAttributeType(FieldConfigModel $attribute)
    {
        if (!$this->configurationProvider->isAttributeActive($attribute)) {
            return null;
        }

        $attributeType = $this->attributeTypeRegistry->getAttributeType($attribute);
        if (!$attributeType instanceof SearchableAttributeTypeInterface) {
            return null;
        }

        return $attributeType;
    }

    /**
     * @param FieldConfigModel $attribute
     * @param SearchableAttributeTypeInterface $type
     * @param bool $force
     *
     * @return bool
     */
    private function isFilterable(FieldConfigModel $attribute, SearchableAttributeTypeInterface $type, $force)
    {
        return $type->isFilterable($attribute) &&
            ($force || $this->configurationProvider->isAttributeFilterable($attribute));
    }

    /**
     * @param FieldConfigModel $attribute
     * @param SearchableAttributeTypeInterface $type
     * @param bool $force
     *
     * @return bool
     */
    private function isSortable(FieldConfigModel $attribute, SearchableAttributeTypeInterface $type, $force)
    {
        return $type->isSortable($attribute) &&
            ($force || $this->configurationProvider->isAttributeSortable($attribute));
    }
}
