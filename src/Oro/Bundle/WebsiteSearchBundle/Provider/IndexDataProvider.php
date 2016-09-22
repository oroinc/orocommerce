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
use Oro\Bundle\WebsiteSearchBundle\Placeholder\ChainReplacePlaceholder;
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

    /** @var ChainReplacePlaceholder */
    private $chainReplacePlaceholder;

    /** @var FieldHelper */
    private $fieldHelper;

    /** @var WebsiteSearchMappingProvider */
    private $mappingProvider;

    /** @var array */
    private $preparedIndexData = [];

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param EntityAliasResolver $entityAliasResolver
     * @param ChainReplacePlaceholder $chainReplacePlaceholder
     * @param FieldHelper $fieldHelper
     * @param WebsiteSearchMappingProvider $mappingProvider
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        EntityAliasResolver $entityAliasResolver,
        ChainReplacePlaceholder $chainReplacePlaceholder,
        FieldHelper $fieldHelper,
        WebsiteSearchMappingProvider $mappingProvider
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->entityAliasResolver = $entityAliasResolver;
        $this->chainReplacePlaceholder = $chainReplacePlaceholder;
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
     * @return array
     */
    public function getEntitiesData($entityClass, array $restrictedEntities, array $context)
    {
        $indexEntityEvent = new IndexEntityEvent($entityClass, $restrictedEntities, $context);
        $this->eventDispatcher->dispatch(IndexEntityEvent::NAME, $indexEntityEvent);

        return $this->prepareIndexData($indexEntityEvent->getEntitiesData(), $entityClass);
    }

    /**
     * Adds field types according to entity config, applies placeholders
     *
     * @param array $indexData
     * @param string $entityClass
     * @return array Structured and cleared data ready to be saved
     */
    private function prepareIndexData(array $indexData, $entityClass)
    {
        $mappingConfig = $this->mappingProvider->getEntityConfig($entityClass);
        $fieldsConfig = $mappingConfig['fields'];

        $this->preparedIndexData = [];

        foreach ($indexData as $entityId => $fieldsCategories) {
            $allTextPlaceholder = '';
            if (isset($fieldsCategories[self::ALL_TEXT_FIELD])) {
                $allTextPlaceholder = $fieldsCategories[self::ALL_TEXT_FIELD];
            }
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
                    $fieldsConfig,
                    $allTextPlaceholder
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
     * @param string $allTextPlaceholder
     */
    private function processPlaceholderValues($entityId, array $fieldsData, array $fieldsConfig, $allTextPlaceholder)
    {
        $allStandardTexts = [];
        $allTextsWithPlaceholders = [];
        if (isset($this->preparedIndexData[$entityId][Query::TYPE_TEXT]) && $allTextPlaceholder) {
            $allStandardTexts = $this->preparedIndexData[$entityId][Query::TYPE_TEXT];
        }

        foreach ($fieldsData as $fieldName => $localizedValues) {
            foreach ($localizedValues as $localeId => $value) {
                /** @var ValueWithPlaceholders $valueWithPlaceholders */
                $valueWithPlaceholders = $value;
                $fieldConfig = $this->getFieldConfig($fieldsConfig, $fieldName);
                $placeholders = $valueWithPlaceholders->getPlaceholders();
                $type = $fieldConfig['type'];
                $replacedFieldName = $this->chainReplacePlaceholder->replace($fieldConfig['name'], $placeholders);
                $clearedValue = $this->clearTextValue($type, $valueWithPlaceholders->getValue());
                $this->preparedIndexData[$entityId][$type][$replacedFieldName] = $clearedValue;

                /** Todo:need to discuss could we have all texts combined for "all_text" field (without localization)*/
                if ($allTextPlaceholder && $type === Query::TYPE_TEXT && isset($placeholders[$allTextPlaceholder])) {
                    $allTextField = $this->chainReplacePlaceholder->replace(
                        self::ALL_TEXT_FIELD . '_' . $allTextPlaceholder,
                        $placeholders
                    );
                    $allTextsWithPlaceholders[$allTextField][] = $clearedValue;
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
