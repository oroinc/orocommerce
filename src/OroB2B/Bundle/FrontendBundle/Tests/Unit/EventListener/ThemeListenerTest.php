<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Route;

use Oro\Bundle\ThemeBundle\Model\ThemeRegistry;

use OroB2B\Bundle\FrontendBundle\EventListener\RouteCollectionListener;
use OroB2B\Bundle\FrontendBundle\EventListener\ThemeListener;

class ThemeListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RouterInterface
     */
    protected $router;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Route
     */
    protected $route;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ThemeRegistry
     */
    protected $themeRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|HttpKernelInterface
     */
    protected $kernel;

    protected function setUp()
    {
        $this->route = $this->getMockBuilder('Symfony\Component\Routing\Route')
            ->disableOriginalConstructor()
            ->getMock();

        $routeCollection = $this->getMock('Symfony\Component\Routing\RouteCollection');
        $routeCollection->expects($this->any())
            ->method('get')
            ->will($this->returnValue($this->route));

        $this->router = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();
        $this->router->expects($this->any())
            ->method('getRouteCollection')
            ->will($this->returnValue($routeCollection));

        $this->themeRegistry = new ThemeRegistry([
            'oro' => [],
            'demo' => [],
        ]);

        $this->kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
    }

    /**
     * @param boolean $installed
     * @param int $requestType
     * @param string $route
     * @param string $expectedTheme
     *
     * @dataProvider onKernelRequestProvider
     */
    public function testOnKernelRequest(
        $installed,
        $requestType,
        $route,
        $expectedTheme
    ) {
        $this->themeRegistry->setActiveTheme('oro');

        $this->route->expects($this->any())
            ->method('getOption')
            ->with(RouteCollectionListener::OPTION_FRONTEND)
            ->will($this->returnValue($route === RouteCollectionListener::OPTION_FRONTEND));

        $request = new Request([], [], ['_route' => $route]);
        $event = new GetResponseEvent($this->kernel, $request, $requestType);

        $listener = new ThemeListener($this->router, $this->themeRegistry, $installed);
        $listener->onKernelRequest($event);

        $this->assertEquals($expectedTheme, $this->themeRegistry->getActiveTheme()->getName());
    }

    /**
     * @return array
     */
    public function onKernelRequestProvider()
    {
        return [
            'not installed application' => [
                'installed' => false,
                'requestType' => HttpKernelInterface::MASTER_REQUEST,
                'route' => 'frontend',
                'expectedTheme' => 'oro'
            ],
            'not master request' => [
                'installed' => false,
                'requestType' => HttpKernelInterface::SUB_REQUEST,
                'route' => 'frontend',
                'expectedTheme' => 'oro'
            ],
            'frontend' => [
                'installed' => true,
                'requestType' => HttpKernelInterface::MASTER_REQUEST,
                'route' => 'frontend',
                'expectedTheme' => 'demo'
            ],
            'backend' => [
                'installed' => true,
                'requestType' => HttpKernelInterface::MASTER_REQUEST,
                'route' => 'backend',
                'expectedTheme' => 'oro'
            ],
        ];
    }
}
