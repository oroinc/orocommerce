<?php

namespace Oro\Bundle\ProductBundle\DataGrid\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
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
    private AttributeManager $attributeManager;
    private AttributeTypeRegistry $attributeTypeRegistry;
    private AttributeConfigurationProviderInterface $configProvider;
    private DoctrineHelper $doctrineHelper;
    private ConfigManager $configManager;
    private DatagridParametersHelper $datagridParametersHelper;
    private FamilyAttributeCountsProvider $familyAttributeCountsProvider;
    private ?array $families = null;
    private bool $inProgress = false;

    public function __construct(
        AttributeManager $attributeManager,
        AttributeTypeRegistry $attributeTypeRegistry,
        AttributeConfigurationProviderInterface $configurationProvider,
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager,
        DatagridParametersHelper $datagridParametersHelper,
        FamilyAttributeCountsProvider $familyAttributeCountsProvider
    ) {
        $this->attributeManager = $attributeManager;
        $this->attributeTypeRegistry = $attributeTypeRegistry;
        $this->configProvider = $configurationProvider;
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
        $this->datagridParametersHelper = $datagridParametersHelper;
        $this->familyAttributeCountsProvider = $familyAttributeCountsProvider;
    }

    public function onPreBuild(PreBuild $event): void
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

            $label = $this->configProvider->getAttributeLabel($attribute);
            if ($type->isFilterable($attribute) && $this->configProvider->isAttributeFilterable($attribute)) {
                $this->addFilter($event->getConfig(), $attribute, $type, $label);
            }

            if ($type->isSortable($attribute) && $this->configProvider->isAttributeSortable($attribute)) {
                $this->addSorter($event->getConfig(), $attribute, $type, $label);
            }
        }

        $this->inProgress = false;
    }

    protected function addFilter(
        DatagridConfiguration $config,
        FieldConfigModel $attribute,
        SearchAttributeTypeInterface $attributeType,
        string $label
    ): string {
        $name = $attributeType->getFilterableFieldNames($attribute)[SearchAttributeTypeInterface::VALUE_MAIN] ?? '';
        $alias = $this->clearName($name);
        $type = $attributeType->getFilterStorageFieldTypes($attribute)[SearchAttributeTypeInterface::VALUE_MAIN] ?? '';

        $params = [
            'type' => $attributeType->getFilterType($attribute),
            'data_name' => sprintf('%s.%s', $type, $name),
            'label' => $label
        ];

        if ($type && $this->configProvider->isAttributeFilterByExactValue($attribute)) {
            $params['force_like'] = true;
        }

        $config->addFilter($alias, $this->applyAdditionalParams($attribute, $attributeType, $params));

        return $alias;
    }

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

        if (\in_array($attributeType->getFilterType($attribute), $entityFilterTypes, true)) {
            $params['class'] = $this->getEntityClass($attribute);
        } elseif ($fieldType === Query::TYPE_TEXT) {
            $params['max_length'] = 255;
        } elseif ($fieldType === Query::TYPE_DECIMAL) {
            $params['options']['data_type'] = NumberFilterTypeInterface::DATA_DECIMAL;
        }

        return $params;
    }

    protected function addSorter(
        DatagridConfiguration $config,
        FieldConfigModel $attribute,
        SearchAttributeTypeInterface $attributeType,
        string $label
    ): string {
        $name = $attributeType->getSortableFieldName($attribute);
        $alias = $this->clearName($name);

        $config->addColumn($alias, ['label' => $label]);
        $config->addSorter(
            $alias,
            ['data_name' => sprintf('%s.%s', $attributeType->getSorterStorageFieldType($attribute), $name)]
        );

        return $alias;
    }

    private function clearName(string $name): string
    {
        $placeholders = ['_' . LocalizationIdPlaceholder::NAME => '', '_enum.' . EnumIdPlaceholder::NAME => ''];

        return strtr($name, $placeholders);
    }

    private function getEntityClass(FieldConfigModel $attribute): ?string
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

    private function getAttributes(DatagridConfiguration $config, ParameterBag $parameters): array
    {
        $families = $this->getGridActiveAttributeFamilies($config, $parameters);

        return $this->attributeManager->getSortableOrFilterableAttributesByClass(Product::class, $families);
    }

    private function getAttributeType(FieldConfigModel $attribute): ?SearchAttributeTypeInterface
    {
        $attributeType = $this->attributeTypeRegistry->getAttributeType($attribute);

        return $attributeType instanceof SearchAttributeTypeInterface ? $attributeType : null;
    }

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
