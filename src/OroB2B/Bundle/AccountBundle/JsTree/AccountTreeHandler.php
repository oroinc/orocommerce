<?php

namespace OroB2B\Bundle\AccountBundle\JsTree;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\AccountBundle\Entity\Account;

class AccountTreeHandler
{
    /**
     * @param string $entityClass
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct($entityClass, ManagerRegistry $managerRegistry)
    {
        $this->entityClass     = $entityClass;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param int $rootId
     * @return array
     */
    public function createTree($rootId)
    {
        /** @var Account $root */
        $root = $this->getEntityRepository()->find((int)$rootId);

        $entities = [];

        $childrenEntities = $this->buildTreeRecursive($root);

        return $this->formatTree(array_merge($entities, $childrenEntities), $rootId);
    }

    /**
     * @param array|Account[] $entities
     * @param int $rootId
     * @return array
     */
    protected function formatTree(array $entities, $rootId)
    {
        $formattedTree = [];

        foreach ($entities as $entity) {
            $formattedTree[] = $this->formatEntity($entity, $rootId);
        }

        return $formattedTree;
    }

    /**
     * @param Account $entity
     * @param int $rootId
     * @return array
     */
    protected function formatEntity(Account $entity, $rootId)
    {
        return [
            'id'     => $entity->getId(),
            'parent' => $entity->getParent() && $entity->getParent()->getId() !== $rootId
                ? $entity->getParent()->getId()
                : '#',
            'text'   => $entity->getName(),
            'state'  => [
                'opened' => !$entity->getChildren()->isEmpty()
            ]
        ];
    }

    /**
     * @param Account $entity
     * @return array
     */
    protected function buildTreeRecursive(Account $entity)
    {
        $entities = [];

        $children = $entity->getChildren();

        foreach ($children->toArray() as $child) {
            $entities[] = $child;

            $entities = array_merge($entities, $this->buildTreeRecursive($child));
        }

        return $entities;
    }

    /**
     * @return ObjectRepository
     */
    protected function getEntityRepository()
    {
        return $this->managerRegistry->getRepository($this->entityClass);
    }
}
