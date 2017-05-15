<?php

namespace Oro\Bundle\CommerceMenuBundle\Tests\Unit\Twig;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\MatcherInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\CommerceMenuBundle\Twig\MenuExtension;

class MenuExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var MatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $matcher;

    /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject */
    private $requestStack;

    /** @var MenuExtension */
    private $extension;

    public function setUp()
    {
        $this->matcher = $this->getMockBuilder(MatcherInterface::class)->disableOriginalConstructor()->getMock();
        $this->requestStack = $this->getMockBuilder(RequestStack::class)->getMock();
        $this->extension = new MenuExtension($this->matcher);
        $this->extension->setRequestStack($this->requestStack);
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
        $this->assertCount(3, $functions);

        $this->assertArrayHasKey($keyName, $functions);

        /** @var \Twig_SimpleFunction $function */
        $function = $functions[$keyName];

        $this->assertInstanceOf(\Twig_SimpleFunction::class, $function);
        $this->assertEquals([$this->extension, $functionName], $function->getCallable());
    }

    public function testIsCurrent()
    {
        /** @var ItemInterface|\PHPUnit_Framework_MockObject_MockObject $item */
        $item = $this->createMock(ItemInterface::class);

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
        $item = $this->createMock(ItemInterface::class);

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
            ['oro_commercemenu_is_ancestor', 'isAncestor'],
            ['oro_commercemenu_get_url', 'getUrl']
        ];
    }

    /**
     * @dataProvider originalUrlDataProvider
     *
     * @param string $url
     */
    public function testGetUrlOriginal($url)
    {
        $this->requestStack
            ->expects($this->never())
            ->method('getCurrentRequest');

        $this->assertEquals($url, $this->extension->getUrl($url));
    }

    /**
     * @dataProvider preparedUrlDataProvider
     *
     * @param string $url
     * @param string $result
     */
    public function testGetUrlPrepared($url, $result)
    {
        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $request->expects($this->once())
            ->method('getUriForPath')
            ->with($result)
            ->willReturn('http://example.com'. $result);

        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->assertEquals('http://example.com' . $result, $this->extension->getUrl($url));
    }

    /**
     * @return array
     */
    public function originalUrlDataProvider()
    {
        return [
            'tel' => ['tel:123'],
            'skype' => ['skype:+123?call'],
            'skype callto' => ['callto://+123'],
            'mailto' => ['mailto:someone@example.com?Subject=Hello%20again'],
            'with default schema' => ['//example.com'],
            'with default schema and path' => ['//example.com/123'],
            'with "http" schema' => ['http://example.com'],
            'with "http" schema and path' => ['http://example.com/123'],
            'with "http" schema and port' => ['http://example.com:80'],
            'with "http" schema, port and path' => ['http://example.com:80/123'],
        ];
    }

    /**
     * @return array
     */
    public function preparedUrlDataProvider()
    {
        return [
            'without "/"' => [
                'url' => 'help',
                'result' => '/help'
            ],
            'without "/" and with request param' => [
                'url' => 'help?123',
                'result' => '/help?123'
            ],
            'with "/"' => [
                'url' => '/help?123',
                'result' => '/help?123'
            ],
        ];
    }
}
