<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Oro\Bundle\WebsiteSearchBundle\Placeholder\ValueWithPlaceholders;
use Oro\Bundle\WebsiteSearchBundle\Provider\IndexDataProvider;

use Symfony\Component\EventDispatcher\Event;

class IndexEntityEvent extends Event
{
    const NAME = 'oro_website_search.event.index_entity';

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var array
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
     * @param string $entityClass
     * @param object[] $entities
     * @param array $context
     */
    public function __construct($entityClass, array $entities, array $context)
    {
        $this->context = $context;
        $this->entities = $entities;
        $this->entityClass = $entityClass;
    }

    /**
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
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
     * @param int $entityId
     * @param string $fieldName
     * @param string|int|float $value
     * @return $this
     */
    public function addField($entityId, $fieldName, $value)
    {
        $this->entitiesData[$entityId][IndexDataProvider::STANDARD_VALUES_KEY][$fieldName] = $value;

        return $this;
    }

    /**
     * @param int $entityId
     * @param string $fieldName
     * @param string|int|float|array $value If array passed this means batch of fields data needed to "all_text"
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
}
