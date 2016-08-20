<?php

namespace Oro\Bundle\MenuBundle\Tests\Unit\Layout\DataProvider;

use Knp\Menu\ItemInterface;
use Knp\Menu\Provider\MenuProviderInterface;

use Oro\Bundle\MenuBundle\Layout\DataProvider\MenuProvider;

class MenuProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var MenuProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $menuProvider;

    /** @var MenuProvider */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->menuProvider = $this->getMock(MenuProviderInterface::class);

        $this->provider = new MenuProvider($this->menuProvider);
    }

    public function testGetMenu()
    {
        $name = 'menu';

        $result = $this->getMock(ItemInterface::class);

        $this->menuProvider
            ->expects($this->once())
            ->method('has')
            ->with($name)
            ->will($this->returnValue(true));

        $this->menuProvider
            ->expects($this->once())
            ->method('get')
            ->with($name)
            ->will($this->returnValue($result));

        $this->assertSame($result, $this->provider->getMenu($name));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetMenuRuntimeException()
    {
        $name = 'menu';

        $this->menuProvider
            ->expects($this->once())
            ->method('has')
            ->with($name)
            ->will($this->returnValue(false));

        $this->provider->getMenu($name);
    }
}
