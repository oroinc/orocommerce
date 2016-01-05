<?php

namespace OroB2B\Bundle\MenuBundle\JsTree;

use OroB2B\Bundle\MenuBundle\Entity\MenuItem;
use OroB2B\Bundle\MenuBundle\JsTree\Exception\MenuItemRootChangedException;
use OroB2B\Component\Tree\Handler\AbstractTreeHandler;

class MenuItemTreeHandler extends AbstractTreeHandler
{
    const ROOT_PARENT_VALUE = '#';

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
     * {@inheritdoc}
     */
    public function createTree($root = null, $includeRoot = true)
    {
        return parent::createTree($root, false);
    }

    /**
     * {@inheritdoc}
     */
    protected function formatEntity($entity, $root)
    {
        $parent = self::ROOT_PARENT_VALUE;
        if ($entity->getParentMenuItem() && (!$root || $entity->getParentMenuItem()->getId() !== $root->getId())) {
            $parent = $entity->getParentMenuItem()->getId();
        }
        return [
            'id' => $entity->getId(),
            'parent' => $parent,
            'text' => $entity->getDefaultTitle(),
            'state' => [
                'opened' => $entity->getParentMenuItem() === null
            ]
        ];
    }
}
