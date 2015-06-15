<?php

namespace OroB2B\Bundle\CustomerBundle\Menu;

use Knp\Menu\ItemInterface;

use Oro\Bundle\NavigationBundle\Menu\BuilderInterface;

class AccountUserMenuBuilder implements BuilderInterface
{
    /**
     * {@inheritDoc}
     */
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
