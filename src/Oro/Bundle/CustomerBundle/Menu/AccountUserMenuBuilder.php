<?php

namespace Oro\Bundle\CustomerBundle\Menu;

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
            ->addChild('divider-account-user-before-logout')
            ->setLabel('')
            ->setExtra('divider', true);

        $menu
            ->addChild(
                'oro.customer.menu.account_user_logout.label',
                [
                    'route'          => 'oro_customer_account_user_security_logout',
                    'linkAttributes' => ['class' => 'no-hash']
                ]
            );
    }
}
