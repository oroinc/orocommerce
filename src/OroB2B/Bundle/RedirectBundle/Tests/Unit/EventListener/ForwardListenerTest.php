<?php

namespace OroB2B\Bundle\RedirectBundle\Test\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use OroB2B\Bundle\RedirectBundle\Entity\Slug;
use OroB2B\Bundle\RedirectBundle\EventListener\ForwardListener;

class ForwardListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ForwardListener
     */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Router
     */
    protected $router;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    protected function setUp()
    {
        $this->router = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
    }

    /**
     * @dataProvider onKernelRequestDataProvider
     * @param boolean $installed
     * @param string $requestType
     * @param boolean $existingController
     * @param array $slug_params
     * @param array $expected
     */
    public function testOnKernelRequest(
        $installed,
        $requestType,
        $existingController,
        array $slug_params,
        array $expected
    ) {
        $this->listener = new ForwardListener($this->router, $this->registry, $installed);

        /**
         * @var \Symfony\Component\HttpKernel\HttpKernelInterface|\PHPUnit_Framework_MockObject_MockObject $kernel
         */
        $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
        $request = Request::create('http://localhost'. $slug_params['url']);
        if ($existingController) {
            $request->attributes->add(['_controller' => 'ExistingController']);
        }
        $event = new GetResponseEvent($kernel, $request, $requestType);

        $slug = new Slug();
        $slug->setRouteName($slug_params['route_name']);
        $slug->setUrl($slug_params['url']);
        $slug->setRouteParameters($slug_params['route_parameters']);

        if ($requestType === HttpKernelInterface::MASTER_REQUEST) {
            $slugRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
                ->disableOriginalConstructor()
                ->getMock();

            if ($slug_params['url'] !== '/') {
                $slug_params['url'] = rtrim($slug_params['url'], '/');
            }

            if ($slug_params['url'] === '/missing-slug') {
                $slugRepository->expects($this->any())
                    ->method('findOneBy')
                    ->with(['url' => $slug_params['url']])
                    ->will($this->returnValue(null));
            } else {
                $slugRepository->expects($this->any())
                    ->method('findOneBy')
                    ->with(['url' => $slug_params['url']])
                    ->will($this->returnValue($slug));
            }

            $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
                ->disableOriginalConstructor()
                ->getMock();

            $em->expects($this->any())
                ->method('getRepository')
                ->with('OroB2BRedirectBundle:Slug')
                ->will($this->returnValue($slugRepository));

            $this->registry->expects($this->any())
                ->method('getManagerForClass')
                ->with('OroB2BRedirectBundle:Slug')
                ->will($this->returnValue($em));

            $route = $this->getMockBuilder('Symfony\Component\Routing\Route')
                ->disableOriginalConstructor()
                ->getMock();

            $route->expects($this->any())
                ->method('getDefault')
                ->with('_controller')
                ->will($this->returnValue('TestController'));

            $routeCollection = $this->getMock('Symfony\Component\Routing\RouteCollection');

            $routeCollection->expects($this->any())
                ->method('get')
                ->with('test_route')
                ->will($this->returnValue($route));

            $this->router->expects($this->any())
                ->method('getRouteCollection')
                ->will($this->returnValue($routeCollection));
        }

        $this->listener->onKernelRequest($event);

        if ($requestType === HttpKernelInterface::MASTER_REQUEST) {
            $parameters = $request->attributes->all();
            $this->assertEquals($expected, $parameters);
        }
    }

    /**
     * @return array
     */
    public function onKernelRequestDataProvider()
    {
        return [
            'with existing slug' => [
                'installed' => true,
                'requestType' => HttpKernelInterface::MASTER_REQUEST,
                'existingController' => false,
                'slugParams' => [
                    'url' => '/',
                    'route_name' => 'test_route',
                    'route_parameters' => ['id' => '1']
                ],
                'expected' => [
                    '_route' => 'test_route',
                    '_controller' => 'TestController',
                    'id' => '1',
                    '_route_params' => ['id' => '1']
                ]
            ],
            'with subrequest' => [
                'installed' => true,
                'requestType' => HttpKernelInterface::SUB_REQUEST,
                'existingController' => false,
                'slugParams' => [
                    'url' => '/',
                    'route_name' => 'test_route',
                    'route_parameters' => ['id' => '1']
                ],
                'expected' => []
            ],
            'with existing controller' => [
                'installed' => true,
                'requestType' => HttpKernelInterface::MASTER_REQUEST,
                'existingController' => true,
                'slugParams' => [
                    'url' => '/',
                    'route_name' => 'test_route',
                    'route_parameters' => ['id' => '1']
                ],
                'expected' => [
                    '_controller' => 'ExistingController',
                ]
            ],
            'with closing slash' => [
                'installed' => true,
                'requestType' => HttpKernelInterface::MASTER_REQUEST,
                'existingController' => false,
                'slugParams' => [
                    'url' => '/test/',
                    'route_name' => 'test_route',
                    'route_parameters' => ['id' => '1']
                ],
                'expected' => [
                    '_route' => 'test_route',
                    '_controller' => 'TestController',
                    'id' => '1',
                    '_route_params' => ['id' => '1']
                ],
            ],
            'without existing slug' => [
                'installed' => true,
                'requestType' => HttpKernelInterface::MASTER_REQUEST,
                'existingController' => false,
                'slugParams' => [
                    'url' => '/missing-slug',
                    'route_name' => 'test_route',
                    'route_parameters' => ['id' => '1']
                ],
                'expected' => [],
            ],
            'not installed application' => [
                'installed' => false,
                'requestType' => HttpKernelInterface::MASTER_REQUEST,
                'existingController' => false,
                'slugParams' => [
                    'url' => '/test/',
                    'route_name' => 'test_route',
                    'route_parameters' => ['id' => '1']
                ],
                'expected' => [],
            ]
        ];
    }
}
