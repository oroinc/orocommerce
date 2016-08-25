<?php

namespace Oro\Bundle\MenuBundle\Tests\Unit\Menu;

use Oro\Bundle\MenuBundle\Menu\MenuSerializer;

class MenuSerializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MenuSerializer
     */
    protected $serializer;

    /**
     * @var \Knp\Menu\Loader\ArrayLoader|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $loader;

    /**
     * @var \Knp\Menu\Util\MenuManipulator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $manipulator;

    public function setUp()
    {
        $this->loader = $this->getMockBuilder('Knp\Menu\Loader\ArrayLoader')
            ->disableOriginalConstructor()
            ->getMock();
        $this->manipulator = $this->getMock('Knp\Menu\Util\MenuManipulator');

        $this->serializer = new MenuSerializer($this->loader, $this->manipulator);
    }

    public function testSerialize()
    {
        $menu = $this->createMenuItemMock();
        $expected = ['title' => 'test'];
        $this->manipulator->expects($this->once())
            ->method('toArray')
            ->with($menu)
            ->willReturn($expected);

        $actual = $this->serializer->serialize($menu);

        $this->assertEquals($expected, $actual);
    }

    public function testDeserialize()
    {
        $data = ['title' => 'test'];
        $expected = $this->createMenuItemMock();
        $this->loader->expects($this->once())
            ->method('load')
            ->with($data)
            ->willReturn($expected);

        $actual = $this->serializer->deserialize($data);

        $this->assertEquals($expected, $actual);
    }

    protected function createMenuItemMock()
    {
        return $this->getMock('Knp\Menu\ItemInterface');
    }
}
