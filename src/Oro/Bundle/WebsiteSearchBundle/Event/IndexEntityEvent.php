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
     * @param string $fieldType
     * @param string $fieldName
     * @param string|int|float $value
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function addField($entityId, $fieldType, $fieldName, $value)
    {
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
        if (!in_array($fieldType, self::$fieldTypes, true)) {
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
