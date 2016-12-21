<?php

namespace Oro\Bundle\CommerceMenuBundle\Layout\DataProvider;

use Knp\Menu\ItemInterface;
use Knp\Menu\Provider\MenuProviderInterface;

class MenuProvider
{
    /** @var MenuProviderInterface */
    private $provider;

    /**
     * @param MenuProviderInterface $provider
     */
    public function __construct(MenuProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Retrieves item in the menu, eventually using the menu provider.
     *
     * @param string $menuName
     * @param array  $options
     *
     * @return ItemInterface
     */
    public function getMenu($menuName, array $options = ['check_access' => false])
    {
        return $this->provider->get($menuName, $options);
    }
}
