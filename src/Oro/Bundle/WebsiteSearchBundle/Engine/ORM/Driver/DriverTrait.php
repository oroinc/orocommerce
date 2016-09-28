<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\ORM\Driver;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Entity\Repository\ItemRepository;

/**
 * @method initRepo(\Doctrine\ORM\EntityManager $em, \Doctrine\ORM\Mapping\ClassMetadata $class)
 */
trait DriverTrait
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    /** {@inheritdoc} */
    public function initialize(EntityManagerInterface $entityManager)
    {
        $this->initRepo($entityManager, $entityManager->getClassMetadata(Item::class));

        $this->entityManager = $entityManager;
    }

    /**
     * @return ItemRepository
     */
    protected function getRepository()
    {
        return $this->entityManager->getRepository(Item::class);
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

    /** {@inheritdoc} */
    public function saveItems(array $items)
    {
        array_walk(
            $items,
            function (Item $item) {
                $this->entityManager->persist($item);
            }
        );

        $this->entityManager->flush($items);
        $this->entityManager->clear(Item::class);
    }
}
