<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderValue;

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
     * @param int $entityId
     * @param string $fieldName
     * @param string|int|float|\DateTime $value
     * @return $this
     * @throws \InvalidArgumentException if value is array
     */
    public function addField($entityId, $fieldName, $value)
    {
        if (!is_scalar($value) && !$value instanceof \DateTime) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Scalars and \DateTime are supported only, "%s" given',
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }

        $this->entitiesData[$entityId][$fieldName] = $value;

        return $this;
    }

    /**
     * @param int $entityId
     * @param string $fieldName
     * @param string $value
     * @param array $placeholders
     * @return $this
     * @throws \InvalidArgumentException if value is array
     */
    public function addPlaceholderField($entityId, $fieldName, $value, $placeholders)
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Strings are supported only, "%s" given',
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }

        $this->entitiesData[$entityId][$fieldName][$value] = new PlaceholderValue($value, $placeholders);

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
