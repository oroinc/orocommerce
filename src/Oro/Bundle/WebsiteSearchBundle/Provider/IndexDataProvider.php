<?php

namespace Oro\Bundle\WebsiteSearchBundle\Provider;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\CollectContextEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntityEvent;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Helper\FieldHelper;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\VisitorReplacePlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\ValueWithPlaceholders;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * Class corresponds for triggering all events during indexation
 * and returning all collected and prepared for saving event data
 */
class IndexDataProvider
{
    const ALL_TEXT_FIELD = 'all_text';
    const PLACEHOLDER_VALUES_KEY = 'placeholder_values_key';
    const STANDARD_VALUES_KEY = 'standard_values_key';

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var EntityAliasResolver */
    private $entityAliasResolver;

    /** @var VisitorReplacePlaceholder */
    private $visitorReplacePlaceholder;

    /** @var FieldHelper */
    private $fieldHelper;

    /** @var WebsiteSearchMappingProvider */
    private $mappingProvider;

    /** @var array */
    private $preparedIndexData = [];

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param EntityAliasResolver $entityAliasResolver
     * @param VisitorReplacePlaceholder $visitorReplacePlaceholder
     * @param FieldHelper $fieldHelper
     * @param WebsiteSearchMappingProvider $mappingProvider
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        EntityAliasResolver $entityAliasResolver,
        VisitorReplacePlaceholder $visitorReplacePlaceholder,
        FieldHelper $fieldHelper,
        WebsiteSearchMappingProvider $mappingProvider
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->entityAliasResolver = $entityAliasResolver;
        $this->visitorReplacePlaceholder = $visitorReplacePlaceholder;
        $this->fieldHelper = $fieldHelper;
        $this->mappingProvider = $mappingProvider;
    }

    /**
     * @param int $websiteId
     * @param array $context
     * @return array
     */
    public function collectContextForWebsite($websiteId, array $context)
    {
        $context[AbstractIndexer::CONTEXT_WEBSITE_ID_KEY] = $websiteId;
        $collectContextEvent = new CollectContextEvent($context);
        $this->eventDispatcher->dispatch(CollectContextEvent::NAME, $collectContextEvent);

        return $collectContextEvent->getContext();
    }

    /**
     * @param string $entityClass
     * @param object[] $restrictedEntities
     * @param array $context
     * @param array $entityConfig
     * @return array
     */
    public function getEntitiesData($entityClass, array $restrictedEntities, array $context, array $entityConfig)
    {
        $indexEntityEvent = new IndexEntityEvent($entityClass, $restrictedEntities, $context);
        $this->eventDispatcher->dispatch(IndexEntityEvent::NAME, $indexEntityEvent);

        return $this->prepareIndexData($indexEntityEvent->getEntitiesData(), $entityConfig);
    }

    /**
     * Adds field types according to entity config, applies placeholders
     * @param array $indexData
     * @param array $entityConfig
     * @return array Structured and cleared data ready to be saved
     */
    private function prepareIndexData(array $indexData, $entityConfig)
    {
        $fieldsConfig = $entityConfig['fields'];

        $this->preparedIndexData = [];

        foreach ($indexData as $entityId => $fieldsCategories) {
            if (isset($fieldsCategories[self::STANDARD_VALUES_KEY])) {
                $this->processStandardValues(
                    $entityId,
                    $fieldsCategories[self::STANDARD_VALUES_KEY],
                    $fieldsConfig
                );
            }
            if (isset($fieldsCategories[self::PLACEHOLDER_VALUES_KEY])) {
                $this->processPlaceholderValues(
                    $entityId,
                    $fieldsCategories[self::PLACEHOLDER_VALUES_KEY],
                    $fieldsConfig
                );
            }
        }

        return $this->preparedIndexData;
    }

    /**
     * @param int $entityId
     * @param array $fieldsData
     * @param array $fieldsConfig
     */
    private function processStandardValues($entityId, array $fieldsData, array $fieldsConfig)
    {
        foreach ($fieldsData as $field => $value) {
            $fieldConfig = $this->getFieldConfig($fieldsConfig, $field);
            $type = $fieldConfig['type'];
            $this->preparedIndexData[$entityId][$type][$field] = $this->clearTextValue($type, $value);
        }
    }

    /**
     * @param int $entityId
     * @param array $fieldsData
     * @param array $fieldsConfig
     */
    private function processPlaceholderValues($entityId, array $fieldsData, array $fieldsConfig)
    {
        $allTextsWithPlaceholders = [];
        $allStandardTexts = $this->preparedIndexData[$entityId][Query::TYPE_TEXT];
        $allTextFieldConfigName = $this->getFieldConfig($fieldsConfig, self::ALL_TEXT_FIELD)['name']; //all_text_LOCALIZATION_ID

        foreach ($fieldsData as $fieldName => $placeholderValues) {
            foreach ($placeholderValues as $value) {
                /** @var ValueWithPlaceholders $valueWithPlaceholders */
                $valueWithPlaceholders = $value;
                $fieldConfig = $this->getFieldConfig($fieldsConfig, $fieldName);
                $placeholders = $valueWithPlaceholders->getPlaceholders();
                $type = $fieldConfig['type'];
                $replacedFieldName = $this->visitorReplacePlaceholder->replace($fieldConfig['name'], $placeholders);
                $clearedValue = $this->clearTextValue($type, $valueWithPlaceholders->getValue());
                $this->preparedIndexData[$entityId][$type][$replacedFieldName] = $clearedValue;

                if ($type === Query::TYPE_TEXT) {
                    $replacedTextField = $this->visitorReplacePlaceholder->replace(
                        $allTextFieldConfigName,
                        $placeholders
                    );
                    $allTextsWithPlaceholders[$replacedTextField][] = $clearedValue;
                }
            }
        }
        $this->combineAllTexts($entityId, $allTextsWithPlaceholders, $allStandardTexts);
    }

    /**
     * @param int $entityId
     * @param array $allTextsWithPlaceholders
     * @param array $allStandardTexts
     */
    private function combineAllTexts($entityId, array $allTextsWithPlaceholders, array $allStandardTexts = [])
    {
        if ($allTextsWithPlaceholders) {
            foreach ($allTextsWithPlaceholders as $field => $values) {
                $allTextsString = implode(' ', array_merge($allStandardTexts, $values));
                $this->preparedIndexData[$entityId][Query::TYPE_TEXT][$field] = $allTextsString;
            }
        }
    }

    /**
     * @param array $fieldsConfig
     * @param string $fieldName
     * @return array
     */
    private function getFieldConfig(array $fieldsConfig, $fieldName)
    {
        if (!isset($fieldsConfig[$fieldName])) {
            throw new InvalidConfigurationException(
                'You try to index field in listener which is not added to mapping - ' . $fieldName
            );
        }

        return $fieldsConfig[$fieldName];
    }

    /**
     * Checks if value is text type and applies stripping tags
     * @param string $type
     * @param string $value
     * @return string
     */
    private function clearTextValue($type, $value)
    {
        if ($type === Query::TYPE_TEXT) {
            $value = $this->fieldHelper->stripTagsAndSpaces($value);
        }

        return $value;
    }

    /**
     * @param $entityClass
     * @param $queryBuilder
     * @param $context
     * @return QueryBuilder
     */
    public function getRestrictedEntitiesQueryBuilder($entityClass, $queryBuilder, $context)
    {
        $entityAlias = $this->entityAliasResolver->getAlias($entityClass);

        $restrictEntitiesEvent = new RestrictIndexEntityEvent($queryBuilder, $context);
        $this->eventDispatcher->dispatch(RestrictIndexEntityEvent::NAME, $restrictEntitiesEvent);
        $this->eventDispatcher->dispatch(
            sprintf('%s.%s', RestrictIndexEntityEvent::NAME, $entityAlias),
            $restrictEntitiesEvent
        );

        return $restrictEntitiesEvent->getQueryBuilder();
    }
}
