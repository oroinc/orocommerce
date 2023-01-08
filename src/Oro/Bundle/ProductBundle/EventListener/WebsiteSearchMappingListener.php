<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Attribute\AttributeConfigurationProviderInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\AttributeTypeRegistry;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Search\ProductIndexAttributeProviderInterface;
use Oro\Bundle\SearchBundle\Event\SearchMappingCollectEvent;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Attribute\SearchableInformationProvider;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\FulltextAwareTypeInterface;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\SearchAttributeTypeInterface;
use Oro\Bundle\WebsiteSearchBundle\Configuration\MappingConfiguration;
use Symfony\Component\Config\Definition\Processor;

/**
 * Adds dynamical information about product attributes to website search index mapping
 */
class WebsiteSearchMappingListener
{
    /** @var AttributeManager */
    protected $attributeManager;

    /** @var AttributeTypeRegistry */
    protected $attributeTypeRegistry;

    /** @var AttributeConfigurationProviderInterface */
    protected $configurationProvider;

    /** @var ProductIndexAttributeProviderInterface */
    protected $fieldsProvider;

    /** @var SearchableInformationProvider */
    private $searchableProvider;

    public function __construct(
        AttributeManager $attributeManager,
        AttributeTypeRegistry $attributeTypeRegistry,
        AttributeConfigurationProviderInterface $configurationProvider,
        ProductIndexAttributeProviderInterface $fieldsProvider,
        SearchableInformationProvider $searchableProvider
    ) {
        $this->attributeManager = $attributeManager;
        $this->attributeTypeRegistry = $attributeTypeRegistry;
        $this->configurationProvider = $configurationProvider;
        $this->fieldsProvider = $fieldsProvider;
        $this->searchableProvider = $searchableProvider;
    }

    public function onWebsiteSearchMapping(SearchMappingCollectEvent $event)
    {
        $attributes = $this->attributeManager->getAttributesByClass(Product::class);
        $fields = [];

        foreach ($attributes as $attribute) {
            $organizationId = $attribute->toArray('attribute')['organization_id'] ?? null;

            $attributeType = $this->getAttributeType($attribute);
            if (!$attributeType) {
                continue;
            }

            $isForceIndexed = $this->fieldsProvider->isForceIndexed($attribute->getFieldName());

            if ($this->isFilterable($attribute, $attributeType, $isForceIndexed)) {
                $fields = $this->getFilterableFields($attributeType, $attribute, $fields, $organizationId);
            }

            if ($this->isBoostable($attribute, $attributeType)) {
                $name = $attributeType->getSearchableFieldName($attribute);

                if (array_key_exists($name, $fields)) {
                    continue;
                }

                $fields[$name] = [
                    'name' => $name,
                    'type' => Query::TYPE_TEXT,
                    'organization_id' => $organizationId,
                    'fulltext' => true
                ];
            }

            if ($this->isSortable($attribute, $attributeType, $isForceIndexed)) {
                $name = $attributeType->getSortableFieldName($attribute);

                if (array_key_exists($name, $fields)) {
                    continue;
                }

                $fields[$name] = [
                    'name' => $name,
                    'type' => $attributeType->getSorterStorageFieldType($attribute),
                    'organization_id' => $organizationId,
                    'fulltext' => false,
                ];
            }
        }

        if ($fields) {
            $this->setConfiguration($event, $fields);
        }
    }

    /**
     * @param FieldConfigModel $attribute
     *
     * @return null|SearchAttributeTypeInterface
     */
    protected function getAttributeType(FieldConfigModel $attribute)
    {
        $attributeType = $this->attributeTypeRegistry->getAttributeType($attribute);
        if (!$attributeType instanceof SearchAttributeTypeInterface) {
            return null;
        }

        if (!$this->configurationProvider->isAttributeActive($attribute)) {
            return null;
        }

        return $attributeType;
    }

    protected function getFilterableFields(
        SearchAttributeTypeInterface $attributeType,
        FieldConfigModel $attribute,
        array $fields,
        ?int $organizationId
    ): array {
        $names = $attributeType->getFilterableFieldNames($attribute);
        $types = $attributeType->getFilterStorageFieldTypes($attribute);

        foreach ($names as $key => $scalarName) {
            $field = [
                'name' => $scalarName,
                'type' => $types[$key],
                'organization_id' => $organizationId
            ];
            if ($attributeType instanceof FulltextAwareTypeInterface) {
                $field['fulltext'] = $attributeType->isFulltextSearchSupported();
            }
            $fields[$scalarName] = $field;
        }

        return $fields;
    }

    /**
     * Merge the config with the existing one.
     */
    private function setConfiguration(SearchMappingCollectEvent $event, array $fields)
    {
        $config[Product::class]['fields'] = $fields;
        $processor = new Processor();
        $event->setMappingConfig(
            $processor->processConfiguration(
                new MappingConfiguration(),
                [$event->getMappingConfig(), $config]
            )
        );
    }

    /**
     * @param FieldConfigModel $attribute
     * @param SearchAttributeTypeInterface $type
     * @param bool $force
     *
     * @return bool
     */
    private function isFilterable(FieldConfigModel $attribute, SearchAttributeTypeInterface $type, $force)
    {
        return $type->isFilterable($attribute) &&
            ($force || $this->configurationProvider->isAttributeFilterable($attribute));
    }

    /**
     * @param FieldConfigModel $attribute
     * @param SearchAttributeTypeInterface $type
     * @param bool $force
     *
     * @return bool
     */
    private function isSortable(FieldConfigModel $attribute, SearchAttributeTypeInterface $type, $force)
    {
        return $type->isSortable($attribute) &&
            ($force || $this->configurationProvider->isAttributeSortable($attribute));
    }

    /**
     * @param FieldConfigModel $attribute
     * @param SearchAttributeTypeInterface $type
     *
     * @return bool
     */
    private function isBoostable(FieldConfigModel $attribute, SearchAttributeTypeInterface $type)
    {
        return $type->isSearchable($attribute)
            && $this->configurationProvider->isAttributeSearchable($attribute)
            && $this->searchableProvider->getAttributeSearchBoost($attribute);
    }
}
