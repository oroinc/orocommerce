<?php

namespace OroB2B\Bundle\MenuBundle\Tests\Unit\Twig;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\MatcherInterface;

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
     * @var MatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $matcher;

    /**
     * @var MenuItemExtension
     */
    protected $extension;

    public function setUp()
    {
        $this->menuItemTreeHandler = $this->getMockBuilder('OroB2B\Bundle\MenuBundle\JsTree\MenuItemTreeHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->matcher = $this->getMockBuilder('Knp\Menu\Matcher\MatcherInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new MenuItemExtension($this->menuItemTreeHandler, $this->matcher);
    }

    public function testGetName()
    {
        $this->assertEquals(MenuItemExtension::NAME, $this->extension->getName());
    }

    /**
     * @dataProvider expectedFunctionsProvider
     *
     * @param string $keyName
     * @param string $functionName
     */
    public function testGetFunctions($keyName, $functionName)
    {
        $functions = $this->extension->getFunctions();
        $this->assertCount(3, $functions);

        $this->assertArrayHasKey($keyName, $functions);

        /** @var \Twig_Function_Method $function */
        $function = $functions[$keyName];

        $this->assertInstanceOf('\Twig_Function_Method', $function);
        $this->assertEquals([$this->extension, $functionName], $function->getCallable());
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

    public function testIsCurrent()
    {
        /** @var ItemInterface|\PHPUnit_Framework_MockObject_MockObject $item */
        $item = $this->getMock('Knp\Menu\ItemInterface');

        $this->matcher
            ->expects($this->once())
            ->method('isCurrent')
            ->with($item)
            ->will($this->returnValue(true));

        $this->assertTrue($this->extension->isCurrent($item));
    }

    public function testIsAncestor()
    {
        /** @var ItemInterface|\PHPUnit_Framework_MockObject_MockObject $item */
        $item = $this->getMock('Knp\Menu\ItemInterface');

        $this->matcher
            ->expects($this->once())
            ->method('isAncestor')
            ->with($item)
            ->will($this->returnValue(true));

        $this->assertTrue($this->extension->isAncestor($item));
    }

    /**
     * @return array
     */
    public function expectedFunctionsProvider()
    {
        return [
            ['orob2b_menu_item_list', 'getTree'],
            ['orob2b_menu_is_current', 'isCurrent'],
            ['orob2b_menu_is_ancestor', 'isAncestor']
        ];
    }
}
