<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Oro\Bundle\WebsiteSearchBundle\Placeholder\ValueWithPlaceholders;
use Oro\Bundle\WebsiteSearchBundle\Provider\IndexDataProvider;

use Symfony\Component\EventDispatcher\Event;

class IndexEntityEvent extends Event
{
    const NAME = 'oro_website_search.event.index_entity';

    /**
     * @var object[]
     */
    private $entities;

    /**
     * @var array
     */
    private $context;

    /**
     * @var array
     */
    private $entitiesData = [];

    /**
     * @param object[] $entities
     * @param array    $context
     */
    public function __construct(array $entities, array $context)
    {
        $this->context  = $context;
        $this->entities = $entities;
    }

    /**
     * @return array
     */
    public function getEntities()
    {
        return $this->entities;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param int              $entityId
     * @param string           $fieldName
     * @param string|int|float $value
     * @return $this
     */
    public function addField($entityId, $fieldName, $value)
    {
        $this->entitiesData[$entityId][IndexDataProvider::STANDARD_VALUES_KEY][$fieldName] = $value;

        return $this;
    }

    /**
     * @param int    $entityId
     * @param string $fieldName
     * @param string|int|float
     * @param array  $placeholders
     * @return $this
     */
    public function addPlaceholderField($entityId, $fieldName, $value, $placeholders)
    {
        $this->entitiesData[$entityId][IndexDataProvider::PLACEHOLDER_VALUES_KEY][$fieldName][] =
            new ValueWithPlaceholders($value, $placeholders);

        return $this;
    }

    /**
     * @param string $entityId
     * @param string $fieldName
     * @param string $string
     * @param string $placeholderKey
     * @param string $placeholderValue
     */
    public function appendToPlaceholderField($entityId, $fieldName, $string, $placeholderKey, $placeholderValue)
    {
        $placeholderData = $this->getPlaceholderFieldValue($entityId, $fieldName);

        if (null === $placeholderData) {
            return;
        }

        $resultPlaceholderData = [];

        foreach ($placeholderData as $valueWithPlaceholders) {
            $placeholders = $valueWithPlaceholders->getPlaceholders();
            $value        = $valueWithPlaceholders->getValue();
            $isMatchin    = isset($placeholders[$placeholderKey]) &&
                            $placeholderValue === $placeholders[$placeholderKey];
            if (true === $isMatchin) {
                $newValue                 = $value . ' ' . $string;
                $newValueWithPlaceholders = new ValueWithPlaceholders($newValue, $placeholders);
                $resultPlaceholderData[]  = $newValueWithPlaceholders;
            } else {
                $resultPlaceholderData[] = $valueWithPlaceholders;
            }
        }

        $this->entitiesData[$entityId][IndexDataProvider::PLACEHOLDER_VALUES_KEY][$fieldName] = $resultPlaceholderData;
    }

    /**
     * @param $entityId
     * @param $fieldName
     * @return string|object|null
     */
    public function getFieldValue($entityId, $fieldName)
    {
        return $this->entitiesData[$entityId][IndexDataProvider::STANDARD_VALUES_KEY][$fieldName] ?? null;
    }

    /**
     * @param $entityId
     * @param $fieldName
     * @return ValueWithPlaceholders[]|null
     */
    public function getPlaceholderFieldValue($entityId, $fieldName)
    {
        return $this->entitiesData[$entityId][IndexDataProvider::PLACEHOLDER_VALUES_KEY][$fieldName] ?? null;
    }

    /**
     * @return array
     */
    public function getEntitiesData()
    {
        return $this->entitiesData;
    }
}
