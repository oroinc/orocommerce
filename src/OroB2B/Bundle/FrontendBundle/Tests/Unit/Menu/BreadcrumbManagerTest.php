<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Unit\Menu;

use Knp\Menu\MenuItem;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

use OroB2B\Bundle\FrontendBundle\Menu\BreadcrumbManager;

class BreadcrumbManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BreadcrumbManager
     */
    protected $manager;

    /**
     * @var \Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $provider;

    /**
     * @var \Knp\Menu\Matcher\Matcher|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $matcher;

    /**
     * @var \Symfony\Component\Routing\Router|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $router;

    /**
     * @var \Knp\Menu\FactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $factory;

    public function setUp()
    {
        $this->matcher = $this->getMockBuilder('Knp\Menu\Matcher\Matcher')
            ->disableOriginalConstructor()
            ->getMock();

        $this->router = $this->getMockBuilder('Symfony\Component\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->factory = $this->getMockBuilder('Knp\Menu\MenuFactory')
            ->setMethods(['getRouteInfo', 'processRoute'])
            ->getMock();

        $routeCollection = new RouteCollection();
        $routeCollection->add('route_without_frontend', new Route('route_without_frontend'));
        $routeCollection->add(
            'route_with_frontend_true',
            (new Route('route_with_frontend_true'))->setOption('frontend', true)
        );
        $routeCollection->add(
            'route_with_frontend_false',
            (new Route('route_with_frontend_false'))->setOption('frontend', false)
        );
        $this->router->expects($this->any())
            ->method('getRouteCollection')
            ->will($this->returnValue($routeCollection));

        $this->manager = new BreadcrumbManager($this->provider, $this->matcher, $this->router);
    }

    /**
     * @dataProvider supportsDataProvider
     * @param Route|string $route
     * @param $expected
     */
    public function testSupports($route, $expected)
    {
        $actual = $this->manager->supports($route);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function supportsDataProvider()
    {
        return [
            [
                'route_without_frontend',
                false
            ],
            [
                'route_with_frontend_false',
                false
            ],
            [
                'route_with_frontend_true',
                true
            ],
            [
                new Route('route_without_frontend'),
                false
            ],
            [
                (new Route('route_with_frontend_false'))->setOption('frontend', false),
                false
            ],
            [
                (new Route('route_with_frontend_true'))->setOption('frontend', true),
                true
            ]
        ];
    }

    /**
     * @dataProvider getBreadcrumbManagerDataProvider
     * @param $expected
     * @param $menu
     * @param $route
     */
    public function testGetBreadcrumbLabels($expected, $menu, $route)
    {
        $this->provider->expects($this->any())
            ->method('get')
            ->will(
                $this->returnCallback(
                    function ($menu) {
                        switch ($menu) {
                            case BreadcrumbManager::FRONTEND_MENU:
                                $item = new MenuItem('frontend_menu__test', $this->factory);
                                $item->setExtra(
                                    'routes',
                                    [
                                        'another_route',
                                        '/another_route/',
                                        'another*route',
                                        'route_with_frontend_true',
                                    ]
                                );
                                $item1 = new MenuItem('frontend_menu__test1', $this->factory);
                                $item2 = new MenuItem('frontend_menu__sub_item', $this->factory);
                                $item1->addChild($item2);
                                $item1->setExtra('routes', []);
                                $item2->addChild($item);

                                return $item1;
                            case 'test_menu':
                                $item = new MenuItem('test_menu__test', $this->factory);
                                $item->setExtra(
                                    'routes',
                                    [
                                        'another_route',
                                        '/another_route/',
                                        'another*route',
                                        'route_without_frontend',
                                    ]
                                );
                                $item1 = new MenuItem('test_menu__test1', $this->factory);
                                $item2 = new MenuItem('test_menu__sub_item', $this->factory);
                                $item1->addChild($item2);
                                $item1->setExtra('routes', []);
                                $item2->addChild($item);

                                return $item1;
                        }

                        return null;
                    }
                )
            );
        $this->assertEquals(
            $expected,
            $this->manager->getBreadcrumbLabels(
                $menu,
                $route
            )
        );
    }

    /**
     * @return array
     */
    public function getBreadcrumbManagerDataProvider()
    {
        return [
            'frontend route' => [
                ['frontend_menu__test', 'frontend_menu__sub_item', 'frontend_menu__test1'],
                'test_menu',
                'route_with_frontend_true'
            ],
            'non frontend route' => [
                ['test_menu__test', 'test_menu__sub_item', 'test_menu__test1'],
                'test_menu',
                'route_without_frontend'
            ]
        ];
    }

    /**
     * @dataProvider isRouteFrontendDataProvider
     * @param $route
     * @param $expected
     */
    public function testIsRouteFrontend(Route $route, $expected)
    {
        $reflectionClass = new \ReflectionClass('\OroB2B\Bundle\FrontendBundle\Menu\BreadcrumbManager');
        $method = $reflectionClass->getMethod('isRouteFrontend');
        $method->setAccessible(true);
        $actual = $method->invoke($this->manager, $route);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function isRouteFrontendDataProvider()
    {
        return [
            'route without frontend option' => [
                new Route('test'),
                false
            ],
            'route with frontend = false' => [
                (new Route('route_with_frontend_false'))->setOption('frontend', false),
                false
            ],
            'route with frontend = true' => [
                (new Route('route_with_frontend_true'))->setOption('frontend', true),
                true
            ]
        ];
    }
}
