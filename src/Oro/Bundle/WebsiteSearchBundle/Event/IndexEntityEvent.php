<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Oro\Bundle\SearchBundle\Query\Query;

use Symfony\Component\EventDispatcher\Event;

class IndexEntityEvent extends Event
{
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
     * @var array
     */
    private static $fieldTypesHash;

    /**
     * @var string
     */
    private $entityName;

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
     * @param string $entityName
     * @param array $entityIds
     * @param array $context
     */
    public function __construct($entityName, array $entityIds, array $context)
    {
        $this->context = $context;
        $this->entityIds = array_combine($entityIds, $entityIds);
        $this->entityName = $entityName;

        if (null === self::$fieldTypesHash) {
            self::$fieldTypesHash = array_flip(self::$fieldTypes);
        }
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * @return array
     */
    public function getEntityIds()
    {
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
     * @param int $entityId
     * @param string $fieldType
     * @param string $fieldName
     * @param string|int|float $value
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function addField($entityId, $fieldType, $fieldName, $value)
    {
        if (!isset($this->entityIds[$entityId])) {
            throw new \InvalidArgumentException(
                sprintf('There is no entity with id %s', $entityId)
            );
        }

        $this->assertFieldType($fieldType);

        $this->entitiesData[$entityId][$fieldType][$fieldName] = $value;

        return $this;
    }

    /**
     * @param string $fieldType
     * @throws \InvalidArgumentException
     */
    private function assertFieldType($fieldType)
    {
        if (!isset(self::$fieldTypesHash[$fieldType])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Field type must be one of %s',
                    implode(', ', self::$fieldTypes)
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
}
