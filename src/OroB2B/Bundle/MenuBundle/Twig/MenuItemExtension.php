<?php

namespace OroB2B\Bundle\MenuBundle\Twig;

use OroB2B\Bundle\MenuBundle\Entity\MenuItem;
use OroB2B\Bundle\MenuBundle\JsTree\MenuItemTreeHandler;

class MenuItemExtension extends \Twig_Extension
{
    const NAME = 'orob2b_menu_item_extension';

    /**
     * @var MenuItemTreeHandler
     */
    protected $menuItemTreeHandler;

    /**
     * @param MenuItemTreeHandler $menuItemTreeHandler
     */
    public function __construct(MenuItemTreeHandler $menuItemTreeHandler)
    {
        $this->menuItemTreeHandler = $menuItemTreeHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @inheritDoc
     */
    public function getFunctions()
    {
        return [
            'orob2b_menu_item_list' => new \Twig_Function_Method($this, 'getTree'),
        ];
    }

    /**
     * @param MenuItem $entity
     * @return array
     */
    public function getTree(MenuItem $entity)
    {
        return $this->menuItemTreeHandler->createTree($entity->getRoot());
    }
}
