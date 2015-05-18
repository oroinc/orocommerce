<?php

namespace OroB2B\Bundle\CustomerBundle\JsTree;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;

class CustomerTreeHandler
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
        /** @var Customer $root */
        $root = $this->getEntityRepository()->find((int)$rootId);

        $entities = [];

        $childrenEntities = $this->buildTreeRecursive($root);

        return $this->formatTree(array_merge($entities, $childrenEntities), $rootId);
    }

    /**
     * @param array|Customer[] $entities
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
     * @param Customer $entity
     * @param int $rootId
     * @return array
     */
    protected function formatEntity(Customer $entity, $rootId)
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
     * @param Customer $entity
     * @return array
     */
    protected function buildTreeRecursive(Customer $entity)
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
