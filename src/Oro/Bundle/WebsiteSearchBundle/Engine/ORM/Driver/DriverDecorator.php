<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\ORM\Driver;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;

class DriverDecorator implements DriverInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var DriverInterface[] */
    private $availableDrivers = [];

    /** @var DriverInterface */
    private $driver;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /** @param DriverInterface $driver */
    public function addDriver(DriverInterface $driver)
    {
        $this->availableDrivers[$driver->getName()] = $driver;
    }

    /**
     * @return DriverInterface
     */
    protected function getDriver()
    {
        if (!$this->driver) {
            $em = $this->doctrineHelper->getEntityManagerForClass(Item::class);

            $databasePlatform = $em->getConnection()->getDriver()->getName();
            if (!array_key_exists($databasePlatform, $this->availableDrivers)) {
                throw new \RuntimeException(sprintf('Missing driver for %s platform', $databasePlatform));
            }

            $this->driver = $this->availableDrivers[$databasePlatform];
            $this->driver->initialize($em);
        }

        return $this->driver;
    }

    /** {@inheritdoc} */
    public function getName()
    {
        return $this->getDriver()->getName();
    }

    /** {@inheritdoc} */
    public function initialize(EntityManagerInterface $entityManager)
    {
        $this->getDriver()->initialize($entityManager);
    }

    /** {@inheritdoc} */
    public function search(Query $query)
    {
        return $this->getDriver()->search($query);
    }

    /** {@inheritdoc} */
    public function removeIndexByAlias($currentAlias)
    {
        return $this->getDriver()->removeIndexByAlias($currentAlias);
    }

    /** {@inheritdoc} */
    public function renameIndexAlias($temporaryAlias, $currentAlias)
    {
        return $this->getDriver()->renameIndexAlias($temporaryAlias, $currentAlias);
    }

    /** {@inheritdoc} */
    public function removeEntities(array $entityIds, $entityClass, $entityAlias = null)
    {
        return $this->getDriver()->removeEntities($entityIds, $entityClass, $entityAlias);
    }

    /** {@inheritdoc} */
    public function removeIndexByClass($entityClass = null)
    {
        return $this->getDriver()->removeIndexByClass($entityClass);
    }

    /** {@inheritdoc} */
    public function createItem()
    {
        return $this->getDriver()->createItem();
    }

    /** {@inheritdoc} */
    public function saveItems(array $items)
    {
        return $this->getDriver()->saveItems($items);
    }

    /** {@inheritdoc} */
    public function getRecordsCount(Query $query)
    {
        return $this->getDriver()->getRecordsCount($query);
    }
}
