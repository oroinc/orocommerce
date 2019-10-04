<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Attribute\AttributeConfigurationProviderInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\AttributeTypeRegistry;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Search\ProductIndexFieldsProvider;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\SearchAttributeTypeInterface;
use Oro\Bundle\WebsiteSearchBundle\Event\WebsiteSearchMappingEvent;

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

    /** @var ProductIndexFieldsProvider */
    protected $fieldsProvider;

    /**
     * @param AttributeManager $attributeManager
     * @param AttributeTypeRegistry $attributeTypeRegistry
     * @param AttributeConfigurationProviderInterface $configurationProvider
     * @param ProductIndexFieldsProvider $fieldsProvider
     */
    public function __construct(
        AttributeManager $attributeManager,
        AttributeTypeRegistry $attributeTypeRegistry,
        AttributeConfigurationProviderInterface $configurationProvider,
        ProductIndexFieldsProvider $fieldsProvider
    ) {
        $this->attributeManager = $attributeManager;
        $this->attributeTypeRegistry = $attributeTypeRegistry;
        $this->configurationProvider = $configurationProvider;
        $this->fieldsProvider = $fieldsProvider;
    }

    /**
     * @param WebsiteSearchMappingEvent $event
     */
    public function onWebsiteSearchMapping(WebsiteSearchMappingEvent $event)
    {
        $attributes = $this->attributeManager->getAttributesByClass(Product::class);
        $fields = [];

        foreach ($attributes as $attribute) {
            $attributeType = $this->getAttributeType($attribute);
            if (!$attributeType) {
                continue;
            }

            $isForceIndexed = $this->fieldsProvider->isForceIndexed($attribute->getFieldName());

            if ($this->isFilterable($attribute, $attributeType, $isForceIndexed)) {
                $names = $attributeType->getFilterableFieldNames($attribute);
                $types = $attributeType->getFilterStorageFieldTypes();

                foreach ($names as $key => $scalarName) {
                    $fields[$scalarName] = ['name' => $scalarName, 'type' => $types[$key]];
                }
            }

            if ($this->isSortable($attribute, $attributeType, $isForceIndexed)) {
                $name = $attributeType->getSortableFieldName($attribute);

                if (array_key_exists($name, $fields)) {
                    continue;
                }

                $fields[$name] = ['name' => $name, 'type' => $attributeType->getSorterStorageFieldType()];
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

    /**
     * @param WebsiteSearchMappingEvent $event
     * @param array $fields
     */
    private function setConfiguration(WebsiteSearchMappingEvent $event, array $fields)
    {
        $config = $event->getConfiguration();

        if (isset($config[Product::class]['fields'])) {
            $config[Product::class]['fields'] = array_merge($config[Product::class]['fields'], $fields);
        } else {
            $config[Product::class]['fields'] = $fields;
        }

        $event->setConfiguration($config);
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
}
