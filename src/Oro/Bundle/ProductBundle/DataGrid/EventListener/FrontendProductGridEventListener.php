<?php

namespace Oro\Bundle\ProductBundle\DataGrid\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Provider\State\DatagridStateProviderInterface;
use Oro\Bundle\DataGridBundle\Tools\DatagridParametersHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\AttributeConfigurationProviderInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\AttributeTypeRegistry;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterTypeInterface;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\FamilyAttributeCountsProvider;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\SearchAttributeTypeInterface;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\EnumIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;

/**
 * Updates configuration of frontend products grid and add filter or sorter on it
 * depends on product attributes configuration and information about product families that are used in products
 */
class FrontendProductGridEventListener
{
    /** @var AttributeManager */
    private $attributeManager;

    /** @var AttributeTypeRegistry */
    private $attributeTypeRegistry;

    /** @var AttributeConfigurationProviderInterface */
    private $configurationProvider;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var DatagridStateProviderInterface */
    private $filtersStateProvider;

    /** @var DatagridStateProviderInterface */
    private $sortersStateProvider;

    /** @var ConfigManager */
    private $configManager;

    /** @var DatagridParametersHelper|null */
    private $datagridParametersHelper;

    /** @var FamilyAttributeCountsProvider */
    private $familyAttributeCountsProvider;

    /** @var null|array */
    private $families = null;

    /** @var bool */
    private $inProgress = false;

    public function __construct(
        AttributeManager $attributeManager,
        AttributeTypeRegistry $attributeTypeRegistry,
        AttributeConfigurationProviderInterface $configurationProvider,
        DoctrineHelper $doctrineHelper,
        DatagridStateProviderInterface $filtersStateProvider,
        DatagridStateProviderInterface $sortersStateProvider,
        ConfigManager $configManager,
        DatagridParametersHelper $datagridParametersHelper,
        FamilyAttributeCountsProvider $familyAttributeCountsProvider
    ) {
        $this->attributeManager = $attributeManager;
        $this->attributeTypeRegistry = $attributeTypeRegistry;
        $this->configurationProvider = $configurationProvider;
        $this->doctrineHelper = $doctrineHelper;
        $this->filtersStateProvider = $filtersStateProvider;
        $this->sortersStateProvider = $sortersStateProvider;
        $this->configManager = $configManager;
        $this->datagridParametersHelper = $datagridParametersHelper;
        $this->familyAttributeCountsProvider = $familyAttributeCountsProvider;
    }

    /**
     * @param PreBuild $event
     *
     * @return void
     */
    public function onPreBuild(PreBuild $event)
    {
        if ($this->inProgress) {
            return;
        }
        $this->inProgress = true;

        $attributes = $this->getAttributes($event->getConfig(), $event->getParameters());
        foreach ($attributes as $attribute) {
            $type = $this->getAttributeType($attribute);
            if (!$type) {
                continue;
            }

            $label = $this->configurationProvider->getAttributeLabel($attribute);
            if ($type->isFilterable($attribute) && $this->configurationProvider->isAttributeFilterable($attribute)) {
                $this->addFilter($event->getConfig(), $attribute, $type, $label);
            }

            if ($type->isSortable($attribute) && $this->configurationProvider->isAttributeSortable($attribute)) {
                $this->addSorter($event->getConfig(), $attribute, $type, $label);
            }
        }

        $this->inProgress = false;
    }

    /**
     * @param DatagridConfiguration $config
     * @param FieldConfigModel $attribute
     * @param SearchAttributeTypeInterface $attributeType
     * @param string $label
     *
     * @return string
     */
    protected function addFilter(
        DatagridConfiguration $config,
        FieldConfigModel $attribute,
        SearchAttributeTypeInterface $attributeType,
        $label
    ) {
        $name = $attributeType->getFilterableFieldNames($attribute)[SearchAttributeTypeInterface::VALUE_MAIN] ?? '';
        $alias = $this->clearName($name);
        $type = $attributeType->getFilterStorageFieldTypes()[SearchAttributeTypeInterface::VALUE_MAIN] ?? '';

        $params = [
            'type' => $attributeType->getFilterType(),
            'data_name' => sprintf('%s.%s', $type, $name),
            'label' => $label
        ];

        if ($type && $this->configurationProvider->isAttributeFilterByExactValue($attribute)) {
            $params['force_like'] = true;
        }

        $config->addFilter($alias, $this->applyAdditionalParams($attribute, $attributeType, $params));

        return $alias;
    }

    /**
     * @deprecated
     *
     * @param PreBuild $event
     * @param array $addedFilterAttrs
     * @param array $hideAttrs
     *
     * @return void
     */
    protected function checkFilters(PreBuild $event, array $addedFilterAttrs, array $hideAttrs): void
    {
        $config = $event->getConfig();

        $filtersState = $this->filtersStateProvider->getState($config, $event->getParameters());
        foreach ($addedFilterAttrs as $attrId => $filterAlias) {
            // check that filter must be hidden and not in use
            if (in_array($attrId, $hideAttrs, true) && !array_key_exists($filterAlias, $filtersState)) {
                $config->removeFilter($filterAlias);
            }
        }
    }

    /**
     * @param FieldConfigModel $attribute
     * @param SearchAttributeTypeInterface $attributeType
     * @param array $params
     *
     * @return array
     */
    protected function applyAdditionalParams(
        FieldConfigModel $attribute,
        SearchAttributeTypeInterface $attributeType,
        array $params
    ): array {
        $fieldTypes = $attributeType->getFilterStorageFieldTypes($attribute);
        $fieldType = $fieldTypes[SearchAttributeTypeInterface::VALUE_MAIN] ?? '';

        $entityFilterTypes = [
            SearchAttributeTypeInterface::FILTER_TYPE_ENUM,
            SearchAttributeTypeInterface::FILTER_TYPE_MULTI_ENUM,
            SearchAttributeTypeInterface::FILTER_TYPE_ENTITY,
        ];

        if (\in_array($attributeType->getFilterType(), $entityFilterTypes, true)) {
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
     * @param SearchAttributeTypeInterface $attributeType
     * @param string $label
     *
     * @return string
     */
    protected function addSorter(
        DatagridConfiguration $config,
        FieldConfigModel $attribute,
        SearchAttributeTypeInterface $attributeType,
        $label
    ) {
        $name = $attributeType->getSortableFieldName($attribute);
        $alias = $this->clearName($name);

        $config->addColumn($alias, ['label' => $label]);
        $config->addSorter(
            $alias,
            ['data_name' => sprintf('%s.%s', $attributeType->getSorterStorageFieldType(), $name)]
        );

        return $alias;
    }

    /**
     * @deprecated
     *
     * @param PreBuild $event
     * @param array $addedSorterAttrs
     * @param array $hideAttrs
     *
     * @return void
     */
    protected function checkSorters(PreBuild $event, array $addedSorterAttrs, array $hideAttrs): void
    {
        $config = $event->getConfig();

        $sortersState = $this->sortersStateProvider->getState($config, $event->getParameters());
        foreach ($addedSorterAttrs as $attrId => $sorterAlias) {
            // check that sorter must be hidden and not in use
            if (in_array($attrId, $hideAttrs, true) && !array_key_exists($sorterAlias, $sortersState)) {
                $config->removeSorter($sorterAlias);
            }
        }
    }

    /**
     * @param string $name
     * @return string
     */
    private function clearName($name)
    {
        $placeholders = ['_' . LocalizationIdPlaceholder::NAME => '', '_' . EnumIdPlaceholder::NAME => ''];

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

    /**
     * @param DatagridConfiguration $config
     * @param ParameterBag $parameters
     *
     * @return array
     */
    private function getAttributes(DatagridConfiguration $config, ParameterBag $parameters): array
    {
        $families = $this->getGridActiveAttributeFamilies($config, $parameters);

        return $this->attributeManager->getSortableOrFilterableAttributesByClass(Product::class, $families);
    }

    /**
     * @param FieldConfigModel $attribute
     *
     * @return SearchAttributeTypeInterface|null
     */
    private function getAttributeType(FieldConfigModel $attribute): ?SearchAttributeTypeInterface
    {
        $attributeType = $this->attributeTypeRegistry->getAttributeType($attribute);

        return $attributeType instanceof SearchAttributeTypeInterface ? $attributeType : null;
    }

    /**
     * @param DatagridConfiguration $config
     * @param ParameterBag $parameterBag
     *
     * @return array
     */
    private function getGridActiveAttributeFamilies(DatagridConfiguration $config, ParameterBag $parameterBag): array
    {
        $gridName = $config->getName();

        if (null !== $this->families) {
            return $this->families;
        }

        $this->families = [];
        $configKey = Configuration::getConfigKeyByName(Configuration::LIMIT_FILTERS_SORTERS_ON_PRODUCT_LISTING);
        if (!$this->datagridParametersHelper->isDatagridExtensionSkipped($parameterBag)
            && $this->configManager->get($configKey)) {
            $familyAttributes = $this->familyAttributeCountsProvider->getFamilyAttributeCounts($gridName);
            if (!empty($familyAttributes['familyAttributesCount'])) {
                $this->families = array_keys($familyAttributes['familyAttributesCount']);
            }
        }

        return $this->families;
    }
}
