<?php

namespace Oro\Bundle\MenuBundle\Layout\DataProvider;

use Knp\Menu\ItemInterface;
use Knp\Menu\Provider\MenuProviderInterface;

class MenuProvider
{
    /** @var MenuProviderInterface */
    protected $menuProvider;

    /**
     * @param MenuProviderInterface $menuProvider
     */
    public function __construct(MenuProviderInterface $menuProvider)
    {
        $this->menuProvider = $menuProvider;
    }

    /**
     * @param string $name
     *
     * @return ItemInterface
     */
    public function getMenu($name)
    {
        if (!$this->menuProvider->has($name)) {
            throw new \RuntimeException(sprintf('Menu "%s" doesn\'t exist.', $name));
        }

        return $this->menuProvider->get($name);
    }
}
