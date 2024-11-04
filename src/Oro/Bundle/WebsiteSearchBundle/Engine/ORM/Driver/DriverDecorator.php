<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\ORM\Driver;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Entity\AbstractItem;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;

/**
 * Encapsulates currently used database driver. Depending on the current connection to the database
 * the corresponding driver will be used. Each driver should implement DriverInterface interface.
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DriverDecorator implements DriverInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;


    /** @var DriverInterface[] */
    private $availableDrivers = [];

    /** @var DriverInterface */
    private $driver;

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
            $databasePlatform = $em->getConnection()->getDatabasePlatform()->getName();
            if (!array_key_exists($databasePlatform, $this->availableDrivers)) {
                throw new \RuntimeException(sprintf('Missing driver for %s platform', $databasePlatform));
            }

            $this->driver = $this->availableDrivers[$databasePlatform];
            $this->driver->initialize($em);
        }

        return $this->driver;
    }

    #[\Override]
    public function createQueryBuilder($alias)
    {
        return $this->getDriver()->createQueryBuilder($alias);
    }

    #[\Override]
    public function getName()
    {
        return $this->getDriver()->getName();
    }

    #[\Override]
    public function initialize(EntityManagerInterface $entityManager)
    {
        $this->getDriver()->initialize($entityManager);
    }

    #[\Override]
    public function search(Query $query)
    {
        return $this->getDriver()->search($query);
    }

    #[\Override]
    public function removeIndexByAlias($currentAlias)
    {
        return $this->getDriver()->removeIndexByAlias($currentAlias);
    }

    #[\Override]
    public function renameIndexAlias($temporaryAlias, $currentAlias)
    {
        return $this->getDriver()->renameIndexAlias($temporaryAlias, $currentAlias);
    }

    #[\Override]
    public function removeEntities(array $entityIds, $entityClass, $entityAlias = null)
    {
        return $this->getDriver()->removeEntities($entityIds, $entityClass, $entityAlias);
    }

    #[\Override]
    public function removeIndexByClass($entityClass = null)
    {
        return $this->getDriver()->removeIndexByClass($entityClass);
    }

    #[\Override]
    public function createItem()
    {
        return $this->getDriver()->createItem();
    }

    #[\Override]
    public function getRecordsCount(Query $query)
    {
        return $this->getDriver()->getRecordsCount($query);
    }

    #[\Override]
    public function flushWrites()
    {
        $this->getDriver()->flushWrites();
    }

    #[\Override]
    public function writeItem(AbstractItem $item)
    {
        $this->getDriver()->writeItem($item);
    }

    #[\Override]
    public function getAggregatedData(Query $query)
    {
        return $this->getDriver()->getAggregatedData($query);
    }
}
