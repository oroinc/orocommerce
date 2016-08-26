<?php

namespace Oro\Bundle\MenuBundle\Menu;

use Knp\Menu\ItemInterface;
use Knp\Menu\Loader\ArrayLoader;
use Knp\Menu\Util\MenuManipulator;

class MenuSerializer
{
    /**
     * @var MenuManipulator
     */
    protected $manipulator;

    /**
     * @var ArrayLoader
     */
    protected $arrayLoader;

    /**
     * @param ArrayLoader $arrayLoader
     * @param MenuManipulator $manipulator
     */
    public function __construct(ArrayLoader $arrayLoader, MenuManipulator $manipulator)
    {
        $this->arrayLoader = $arrayLoader;
        $this->manipulator = $manipulator;
    }

    /**
     * @param ItemInterface $menu
     * @return array
     */
    public function serialize(ItemInterface $menu)
    {
        return $this->manipulator->toArray($menu);
    }

    /**
     * @param array $data
     * @return ItemInterface
     */
    public function deserialize(array $data)
    {
        return $this->arrayLoader->load($data);
    }
}
