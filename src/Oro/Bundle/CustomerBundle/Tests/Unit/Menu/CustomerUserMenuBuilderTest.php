<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Menu;

use Oro\Bundle\CustomerBundle\Menu\CustomerUserMenuBuilder;

class CustomerUserMenuBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CustomerUserMenuBuilder
     */
    protected $builder;

    protected function setUp()
    {
        $this->builder = new CustomerUserMenuBuilder();
    }

    protected function tearDown()
    {
        unset($this->builder);
    }

    public function testBuild()
    {
        $child = $this->createMock('Knp\Menu\ItemInterface');
        $child->expects($this->once())
            ->method('setLabel')
            ->with('')
            ->willReturnSelf();
        $child->expects($this->once())
            ->method('setExtra')
            ->with('divider', true)
            ->willReturnSelf();

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Knp\Menu\ItemInterface $menu */
        $menu = $this->createMock('Knp\Menu\ItemInterface');
        $menu->expects($this->at(0))
            ->method('setExtra')
            ->with('type', 'dropdown');

        $menu->expects($this->at(1))
            ->method('addChild')
            ->willReturn($child);

        $menu->expects($this->at(2))
            ->method('addChild')
            ->with(
                'oro.customer.menu.customer_user_logout.label',
                [
                    'route' => 'oro_customer_customer_user_security_logout',
                    'linkAttributes' => ['class' => 'no-hash']
                ]
            );

        $this->builder->build($menu);
    }
}
