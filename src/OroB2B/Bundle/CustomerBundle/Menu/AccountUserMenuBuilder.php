<?php

namespace OroB2B\Bundle\CustomerBundle\Menu;

use Knp\Menu\ItemInterface;

use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\NavigationBundle\Menu\BuilderInterface;

class AccountUserMenuBuilder implements BuilderInterface
{
    /**
     * @var SecurityContextInterface
     */
    private $securityContext;

    /**
     * @param SecurityContextInterface $securityContext
     */
    public function __construct(SecurityContextInterface $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    public function build(ItemInterface $menu, array $options = array(), $alias = null)
    {
        $menu->setExtra('type', 'dropdown');

        $menu
            ->addChild('divider-' . rand(1, 99999))
            ->setLabel('')
            ->setAttribute('class', 'divider');

        $menu
            ->addChild(
                'Logout',
                [
                    'route'          => 'orob2b_customer_account_user_security_logout',
                    'linkAttributes' => ['class' => 'no-hash']
                ]
            );
    }
}
