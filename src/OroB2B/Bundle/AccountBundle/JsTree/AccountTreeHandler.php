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

        $children = $root->getChildren();

        foreach ($children->toArray() as $child) {
            $entities[] = $child;

            $entities = array_merge($entities, $this->getNodes($child, $includeRoot));
        }

        return $entities;
    }

    /**
     * @param Account $entity
     * @param Account $root
     * @return array
     */
    protected function formatEntity($entity, $root)
    {
        return [
            'id'     => $entity->getId(),
            'parent' => $entity->getParent() && $entity->getParent()->getId() !== $root->getId()
                ? $entity->getParent()->getId()
                : '#',
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
