<?php

namespace Oro\Bundle\ProductBundle\DataGrid\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\AttributeConfigurationProvider;
use Oro\Bundle\EntityConfigBundle\Attribute\AttributeTypeRegistry;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterTypeInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\SearchableAttributeTypeInterface;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\EnumIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;

/**
 * Updates configuration of frontend products grid and add filter or sorter on it
 * depends on product attributes configuration
 */
class FrontendProductGridEventListener
{
    /** @var AttributeManager */
    protected $attributeManager;

    /** @var AttributeTypeRegistry */
    protected $attributeTypeRegistry;

    /** @var AttributeConfigurationProvider */
    protected $configurationProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param AttributeManager $attributeManager
     * @param AttributeTypeRegistry $attributeTypeRegistry
     * @param AttributeConfigurationProvider $configurationProvider
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        AttributeManager $attributeManager,
        AttributeTypeRegistry $attributeTypeRegistry,
        AttributeConfigurationProvider $configurationProvider,
        DoctrineHelper $doctrineHelper
    ) {
        $this->attributeManager = $attributeManager;
        $this->attributeTypeRegistry = $attributeTypeRegistry;
        $this->configurationProvider = $configurationProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param PreBuild $event
     */
    public function onPreBuild(PreBuild $event)
    {
        $config = $event->getConfig();
        $attrs = $this->attributeManager->getAttributesByClass(Product::class);

        foreach ($attrs as $attr) {
            $attributeType = $this->getAttributeType($attr);
            if (!$attributeType) {
                continue;
            }

            $label = $this->configurationProvider->getAttributeLabel($attr);

            if ($attributeType->isFilterable($attr) && $this->configurationProvider->isAttributeFilterable($attr)) {
                $this->addFilter($config, $attr, $attributeType, $label);
            }

            if ($attributeType->isSortable($attr) && $this->configurationProvider->isAttributeSortable($attr)) {
                $this->addSorter($config, $attr, $attributeType, $label);
            }
        }
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
     * @param DatagridConfiguration $config
     * @param FieldConfigModel $attribute
     * @param SearchableAttributeTypeInterface $attributeType
     * @param string $label
     */
    protected function addFilter(
        DatagridConfiguration $config,
        FieldConfigModel $attribute,
        SearchableAttributeTypeInterface $attributeType,
        $label
    ) {
        $name = $attributeType->getFilterableFieldName($attribute);
        $alias = $this->clearName($name);

        $params = [
            'type' => $attributeType->getFilterType(),
            'data_name' => sprintf('%s.%s', $attributeType->getFilterStorageFieldType(), $name),
            'label' => $label
        ];

        if ($attributeType->getFilterStorageFieldType() &&
            $this->configurationProvider->isAttributeFilterByExactValue($attribute)
        ) {
            $params['force_like'] = true;
        }

        $config->addFilter($alias, $this->applyAdditionalParams($attribute, $attributeType, $params));
    }

    /**
     * @param FieldConfigModel $attribute
     * @param SearchableAttributeTypeInterface $attributeType
     * @param array $params
     * @return array
     */
    protected function applyAdditionalParams(
        FieldConfigModel $attribute,
        SearchableAttributeTypeInterface $attributeType,
        array $params
    ) {
        $fieldType = $attributeType->getFilterStorageFieldType();
        $entityFilterTypes = [
            SearchableAttributeTypeInterface::FILTER_TYPE_ENUM,
            SearchableAttributeTypeInterface::FILTER_TYPE_MULTI_ENUM,
            SearchableAttributeTypeInterface::FILTER_TYPE_ENTITY,
        ];

        if (in_array($attributeType->getFilterType(), $entityFilterTypes, true)) {
            $params['class'] = $this->getEntityClass($attribute);
        } elseif ($fieldType === Query::TYPE_TEXT) {
            $params['max_length'] = 255;
        } elseif ($fieldType === Query::TYPE_DECIMAL) {
            $params['options']['data_type'] = NumberFilterTypeInterface::DATA_DECIMAL;
        }

        return $params;
    }

    /**
     * @param DatagridConfiguration $config
     * @param FieldConfigModel $attribute
     * @param SearchableAttributeTypeInterface $attributeType
     * @param string $label
     */
    protected function addSorter(
        DatagridConfiguration $config,
        FieldConfigModel $attribute,
        SearchableAttributeTypeInterface $attributeType,
        $label
    ) {
        $name = $attributeType->getSortableFieldName($attribute);
        $alias = $this->clearName($name);

        $config->addColumn($alias, ['label' => $label]);
        $config->addSorter(
            $alias,
            [
                'data_name' => sprintf('%s.%s', $attributeType->getSorterStorageFieldType(), $name),
            ]
        );
    }

    /**
     * @param string $name
     * @return string
     */
    private function clearName($name)
    {
        $placeholders = ['_'.LocalizationIdPlaceholder::NAME => '', '_'.EnumIdPlaceholder::NAME => ''];

        return strtr($name, $placeholders);
    }

    /**
     * @param FieldConfigModel|null $attribute
     *
     * @return string|null
     */
    private function getEntityClass(FieldConfigModel $attribute)
    {
        $config = $attribute->toArray('extend');
        if (isset($config['target_entity'])) {
            return $config['target_entity'];
        }

        $fieldName = $attribute->getFieldName();
        $metadata = $this->doctrineHelper->getEntityMetadata($attribute->getEntity()->getClassName(), false);
        if (!$metadata || !$metadata->hasAssociation($fieldName)) {
            return null;
        }

        $mapping = $metadata->getAssociationMapping($fieldName);

        return $mapping['targetEntity'] ?? null;
    }
}
