<?php

namespace Oro\Bundle\MenuBundle\Menu;

use Knp\Menu\ItemInterface;

interface BuilderInterface
{
    const IS_ALLOWED_OPTION_KEY = 'isAllowed';

    /**
     * Create menu by alias
     *
     * @param string $alias
     * @param array $options
     * @param string|null $alias
     * @return ItemInterface
     */
    public function build($alias, array $options = []);

    /**
     * @param $alias
     * @return bool
     */
    public function isSupported($alias);
}
