<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Unit\Placeholder;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

use OroB2B\Bundle\FrontendBundle\EventListener\RouteCollectionListener;
use OroB2B\Bundle\FrontendBundle\Placeholder\FrontendFilter;

class FrontendFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RouterInterface
     */
    protected $router;

    /**
     * @var FrontendFilter
     */
    protected $filter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->router = $this->getMock('Symfony\Component\Routing\RouterInterface');

        $this->filter = new FrontendFilter($this->router);
    }

    public function testNoRequestBehaviour()
    {
        $this->assertTrue($this->filter->isBackendRoute());
        $this->assertFalse($this->filter->isFrontendRoute());
    }

    /**
     * @param bool $isBackend
     * @param bool $isFrontend
     * @param string|null $routeName
     * @param Route|null $route
     * @dataProvider isBackendIsFrontendDataProvider
     */
    public function testIsBackendIsFrontend($isBackend, $isFrontend, $routeName = null, Route $route = null)
    {
        $request = new Request();
        if ($routeName) {
            $request->attributes->set('_route', $routeName);
        }

        $routeCollection = new RouteCollection();
        if ($routeName && $route) {
            $routeCollection->add($routeName, $route);
        }

        $this->router->expects($this->any())
            ->method('getRouteCollection')
            ->willReturn($routeCollection);

        $this->filter->setRequest($request);

        $this->assertSame($isBackend, $this->filter->isBackendRoute());
        $this->assertSame($isFrontend, $this->filter->isFrontendRoute());
    }

    /**
     * @return array
     */
    public function isBackendIsFrontendDataProvider()
    {
        return [
            'no route attribute' => [
                'isBackend' => true,
                'isFrontend' => false,
            ],
            'no route' => [
                'isBackend' => true,
                'isFrontend' => false,
                'routeName' => 'random_route',
            ],
            'backend route' => [
                'isBackend' => true,
                'isFrontend' => false,
                'routeName' => 'backend_route',
                'route' => new Route('/admin'),
            ],
            'frontend route' => [
                'isBackend' => false,
                'isFrontend' => true,
                'routeName' => 'frontend_route',
                'route' => new Route('/', [], [], [RouteCollectionListener::OPTION_FRONTEND => true]),
            ],
        ];
    }
}
