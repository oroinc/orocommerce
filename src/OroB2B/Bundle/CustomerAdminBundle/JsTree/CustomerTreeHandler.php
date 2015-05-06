<?php

namespace OroB2B\Bundle\CustomerAdminBundle\JsTree;

use OroB2B\Bundle\CustomerAdminBundle\Entity\Customer;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

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
        $root = $this->getEntityRepository()->find((int) $rootId);

        $entities = [$root];

        $childrenEntities = $this->buildTreeRecursive($root);

        return $this->formatTree(array_merge($entities, $childrenEntities));
    }

    /**
     * @param array|Customer[] $entities
     * @return array
     */
    protected function formatTree(array $entities)
    {
        $formattedTree = [];

        foreach ($entities as $entity) {
            $formattedTree[] = $this->formatEntity($entity);
        }

        return $formattedTree;
    }

    /**
     * @param Customer $entity
     * @return array
     */
    protected function formatEntity(Customer $entity)
    {
        return [
            'id'     => $entity->getId(),
            'parent' => $entity->getParent() ? $entity->getParent()->getId() : '#',
            'text'   => $entity->getName(),
            'state'  => [
                'opened' => !($entity->getChildren() === null)
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

        if ($children && is_array($children)) {
            foreach ($children as $child) {
                $entities[] = $child;

                $entities = array_merge($entities, $this->buildTreeRecursive($child));
            }
        }

        return $entities;
    }

    /**
     * @return ObjectRepository
     */
    protected function getEntityRepository()
    {
        return $this->managerRegistry
            ->getManagerForClass($this->entityClass)
            ->getRepository($this->entityClass);
    }
}
