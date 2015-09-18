<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Menu;

use OroB2B\Bundle\AccountBundle\Menu\AccountUserMenuBuilder;

class AccountUserMenuBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AccountUserMenuBuilder
     */
    protected $builder;

    protected function setUp()
    {
        $this->builder = new AccountUserMenuBuilder();
    }

    protected function tearDown()
    {
        unset($this->builder);
    }

    public function testBuild()
    {
        $child = $this->getMock('Knp\Menu\ItemInterface');
        $child->expects($this->once())
            ->method('setLabel')
            ->with('')
            ->willReturnSelf();
        $child->expects($this->once())
            ->method('setAttribute')
            ->with('class', 'divider')
            ->willReturnSelf();

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Knp\Menu\ItemInterface $menu */
        $menu = $this->getMock('Knp\Menu\ItemInterface');
        $menu->expects($this->at(0))
            ->method('setExtra')
            ->with('type', 'dropdown');

        $menu->expects($this->at(1))
            ->method('addChild')
            ->willReturn($child);

        $menu->expects($this->at(2))
            ->method('addChild')
            ->with(
                'orob2b.account.menu.account_user_logout.label',
                [
                    'route' => 'orob2b_account_account_user_security_logout',
                    'linkAttributes' => ['class' => 'no-hash']
                ]
            );

        $this->builder->build($menu);
    }
}
