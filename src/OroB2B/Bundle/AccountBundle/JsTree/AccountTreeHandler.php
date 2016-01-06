<?php

namespace OroB2B\Bundle\AccountBundle\JsTree;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Component\Tree\Handler\AbstractTreeHandler;

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
        return array_merge($entities, $this->getAllChildren($root));
    }

    /**
     * @param Account $entity
     * @return array
     */
    protected function getAllChildren(Account $entity)
    {
        $entities = [];

        $children = $entity->getChildren();

        foreach ($children->toArray() as $child) {
            $entities[] = $child;

            $entities = array_merge($entities, $this->getAllChildren($child));
        }

        return $entities;
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
     * {@inheritdoc}
     */
    protected function moveProcessing($entityId, $parentId, $position)
    {
        throw new \LogicException('Account moving is not supported');
    }
}
