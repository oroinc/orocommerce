<?php

namespace OroB2B\Bundle\MenuBundle\Menu;

use Knp\Menu\ItemInterface;

interface BuilderInterface
{
    /**
     * Modify menu by adding, removing or editing items.
     *
     * @param string $alias
     * @param array                   $options
     * @param string|null             $alias
     * @return ItemInterface
     */
    public function build($alias, array $options = []);

    /**
     * @param $alias
     * @return bool
     */
    public function isSupported($alias);
}
