<?php

namespace Oro\Bundle\CustomerBundle\JsTree;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Component\Tree\Handler\AbstractTreeHandler;

class CustomerTreeHandler extends AbstractTreeHandler
{
    /**
     * @param Customer $root
     * @param bool $includeRoot
     * @return array
     */
    protected function getNodes($root, $includeRoot)
    {
        $entities = [];
        if ($includeRoot) {
            $entities[] = $root;
        }
        return array_merge($entities, $this->buildTreeRecursive($root));
    }

    /**
     * @param Customer $entity
     * @return array
     */
    protected function formatEntity($entity)
    {
        return [
            'id'     => $entity->getId(),
            'parent' => $entity->getParent() ? $entity->getParent()->getId() : null,
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
     * {@inheritdoc}
     */
    protected function moveProcessing($entityId, $parentId, $position)
    {
        throw new \LogicException('Customer moving is not supported');
    }
}
