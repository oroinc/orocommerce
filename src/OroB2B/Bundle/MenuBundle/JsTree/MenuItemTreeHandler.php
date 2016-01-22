<?php

namespace OroB2B\Bundle\MenuBundle\JsTree;

use OroB2B\Bundle\MenuBundle\Entity\MenuItem;
use OroB2B\Bundle\MenuBundle\JsTree\Exception\MenuItemRootChangedException;
use OroB2B\Component\Tree\Handler\AbstractTreeHandler;

class MenuItemTreeHandler extends AbstractTreeHandler
{
    /**
     * {@inheritdoc}
     */
    protected function moveProcessing($entityId, $parentId, $position)
    {
        /** @var MenuItem $menuItem */
        $menuItem = $this->getEntityRepository()->find($entityId);

        if ($parentId === self::ROOT_PARENT_VALUE) {
            throw new MenuItemRootChangedException('Existing menu can\'t be the root');
        } else {
            /** @var MenuItem $parent */
            $parent = $this->getEntityRepository()->find($parentId);
            if ($parent->getRoot() !== $menuItem->getRoot()) {
                throw new MenuItemRootChangedException('You can\'t move Menu Item to another menu.');
            }
        }

        if ($position) {
            $children = $this->getEntityRepository()->getChildren($parent, true, 'left', 'ASC');
            $this->getEntityRepository()->persistAsNextSiblingOf($menuItem, $children[$position - 1]);
        } else {
            $this->getEntityRepository()->persistAsFirstChildOf($menuItem, $parent);
        }
    }

    /**
     * @param MenuItem $entity
     * @return array
     */
    protected function formatEntity($entity)
    {
        return [
            'id' => $entity->getId(),
            'parent' => $entity->getParent() ? $entity->getParent()->getId() : null,
            'text' => $entity->getDefaultTitle()->getString(),
            'state' => [
                'opened' => $entity->getParent() === null
            ]
        ];
    }
}
