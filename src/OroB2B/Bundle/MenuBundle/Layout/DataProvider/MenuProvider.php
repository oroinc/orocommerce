<?php

namespace OroB2B\Bundle\MenuBundle\Layout\DataProvider;

use Knp\Menu\ItemInterface;
use Knp\Menu\Provider\MenuProviderInterface;

// TODO: Remove extends from AbstractServerRenderDataProvider after closing ticket BB-2188
use Oro\Component\Layout\AbstractServerRenderDataProvider;

class MenuProvider extends AbstractServerRenderDataProvider
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
     * @param $menuTitle
     *
     * @return ItemInterface
     */
    public function getMenu($menuTitle)
    {
        if (!$this->menuProvider->has($menuTitle)) {
            throw new \RuntimeException(sprintf('Menu "%s" doesn\'t exist.', $menuTitle));
        }

        return $this->menuProvider->get($menuTitle);
    }
}
