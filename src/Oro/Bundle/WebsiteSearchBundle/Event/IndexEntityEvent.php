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
     * @param array $context
     */
    public function __construct(array $entities, array $context)
    {
        $this->context = $context;
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
     * @param string           $fieldType
     * @param string           $fieldName
     * @param string|int|float $value
     * @return $this
     */
    public function addField($entityId, $fieldType, $fieldName, $value)
    {
        return $this->processField($entityId, $fieldType, $fieldName, $value);
    }

    /**
     * @param int              $entityId
     * @param string           $fieldType
     * @param string           $fieldName
     * @param string|int|float $value
     * @return $this
     */
    public function appendField($entityId, $fieldType, $fieldName, $value)
    {
        return $this->processField($entityId, $fieldType, $fieldName, $value, true);
    }

    /**
     * @param int $entityId
     * @param string $fieldName
     * @param string|int|float
     * @param array $placeholders
     * @return $this
     */
    public function addPlaceholderField($entityId, $fieldName, $value, $placeholders)
    {
        $this->entitiesData[$entityId][IndexDataProvider::PLACEHOLDER_VALUES_KEY][$fieldName][] =
            new ValueWithPlaceholders($value, $placeholders);

        return $this;
    }

    /**
     * @return array
     */
    public function getEntitiesData()
    {
        return $this->entitiesData;
    }

    /**
     * Add or append data to an existing field.
     *
     * @param int              $entityId
     * @param string           $fieldType
     * @param string           $fieldName
     * @param string|int|float $value
     * @param bool             $appendMode
     * @return $this
     */
    private function processField($entityId, $fieldType, $fieldName, $value, $appendMode = false)
    {
        if (!isset(
            $this->entitiesData[$entityId],
            $this->entitiesData[$entityId][IndexDataProvider::STANDARD_VALUES_KEY],
            $this->entitiesData[$entityId][IndexDataProvider::STANDARD_VALUES_KEY][$fieldName]
        )) {
            $this->entitiesData[$entityId][IndexDataProvider::STANDARD_VALUES_KEY][$fieldName] = '';
        }

        if (false === $appendMode) {
            $this->entitiesData[$entityId][IndexDataProvider::STANDARD_VALUES_KEY][$fieldName] = $value;

            return $this;
        }

        $this->entitiesData[$entityId][IndexDataProvider::STANDARD_VALUES_KEY][$fieldName] .= $value;

        return $this;
    }
}
