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
            $parentMenuItem = $this->getRootNode($menuItem->getRoot());
        } else {
            /** @var MenuItem $parentMenuItem */
            $parentMenuItem = $this->getEntityRepository()->find($parentId);
            if ($parentMenuItem->getRoot() !== $menuItem->getRoot()) {
                throw new MenuItemRootChangedException('You can\'t move Menu Item to another menu.');
            }
        }

        if ($position) {
            $children = $this->getEntityRepository()->getChildren($parentMenuItem, true, 'left', 'ASC');
            $this->getEntityRepository()->persistAsNextSiblingOf($menuItem, $children[$position - 1]);
        } else {
            $this->getEntityRepository()->persistAsFirstChildOf($menuItem, $parentMenuItem);
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
            'parent' => $entity->getParentMenuItem() ? $entity->getParentMenuItem()->getId() : null,
            'text' => $entity->getDefaultTitle()->getString(),
            'state' => [
                'opened' => $entity->getParentMenuItem() === null
            ]
        ];
    }
}
