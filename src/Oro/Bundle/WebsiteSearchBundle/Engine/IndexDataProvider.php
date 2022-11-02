<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Event;
use Oro\Bundle\WebsiteSearchBundle\Helper\PlaceholderHelper;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderInterface;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderValue;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class is responsible for triggering all events during indexation
 * and returning all collected and prepared for saving event data
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class IndexDataProvider
{
    use ContextTrait;

    const ALL_TEXT_PREFIX = 'all_text';
    const ALL_TEXT_L10N_FIELD = 'all_text_LOCALIZATION_ID';

    /** @var array */
    private $cache;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var EntityAliasResolver */
    private $entityAliasResolver;

    /** @var PlaceholderInterface */
    private $placeholder;

    /** @var HtmlTagHelper */
    protected $htmlTagHelper;

    /** @var PlaceholderHelper */
    private $placeholderHelper;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param EntityAliasResolver $entityAliasResolver
     * @param PlaceholderInterface $placeholder
     * @param HtmlTagHelper $htmlTagHelper
     * @param PlaceholderHelper $placeholderHelper
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        EntityAliasResolver $entityAliasResolver,
        PlaceholderInterface $placeholder,
        HtmlTagHelper $htmlTagHelper,
        PlaceholderHelper $placeholderHelper
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->entityAliasResolver = $entityAliasResolver;
        $this->placeholder = $placeholder;
        $this->htmlTagHelper = $htmlTagHelper;
        $this->placeholderHelper = $placeholderHelper;
    }

    /**
     * @param int $websiteId
     * @param array $context
     * @return array
     */
    public function collectContextForWebsite($websiteId, array $context)
    {
        $context = $this->setContextCurrentWebsite($context, $websiteId);
        $collectContextEvent = new Event\CollectContextEvent($context);
        $this->eventDispatcher->dispatch($collectContextEvent, Event\CollectContextEvent::NAME);

        return $collectContextEvent->getContext();
    }

    /**
     * @param string $entityClass
     * @param object[] $restrictedEntities
     * @param array $context
     * $context = [
     *     'currentWebsiteId' int Current website id. Should not be passed manually. It is computed from 'websiteIds'
     * ]
     *
     * @param array $entityConfig
     * @return array
     */
    public function getEntitiesData($entityClass, array $restrictedEntities, array $context, array $entityConfig)
    {
        $entityAlias = $this->entityAliasResolver->getAlias($entityClass);

        $indexEntityEvent = new Event\IndexEntityEvent($entityClass, $restrictedEntities, $context);
        $this->eventDispatcher->dispatch($indexEntityEvent, Event\IndexEntityEvent::NAME);
        $this->eventDispatcher->dispatch(
            $indexEntityEvent,
            sprintf('%s.%s', Event\IndexEntityEvent::NAME, $entityAlias)
        );

        return $this->prepareIndexData($indexEntityEvent->getEntitiesData(), $entityConfig, $context);
    }

    /**
     * Adds field types according to entity config, applies placeholders
     * @param array $indexData
     * @param array $entityConfig
     * @param array $context
     * @return array Structured and cleared data ready to be saved
     */
    private function prepareIndexData(array $indexData, array $entityConfig, array $context): array
    {
        $preparedIndexData = [];

        $allTextL10N = $this->getFieldConfig(
            $entityConfig,
            self::ALL_TEXT_L10N_FIELD,
            'name',
            self::ALL_TEXT_L10N_FIELD
        );

        foreach ($indexData as $entityId => $fieldsValues) {
            $allTextFieldNames = [];

            foreach ($this->toArray($fieldsValues) as $fieldName => $values) {
                $type = $this->getFieldConfig($entityConfig, $fieldName, 'type');

                foreach ($this->toArray($values) as $value) {
                    $allTextFieldName = $allTextL10N;
                    $singleValueFieldName = $fieldName;
                    $addToAllText = $value['all_text'];
                    $value = $value['value'];
                    $placeholders = [];

                    if ($value instanceof PlaceholderValue) {
                        $placeholders = $value->getPlaceholders();
                        $value = $value->getValue();
                    }

                    if ($this->isAllTextCollected($type, $addToAllText)) {
                        $allTextFieldName = $this->placeholder->replace($allTextFieldName, $placeholders);
                        $allTextFieldNames[$allTextFieldName] = $allTextFieldName;
                        $this->setIndexValue($preparedIndexData, $entityId, $allTextFieldName, $value, $type);
                    }

                    if (!str_starts_with($fieldName, self::ALL_TEXT_PREFIX)) {
                        $singleValueFieldName = $this->placeholder->replace($singleValueFieldName, $placeholders);
                        $this->setIndexValue($preparedIndexData, $entityId, $singleValueFieldName, $value, $type);
                    }
                }
            }

            unset($allTextFieldNames[$allTextL10N]);

            $allTextValue = $this->getIndexValue($preparedIndexData, $entityId, $allTextL10N);
            foreach ($allTextFieldNames as $allTextFieldName) {
                $this->setIndexValue($preparedIndexData, $entityId, $allTextFieldName, $allTextValue);
            }

            unset($preparedIndexData[$entityId][Query::TYPE_TEXT][$allTextL10N]);
            $preparedIndexData[$entityId] = $this->squashAllTextFields($preparedIndexData[$entityId] ?? []);

            // add ID field for full indexation
            if (empty($context[AbstractIndexer::CONTEXT_FIELD_GROUPS])) {
                $preparedIndexData[$entityId][Query::TYPE_INTEGER][Indexer::ID_FIELD] = $entityId;
            }
        }

        return $preparedIndexData;
    }

    /**
     * @param string $fieldType
     * @param bool $addToAllText
     * @return bool
     */
    private function isAllTextCollected($fieldType, $addToAllText)
    {
        return $fieldType === Query::TYPE_TEXT && $addToAllText;
    }

    /**
     * @param mixed $value
     * @return array
     */
    private function toArray($value)
    {
        if (is_array($value) && !array_key_exists('value', $value)) {
            return $value;
        }

        return [$value];
    }

    /**
     * @param array $fieldsValues
     * @return array
     */
    private function squashAllTextFields(array $fieldsValues)
    {
        if (!empty($fieldsValues[Query::TYPE_TEXT])) {
            foreach ($fieldsValues[Query::TYPE_TEXT] as $fieldName => $fieldValue) {
                if (str_starts_with($fieldName, self::ALL_TEXT_PREFIX)) {
                    $fieldsValues[Query::TYPE_TEXT][$fieldName] = $this->updateAllTextFieldValue($fieldValue);
                }
            }
        }

        return $fieldsValues;
    }

    /**
     * @param array $preparedIndexData
     * @param int $entityId
     * @param string $fieldName
     * @param array|string $value
     * @param string $type
     */
    private function setIndexValue(array &$preparedIndexData, $entityId, $fieldName, $value, $type = Query::TYPE_TEXT)
    {
        $value = $this->clearValue($type, $fieldName, $value);

        if ($value === null || $value === '' || $value === []) {
            return;
        }

        $existingValue = $this->getIndexValue($preparedIndexData, $entityId, $fieldName, $type);
        if ($existingValue) {
            $value = $this->updateFieldValue($existingValue, $value, $type);
        }

        $preparedIndexData[$entityId][$type][$fieldName] = $value;
    }

    /**
     * @param string|array $value
     *
     * @return string
     */
    private function updateAllTextFieldValue($value)
    {
        if (is_array($value)) {
            $value = implode(' ', $value);
        }

        return implode(' ', array_unique(explode(' ', $value)));
    }

    /**
     * @param string|array  $existingValue
     * @param string        $value
     *
     * @return string|array
     */
    private function updateFieldValue($existingValue, $value, $type)
    {
        if ($type === Query::TYPE_TEXT && is_string($existingValue) && is_string($value)) {
            return $existingValue . ' ' . $value;
        }

        // array_values is required here to make sure that array can be properly converted to json
        return array_values(array_unique(array_merge((array)$existingValue, (array)$value)));
    }

    /**
     * @param array $preparedIndexData
     * @param int $entityId
     * @param string $fieldName
     * @param string $type
     * @return string|array
     */
    private function getIndexValue(array &$preparedIndexData, $entityId, $fieldName, $type = Query::TYPE_TEXT)
    {
        return $preparedIndexData[$entityId][$type][$fieldName] ?? '';
    }

    /**
     * @param array $entityConfig
     * @param string $fieldName
     * @param string $configName
     * @param string $default
     * @return string
     * @throws InvalidConfigurationException
     */
    private function getFieldConfig(array $entityConfig, $fieldName, $configName, $default = null)
    {
        $cacheKey = md5(json_encode($entityConfig)) . $fieldName . $configName;

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $fields = array_filter($entityConfig['fields'], function ($fieldConfig) use ($fieldName, $configName) {
            if (!array_key_exists('name', $fieldConfig)) {
                return false;
            }

            if (!array_key_exists($configName, $fieldConfig)) {
                return false;
            }

            return $fieldConfig['name'] === $fieldName ||
                $this->placeholderHelper->isNameMatch($fieldConfig['name'], $fieldName);
        });

        if (!$fields) {
            if ($default) {
                return $default;
            }

            if ($fieldName === self::ALL_TEXT_L10N_FIELD) {
                return $configName === 'type' ? Query::TYPE_TEXT : $fieldName;
            }

            throw new InvalidConfigurationException(
                sprintf('Missing option "%s" for "%s" field', $configName, $fieldName)
            );
        }

        $field = $this->findBestMatchedFieldConfig($fields);

        $result = $field[$configName];

        $this->cache[$cacheKey] = $result;

        return $result;
    }

    /**
     * Keep HTML in text fields except all_text* fields
     *
     * @param string $type
     * @param string $fieldName
     * @param mixed $value
     * @return mixed|string
     */
    protected function clearValue($type, $fieldName, $value)
    {
        if (is_array($value)) {
            foreach ($value as $key => $element) {
                $value[$key] = $this->clearValue($type, $fieldName, $element);
            }

            return $value;
        }

        if ($type === Query::TYPE_TEXT && str_starts_with($fieldName, self::ALL_TEXT_PREFIX)) {
            $value = (string)$value;
            if ($value) {
                $value = $this->htmlTagHelper->stripTags((string)$value);
                $value = $this->htmlTagHelper->stripLongWords($value);
            }
        }

        return $value;
    }

    /**
     * @param string $entityClass
     * @param QueryBuilder $queryBuilder
     * @param array $context
     * $context = [
     *     'currentWebsiteId' int Current website id. Should not be passed manually. It is computed from 'websiteIds'
     * ]
     *
     * @return QueryBuilder
     */
    public function getRestrictedEntitiesQueryBuilder($entityClass, $queryBuilder, array $context)
    {
        $entityAlias = $this->entityAliasResolver->getAlias($entityClass);

        $restrictEntitiesEvent = new Event\RestrictIndexEntityEvent($queryBuilder, $context);
        $this->eventDispatcher->dispatch($restrictEntitiesEvent, Event\RestrictIndexEntityEvent::NAME);
        $this->eventDispatcher->dispatch(
            $restrictEntitiesEvent,
            sprintf('%s.%s', Event\RestrictIndexEntityEvent::NAME, $entityAlias)
        );

        return $restrictEntitiesEvent->getQueryBuilder();
    }

    /**
     * Finds best matched field config based on length of field name without placeholders
     *
     * @param array $fields
     *
     * @return array
     */
    private function findBestMatchedFieldConfig(array $fields): array
    {
        $field = end($fields);

        if (count($fields) > 1) {
            $availablePlaceholders = $this->placeholderHelper->getPlaceholderKeys();

            $lastCheckedFieldLength = 0;
            foreach ($fields as $keyFieldName => $config) {
                $cleanFieldName = str_replace($availablePlaceholders, '', $keyFieldName);
                if (strlen($cleanFieldName) > $lastCheckedFieldLength) {
                    $lastCheckedFieldLength = strlen($cleanFieldName);
                    $field = $config;
                }
            }
        }

        return $field;
    }
}
