<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderValue;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event used to collect website search index data per entity
 */
class IndexEntityEvent extends Event
{
    const NAME = 'oro_website_search.event.index_entity';

    /**
     * @var string
     */
    private $entityClass;

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
     * @param string $entityClass
     * @param object[] $entities
     * @param array $context
     */
    public function __construct($entityClass, array $entities, array $context)
    {
        $this->entityClass = $entityClass;
        $this->context = $context;
        $this->entities = $entities;
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
     * @param int|string $entityId
     * @param string $fieldName
     * @param string|int|float|\DateTime|array $value
     * @param bool $addToAllText
     * @return $this
     * @throws \InvalidArgumentException if value is array
     */
    public function addField($entityId, $fieldName, $value, $addToAllText = false)
    {
        $this->validate($value);

        $this->entitiesData[$entityId][$fieldName][] = [
            'value' => $value,
            'all_text' => $addToAllText
        ];

        return $this;
    }

    /**
     * @param int $entityId
     * @param string $fieldName
     * @param string|int|float|\DateTime $value
     * @param array $placeholders
     * @param bool $addToAllText
     * @return $this
     * @throws \InvalidArgumentException if value is array
     */
    public function addPlaceholderField($entityId, $fieldName, $value, $placeholders, $addToAllText = false)
    {
        $this->validate($value);

        $this->entitiesData[$entityId][$fieldName][] = [
            'value' => new PlaceholderValue($value, $placeholders),
            'all_text' => $addToAllText
        ];

        return $this;
    }

    /**
     * @param mixed $value
     */
    protected function validate($value)
    {
        if (is_array($value)) {
            foreach ($value as $element) {
                $this->validate($element);
            }
            return;
        }

        if (!is_scalar($value) && !$value instanceof \DateTime && !is_null($value)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Scalars, \DateTime and NULL are supported only, "%s" given',
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }
    }

    /**
     * @return array
     */
    public function getEntitiesData()
    {
        return $this->entitiesData;
    }

    public function setEntitiesData(array $entitiesData): self
    {
        $this->entitiesData = $entitiesData;

        return $this;
    }

    /**
     * @param int|string $entityId
     * @return $this
     */
    public function removeEntityData($entityId): self
    {
        unset($this->entitiesData[$entityId]);

        return $this;
    }
}
