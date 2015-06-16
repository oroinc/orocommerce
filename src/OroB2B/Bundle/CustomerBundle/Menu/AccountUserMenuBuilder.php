<?php

namespace OroB2B\Bundle\CustomerBundle\Menu;

use Knp\Menu\ItemInterface;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\NavigationBundle\Menu\BuilderInterface;

class AccountUserMenuBuilder implements BuilderInterface
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

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
                $this->translator->trans('orob2b.customer.menu.account_user_logout.label'),
                [
                    'route'          => 'orob2b_customer_account_user_security_logout',
                    'linkAttributes' => ['class' => 'no-hash']
                ]
            );
    }
}
