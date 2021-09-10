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
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;
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

    /** @var bool */
    private $inProgress = false;

    /** @var array */
    private $attributesToHide = [];

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

    public function onPreBuild(PreBuild $event)
    {
        if ($this->inProgress) {
            return;
        }
        $this->inProgress = true;

        $config = $event->getConfig();
        $attrs = $this->attributeManager->getAttributesByClass(Product::class);

        $addedFilterAttrs = [];
        $addedSorterAttrs = [];

        foreach ($attrs as $attr) {
            $attributeType = $this->getAttributeType($attr);
            if (!$attributeType) {
                continue;
            }

            $label = $this->configurationProvider->getAttributeLabel($attr);

            if ($attributeType->isFilterable($attr) && $this->configurationProvider->isAttributeFilterable($attr)) {
                $addedFilterAttrs[$attr->getId()] = $this->addFilter($config, $attr, $attributeType, $label);
            }

            if ($attributeType->isSortable($attr) && $this->configurationProvider->isAttributeSortable($attr)) {
                $addedSorterAttrs[$attr->getId()] = $this->addSorter($config, $attr, $attributeType, $label);
            }
        }

        $hideAttrs = $this->getAttributesToHide(
            $config,
            $event->getParameters(),
            array_unique(array_merge(array_keys($addedFilterAttrs), array_keys($addedSorterAttrs)))
        );

        if ($hideAttrs) {
            $this->checkFilters($event, $addedFilterAttrs, $hideAttrs);
            $this->checkSorters($event, $addedSorterAttrs, $hideAttrs);
        }

        $this->inProgress = false;
    }

    /**
     * @param FieldConfigModel $attribute
     *
     * @return null|SearchAttributeTypeInterface
     */
    protected function getAttributeType(FieldConfigModel $attribute)
    {
        if (!$this->configurationProvider->isAttributeActive($attribute)) {
            return null;
        }

        $attributeType = $this->attributeTypeRegistry->getAttributeType($attribute);
        if (!$attributeType instanceof SearchAttributeTypeInterface) {
            return null;
        }

        return $attributeType;
    }

    /**
     * @param DatagridConfiguration $config
     * @param FieldConfigModel $attribute
     * @param SearchAttributeTypeInterface $attributeType
     * @param string $label
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
     * @return array
     */
    protected function applyAdditionalParams(
        FieldConfigModel $attribute,
        SearchAttributeTypeInterface $attributeType,
        array $params
    ) {
        $fieldType = $attributeType->getFilterStorageFieldTypes()[SearchAttributeTypeInterface::VALUE_MAIN] ?? '';

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
            [
                'data_name' => sprintf('%s.%s', $attributeType->getSorterStorageFieldType(), $name),
            ]
        );

        return $alias;
    }

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

    private function getAttributesToHide(
        DatagridConfiguration $config,
        ParameterBag $parameterBag,
        array $attributes
    ): array {
        $gridName = $config->getName();
        if (!array_key_exists($gridName, $this->attributesToHide) &&
            !$this->datagridParametersHelper->isDatagridExtensionSkipped($parameterBag)
        ) {
            $this->attributesToHide[$gridName] = [];

            $configKey = Configuration::getConfigKeyByName(Configuration::LIMIT_FILTERS_SORTERS_ON_PRODUCT_LISTING);
            if ($this->configManager->get($configKey)) {
                $familyAttributeCounts = $this->familyAttributeCountsProvider->getFamilyAttributeCounts($gridName);

                $activeAttributeFamilyIds = [];
                if (!empty($familyAttributeCounts['familyAttributesCount'])) {
                    $activeAttributeFamilyIds = array_keys($familyAttributeCounts['familyAttributesCount']);
                }

                $this->attributesToHide[$gridName] = $this->getDisabledSortAndFilterAttributes(
                    $attributes,
                    $activeAttributeFamilyIds
                );
            }
        }

        return $this->attributesToHide[$gridName] ?? [];
    }

    private function getDisabledSortAndFilterAttributes(array $attributes, array $activeAttributeFamilyIds): array
    {
        /** @var AttributeFamilyRepository $attributeFamilyRepository */
        $attributeFamilyRepository = $this->doctrineHelper->getEntityRepository(AttributeFamily::class);
        $familyIdsForAttributes = $attributeFamilyRepository->getFamilyIdsForAttributes($attributes);

        return array_filter(
            $attributes,
            function ($attrId) use ($familyIdsForAttributes, $activeAttributeFamilyIds) {
                // skip attributes without product families or
                return empty($familyIdsForAttributes[$attrId]) ||
                    // skip attributes that are not included to active attribute families
                    empty(array_intersect($activeAttributeFamilyIds, $familyIdsForAttributes[$attrId]));
            }
        );
    }
}
