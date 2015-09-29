<?php

namespace OroB2B\Bundle\AccountBundle\Menu;

use Knp\Menu\ItemInterface;

use Symfony\Component\Translation\TranslatorInterface;

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
                'orob2b.account.menu.account_user_logout.label',
                [
                    'route'          => 'orob2b_account_account_user_security_logout',
                    'linkAttributes' => ['class' => 'no-hash']
                ]
            );
    }
}
