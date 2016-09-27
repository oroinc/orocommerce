<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\SearchBundle\Query\Query;

class IndexEntityEvent extends Event
{
    const NAME = 'oro_website_search.event.index_entity';

    /**
     * @var array
     */
    private static $fieldTypes = [
        Query::TYPE_DATETIME,
        Query::TYPE_DECIMAL,
        Query::TYPE_INTEGER,
        Query::TYPE_TEXT
    ];

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var array
     */
    private $entityIds;

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
     * @param array  $entityIds
     * @param array  $context
     */
    public function __construct($entityClass, array $entityIds, array $context)
    {
        $this->context     = $context;
        $this->entityIds   = array_combine($entityIds, $entityIds);
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
    public function getEntityIds()
    {
        // @todo: check performance and optimize it
        return array_values($this->entityIds);
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
        return $this->addFieldInternal($entityId, $fieldType, $fieldName, $value);
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
        return $this->addFieldInternal($entityId, $fieldType, $fieldName, $value, true);
    }

    /**
     * @return array
     */
    public function getEntitiesData()
    {
        return $this->entitiesData;
    }

    /**
     * @param int              $entityId
     * @param string           $fieldType
     * @param string           $fieldName
     * @param string|int|float $value
     * @param bool             $appendMode
     * @return $this
     */
    private function addFieldInternal($entityId, $fieldType, $fieldName, $value, $appendMode = false)
    {
        if (!isset($this->entityIds[$entityId])) {
            throw new \InvalidArgumentException(
                sprintf('There is no entity with id %s', $entityId)
            );
        }

        $this->assertFieldType($fieldType);

        if (!isset(
            $this->entitiesData[$entityId],
            $this->entitiesData[$entityId][$fieldType],
            $this->entitiesData[$entityId][$fieldType][$fieldName]
        )) {
            $this->entitiesData[$entityId][$fieldType][$fieldName] = '';
        }

        if (false === $appendMode) {
            $this->entitiesData[$entityId][$fieldType][$fieldName] = $value;

            return $this;
        }

        $this->entitiesData[$entityId][$fieldType][$fieldName] .= $value;

        return $this;
    }

    /**
     * @param string $fieldType
     * @throws \InvalidArgumentException
     */
    private function assertFieldType($fieldType)
    {
        if (!in_array($fieldType, self::$fieldTypes, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Field type must be one of %s',
                    implode(', ', self::$fieldTypes)
                )
            );
        }
    }
}
