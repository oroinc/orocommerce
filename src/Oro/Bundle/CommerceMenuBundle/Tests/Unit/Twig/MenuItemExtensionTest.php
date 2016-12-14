<?php

namespace Oro\Bundle\CommerceMenuBundle\Tests\Unit\Twig;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\MatcherInterface;

use Oro\Bundle\CommerceMenuBundle\Twig\MenuExtension;

class MenuItemExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var MatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $matcher;

    /** @var MenuExtension */
    private $extension;

    public function setUp()
    {
        $this->matcher = $this->getMockBuilder(MatcherInterface::class)->disableOriginalConstructor()->getMock();
        $this->extension = new MenuExtension($this->matcher);
    }

    public function testGetName()
    {
        $this->assertEquals(MenuExtension::NAME, $this->extension->getName());
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
        $this->assertCount(2, $functions);

        $this->assertArrayHasKey($keyName, $functions);

        /** @var \Twig_SimpleFunction $function */
        $function = $functions[$keyName];

        $this->assertInstanceOf(\Twig_SimpleFunction::class, $function);
        $this->assertEquals([$this->extension, $functionName], $function->getCallable());
    }

    public function testIsCurrent()
    {
        /** @var ItemInterface|\PHPUnit_Framework_MockObject_MockObject $item */
        $item = $this->getMock(ItemInterface::class);

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
        $item = $this->getMock(ItemInterface::class);

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
            ['oro_commercemenu_is_current', 'isCurrent'],
            ['oro_commercemenu_is_ancestor', 'isAncestor']
        ];
    }
}
