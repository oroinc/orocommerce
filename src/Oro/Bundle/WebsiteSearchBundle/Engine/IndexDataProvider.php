<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Event;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderInterface;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderValue;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * Class is responsible for triggering all events during indexation
 * and returning all collected and prepared for saving event data
 */
class IndexDataProvider
{
    use ContextTrait;

    const ALL_TEXT_FIELD = 'all_text';
    const ALL_TEXT_L10N_FIELD = 'all_text_LOCALIZATION_ID';

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var EntityAliasResolver */
    private $entityAliasResolver;

    /** @var PlaceholderInterface */
    private $placeholder;

    /** @var HtmlTagHelper */
    private $htmlTagHelper;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param EntityAliasResolver $entityAliasResolver
     * @param PlaceholderInterface $placeholder
     * @param HtmlTagHelper $htmlTagHelper
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        EntityAliasResolver $entityAliasResolver,
        PlaceholderInterface $placeholder,
        HtmlTagHelper $htmlTagHelper
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->entityAliasResolver = $entityAliasResolver;
        $this->placeholder = $placeholder;
        $this->htmlTagHelper = $htmlTagHelper;
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
        $this->eventDispatcher->dispatch(Event\CollectContextEvent::NAME, $collectContextEvent);

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
        $entityAlias = $this->entityAliasResolver->getAlias($entityClass);

        $indexEntityEvent = new Event\IndexEntityEvent($restrictedEntities, $context);
        $this->eventDispatcher->dispatch(Event\IndexEntityEvent::NAME, $indexEntityEvent);
        $this->eventDispatcher->dispatch(
            sprintf('%s.%s', Event\IndexEntityEvent::NAME, $entityAlias),
            $indexEntityEvent
        );

        return $this->prepareIndexData($indexEntityEvent->getEntitiesData(), $entityConfig);
    }

    /**
     * Adds field types according to entity config, applies placeholders
     * @param array $indexData
     * @param array $entityConfig
     * @return array Structured and cleared data ready to be saved
     */
    private function prepareIndexData(array $indexData, array $entityConfig)
    {
        $preparedIndexData = [];

        if (!array_key_exists('fields', $entityConfig)) {
            return $preparedIndexData;
        }

        $allText = $this->getFieldConfig($entityConfig, self::ALL_TEXT_FIELD, 'name', self::ALL_TEXT_FIELD);
        $allTextL10N = $this->getFieldConfig(
            $entityConfig,
            self::ALL_TEXT_L10N_FIELD,
            'name',
            self::ALL_TEXT_L10N_FIELD
        );

        foreach ($indexData as $entityId => $fieldsValues) {
            $L10NFieldNames = [];

            foreach ($this->toArray($fieldsValues) as $fieldName => $values) {
                $type = $this->getFieldConfig($entityConfig, $fieldName, 'type');
                $name = $this->getFieldConfig($entityConfig, $fieldName, 'name');

                foreach ($this->toArray($values) as $value) {
                    $singleValueFieldName = $name;
                    if ($value instanceof PlaceholderValue) {
                        $placeholders = $value->getPlaceholders();
                        $value = $value->getValue();

                        if ($type === Query::TYPE_TEXT) {
                            $L10NFieldName = $this->placeholder->replace($allTextL10N, $placeholders);
                            $L10NFieldNames[$L10NFieldName] = $L10NFieldName;
                            $this->setIndexValue($preparedIndexData, $entityId, $L10NFieldName, $value, $type);
                        }

                        $singleValueFieldName = $this->placeholder->replace($singleValueFieldName, $placeholders);
                    } elseif ($type === Query::TYPE_TEXT) {
                        $this->setIndexValue($preparedIndexData, $entityId, $allText, $value, $type);
                    }

                    $this->setIndexValue($preparedIndexData, $entityId, $singleValueFieldName, $value, $type);
                }
            }

            $allTextValue = $this->getIndexValue($preparedIndexData, $entityId, $allText);
            foreach ($L10NFieldNames as $L10NFieldName) {
                $fieldsValue = $this->getIndexValue($preparedIndexData, $entityId, $L10NFieldName);
                $this->setIndexValue($preparedIndexData, $entityId, $allText, $fieldsValue);
                $this->setIndexValue($preparedIndexData, $entityId, $L10NFieldName, $allTextValue);
            }
        }

        return $preparedIndexData;
    }

    /**
     * @param mixed $value
     * @return array
     */
    private function toArray($value)
    {
        if (is_array($value)) {
            return $value;
        }

        return [$value];
    }

    /**
     * @param array $preparedIndexData
     * @param int $entityId
     * @param string $fileName
     * @param string $value
     * @param string $type
     */
    private function setIndexValue(array &$preparedIndexData, $entityId, $fileName, $value, $type = Query::TYPE_TEXT)
    {
        $value = $this->clearValue($type, $value);

        if (!$value) {
            return;
        }

        if ($type === Query::TYPE_TEXT) {
            $existingValue = $this->getIndexValue($preparedIndexData, $entityId, $fileName);
            if ($existingValue) {
                if (false === strpos($existingValue, $value)) {
                    $value = $existingValue.' '.$value;
                } else {
                    $value = $existingValue;
                }
            }
        }

        $preparedIndexData[$entityId][$type][$fileName] = $value;
    }

    /**
     * @param array $preparedIndexData
     * @param int $entityId
     * @param string $fileName
     * @return string
     */
    private function getIndexValue(array &$preparedIndexData, $entityId, $fileName)
    {
        return isset($preparedIndexData[$entityId][Query::TYPE_TEXT][$fileName])
            ? $preparedIndexData[$entityId][Query::TYPE_TEXT][$fileName] : '';
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
        $fields = array_filter($entityConfig['fields'], function ($fieldConfig) use ($fieldName, $configName) {
            if (!array_key_exists('name', $fieldConfig)) {
                return false;
            }

            if (!array_key_exists($configName, $fieldConfig)) {
                return false;
            }

            return $fieldConfig['name'] === $fieldName;
        });

        if (!$fields) {
            if ($default) {
                return $default;
            }

            if (in_array($fieldName, [self::ALL_TEXT_FIELD, self::ALL_TEXT_L10N_FIELD], true)) {
                return $configName === 'type' ? Query::TYPE_TEXT : $fieldName;
            }

            throw new InvalidConfigurationException(
                sprintf('Missing option "%s" for "%s" field', $configName, $fieldName)
            );
        }

        $field = end($fields);

        return $field[$configName];
    }

    /**
     * Checks if value is text type and applies stripping tags
     * @param string $type
     * @param string $value
     * @return string
     */
    private function clearValue($type, $value)
    {
        if ($type === Query::TYPE_TEXT) {
            $value = $this->htmlTagHelper->stripTags((string)$value);
        }

        return $value;
    }

    /**
     * @param string $entityClass
     * @param QueryBuilder $queryBuilder
     * @param array $context
     * @return QueryBuilder
     */
    public function getRestrictedEntitiesQueryBuilder($entityClass, $queryBuilder, array $context)
    {
        $entityAlias = $this->entityAliasResolver->getAlias($entityClass);

        $restrictEntitiesEvent = new Event\RestrictIndexEntityEvent($queryBuilder, $context);
        $this->eventDispatcher->dispatch(Event\RestrictIndexEntityEvent::NAME, $restrictEntitiesEvent);
        $this->eventDispatcher->dispatch(
            sprintf('%s.%s', Event\RestrictIndexEntityEvent::NAME, $entityAlias),
            $restrictEntitiesEvent
        );

        return $restrictEntitiesEvent->getQueryBuilder();
    }
}
