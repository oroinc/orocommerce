<?php

namespace OroB2B\Bundle\MenuBundle\Tests\Unit\Twig;

use OroB2B\Bundle\MenuBundle\Entity\MenuItem;
use OroB2B\Bundle\MenuBundle\JsTree\MenuItemTreeHandler;
use OroB2B\Bundle\MenuBundle\Twig\MenuItemExtension;

class MenuItemExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MenuItemTreeHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $menuItemTreeHandler;

    /**
     * @var MenuItemExtension
     */
    protected $extension;

    public function setUp()
    {
        $this->menuItemTreeHandler = $this->getMockBuilder('OroB2B\Bundle\MenuBundle\JsTree\MenuItemTreeHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new MenuItemExtension($this->menuItemTreeHandler);
    }

    public function testGetName()
    {
        $this->assertEquals(MenuItemExtension::NAME, $this->extension->getName());
    }

    /**
     * Test related method
     */
    public function testGetFunctions()
    {
        $functions = $this->extension->getFunctions();
        $this->assertCount(1, $functions);

        /** @var \Twig_Function_Method $function */
        foreach ($functions as $functionName => $function) {
            $this->assertInstanceOf('\Twig_Function_Method', $function);
            $this->assertEquals('orob2b_menu_item_list', $functionName);
            $this->assertEquals([$this->extension, 'getTree'], $function->getCallable());
        }
    }

    public function testGetTree()
    {
        $tree = [
            [
                'id' => 1,
                'parent' => '#',
                'text' => 'Product List',
                'state' => [
                    'opened' => true,
                ],
            ],
        ];

        $root = 1;

        /** @var MenuItem|\PHPUnit_Framework_MockObject_MockObject $entity */
        $entity = $this->getMock('OroB2B\Bundle\MenuBundle\Entity\MenuItem');
        $entity->expects($this->once())
            ->method('getRoot')
            ->willReturn($root);

        $this->menuItemTreeHandler->expects($this->once())
            ->method('createTree')
            ->with($root)
            ->will($this->returnValue($tree));

        $result = $this->extension->getTree($entity);
        $this->assertEquals($tree, $result);
    }
}
