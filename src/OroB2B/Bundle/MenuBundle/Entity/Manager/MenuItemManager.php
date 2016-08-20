<?php

namespace Oro\Bundle\MenuBundle\Entity\Manager;

use Knp\Menu\ItemInterface;

use Oro\Bundle\MenuBundle\Entity\MenuItem;

class MenuItemManager
{
    /**
     * @param ItemInterface $item
     * @return MenuItem
     */
    public function createFromItem(ItemInterface $item)
    {
        $extras = $item->getExtras();
        // isAllowed should be unset because in should be recalculated each time and not gone from the database
        unset($extras['isAllowed']);
        $entity = new MenuItem();
        $entity->setDefaultTitle($item->getName())
            ->setUri($item->getUri())
            ->setDisplay($item->isDisplayed())
            ->setDisplayChildren($item->getDisplayChildren())
            ->setData([
                'attributes' => $item->getAttributes(),
                'linkAttributes' => $item->getLinkAttributes(),
                'childrenAttributes' => $item->getChildrenAttributes(),
                'labelAttributes' => $item->getLabelAttributes(),
                'extras' => $extras,
            ]);
        foreach ($item->getChildren() as $child) {
            $entity->addChild($this->createFromItem($child));
        }
        return $entity;
    }
}
