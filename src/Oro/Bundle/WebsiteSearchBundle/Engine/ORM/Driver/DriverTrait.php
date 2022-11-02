<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\ORM\Driver;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Entity\Repository\ItemRepository;

/**
 * Provides set of common operations for database drivers.
 * Each operation can be replaced with specific implementation in certain driver.
 * @method initRepo(EntityManagerInterface $em, \Doctrine\ORM\Mapping\ClassMetadata $class)
 * @property EntityManagerInterface $entityManager
 */
trait DriverTrait
{
    /** {@inheritdoc} */
    public function initialize(EntityManagerInterface $entityManager)
    {
        $this->initRepo($entityManager, $entityManager->getClassMetadata(Item::class));
    }

    /**
     * @return ItemRepository
     */
    protected function getRepository()
    {
        return $this->getEntityManager()->getRepository(Item::class);
    }

    /**
     * @param string $currentAlias
     */
    public function removeIndexByAlias($currentAlias)
    {
        return $this->getRepository()->removeIndexByAlias($currentAlias);
    }

    /**
     * @param string $temporaryAlias
     * @param string $currentAlias
     */
    public function renameIndexAlias($temporaryAlias, $currentAlias)
    {
        return $this->getRepository()->renameIndexAlias($temporaryAlias, $currentAlias);
    }

    /**
     * @param array $entityIds
     * @param string $entityClass
     * @param string|null $entityAlias
     */
    public function removeEntities(array $entityIds, $entityClass, $entityAlias = null)
    {
        return $this->getRepository()->removeEntities($entityIds, $entityClass, $entityAlias);
    }

    /**
     * Removes index data for given $entityClass or all classes.
     * @param string $entityClass
     */
    public function removeIndexByClass($entityClass = null)
    {
        return $this->getRepository()->removeIndexByClass($entityClass);
    }

    /** {@inheritdoc} */
    public function createItem()
    {
        $className = Item::class;

        return new $className();
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        if (!$this->entityManager) {
            throw new \RuntimeException(
                'Initialization using DriverInterface::initialize required'
            );
        }

        return $this->entityManager;
    }
}
