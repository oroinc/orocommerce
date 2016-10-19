<?php

namespace Oro\Bundle\CustomerBundle\JsTree;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Component\Tree\Handler\AbstractTreeHandler;

class AccountTreeHandler extends AbstractTreeHandler
{
    /**
     * @param Account $root
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
     * @param Account $entity
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
     * {@inheritdoc}
     */
    protected function moveProcessing($entityId, $parentId, $position)
    {
        throw new \LogicException('Account moving is not supported');
    }
}
