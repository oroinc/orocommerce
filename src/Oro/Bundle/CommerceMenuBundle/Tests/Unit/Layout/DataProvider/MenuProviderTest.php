<?php

namespace Oro\Bundle\CommerceMenuBundle\Tests\Unit\Layout\DataProvider;

use Knp\Menu\ItemInterface;
use Knp\Menu\Provider\MenuProviderInterface;

use Oro\Bundle\CommerceMenuBundle\Layout\DataProvider\MenuProvider;

class MenuProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var MenuProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $builderChainProvider;

    /** @var MenuProvider */
    private $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->builderChainProvider = $this->getMockBuilder(MenuProviderInterface::class)->getMock();
        $this->provider = new MenuProvider($this->builderChainProvider);
    }

    public function testGetMenu()
    {
        $menuName = 'menuName';
        $options = ['option1', 'option2'];

        /** @var ItemInterface|\PHPUnit_Framework_MockObject_MockObject $item */
        $item = $this->getMockBuilder(ItemInterface::class)->getMock();

        $this->builderChainProvider
            ->expects($this->once())
            ->method('get')
            ->with($menuName, $options)
            ->willReturn($item);

        $this->assertSame($item, $this->provider->getMenu($menuName, $options));
    }

    public function testGetMenuWithDefaultOptions()
    {
        $menuName = 'menuName';
        $options = ['check_access' => false];

        /** @var ItemInterface|\PHPUnit_Framework_MockObject_MockObject $item */
        $item = $this->getMockBuilder(ItemInterface::class)->getMock();

        $this->builderChainProvider
            ->expects($this->once())
            ->method('get')
            ->with($menuName, $options)
            ->willReturn($item);

        $this->assertSame($item, $this->provider->getMenu($menuName));
    }
}
