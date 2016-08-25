<?php

namespace Oro\Bundle\RedirectBundle\Test\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\EventListener\ForwardListener;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;

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

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FrontendHelper
     */
    protected $frontendHelper;

    /**
     * @var \Symfony\Component\HttpKernel\HttpKernelInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $kernel;

    protected function setUp()
    {
        $this->router = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->frontendHelper = $this->getMockBuilder('Oro\Bundle\FrontendBundle\Request\FrontendHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
    }

    /**
     * @dataProvider onKernelRequestDataProvider
     * @param boolean $installed
     * @param boolean $env
     * @param string $requestType
     * @param boolean $existingController
     * @param boolean $isFrontendRoute
     * @param array $slugParams
     * @param array $expected
     */
    public function testOnKernelRequest(
        $installed,
        $env,
        $requestType,
        $existingController,
        $isFrontendRoute,
        array $slugParams,
        array $expected
    ) {
        $this->listener = new ForwardListener($this->router, $this->registry, $this->frontendHelper, $installed, $env);
        $this->listener->addSkippedUrlPattern('/deniedRoute', 'dev');
        $request = Request::create('http://localhost'.$slugParams['url']);
        if ($existingController) {
            $request->attributes->add(['_controller' => 'ExistingController']);
        }
        $event = new GetResponseEvent($this->kernel, $request, $requestType);

        $this->frontendHelper->expects($this->any())
            ->method('isFrontendRequest')
            ->with($request)
            ->willReturn($isFrontendRoute);

        $slug = new Slug();
        $slug->setRouteName($slugParams['route_name']);
        $slug->setUrl($slugParams['url']);
        $slug->setRouteParameters($slugParams['route_parameters']);

        if ($requestType === HttpKernelInterface::MASTER_REQUEST) {
            $this->mockSlugRepository($slugParams, $slug);
            $this->mockRouter(
                $slugParams['route_name'],
                $slugParams['route_parameters'],
                $slugParams['url'],
                isset($expected['_controller']) ? $expected['_controller'] : null
            );
        }

        $this->listener->onKernelRequest($event);

        if ($requestType === HttpKernelInterface::MASTER_REQUEST) {
            $parameters = $request->attributes->all();
            $this->assertEquals($expected, $parameters);
        }
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function onKernelRequestDataProvider()
    {
        return [
            'frontend with existing slug' => [
                'installed' => true,
                'env' => 'prod',
                'requestType' => HttpKernelInterface::MASTER_REQUEST,
                'existingController' => false,
                'isFrontendRoute' => true,
                'slugParams' => ['url' => '/', 'route_name' => 'test_route', 'route_parameters' => ['id' => '1']],
                'expected' => [
                    '_route' => 'test_route',
                    '_controller' => 'TestController',
                    'id' => '1',
                    '_route_params' => ['id' => '1']
                ]
            ],
            'frontend with subrequest' => [
                'installed' => true,
                'env' => 'prod',
                'requestType' => HttpKernelInterface::SUB_REQUEST,
                'existingController' => false,
                'isFrontendRoute' => true,
                'slugParams' => ['url' => '/', 'route_name' => 'test_route', 'route_parameters' => ['id' => '1']],
                'expected' => []
            ],
            'frontend with existing controller' => [
                'installed' => true,
                'env' => 'prod',
                'requestType' => HttpKernelInterface::MASTER_REQUEST,
                'existingController' => true,
                'isFrontendRoute' => true,
                'slugParams' => ['url' => '/', 'route_name' => 'test_route', 'route_parameters' => ['id' => '1']],
                'expected' => ['_controller' => 'ExistingController']
            ],
            'frontend with closing slash' => [
                'installed' => true,
                'env' => 'prod',
                'requestType' => HttpKernelInterface::MASTER_REQUEST,
                'existingController' => false,
                'isFrontendRoute' => true,
                'slugParams' => ['url' => '/test/', 'route_name' => 'test_route', 'route_parameters' => ['id' => '1']],
                'expected' => [
                    '_route' => 'test_route',
                    '_controller' => 'TestController',
                    'id' => '1',
                    '_route_params' => ['id' => '1']
                ],
            ],
            'frontend without existing slug' => [
                'installed' => true,
                'env' => 'prod',
                'requestType' => HttpKernelInterface::MASTER_REQUEST,
                'existingController' => false,
                'isFrontendRoute' => true,
                'slugParams' => [
                    'url' => '/missing-slug',
                    'route_name' => 'test_route',
                    'route_parameters' => ['id' => '1']
                ],
                'expected' => [],
            ],
            'frontend not installed application' => [
                'installed' => false,
                'env' => 'prod',
                'requestType' => HttpKernelInterface::MASTER_REQUEST,
                'existingController' => false,
                'isFrontendRoute' => true,
                'slugParams' => ['url' => '/test/', 'route_name' => 'test_route', 'route_parameters' => ['id' => '1']],
                'expected' => [],
            ],
            'backend with existing slug' => [
                'installed' => true,
                'env' => 'prod',
                'requestType' => HttpKernelInterface::MASTER_REQUEST,
                'existingController' => false,
                'isFrontendRoute' => false,
                'slugParams' => ['url' => '/', 'route_name' => 'test_route', 'route_parameters' => ['id' => '1']],
                'expected' => [],
            ],
            'denied route in right env' => [
                'installed' => true,
                'env' => 'dev',
                'requestType' => HttpKernelInterface::MASTER_REQUEST,
                'existingController' => false,
                'isFrontendRoute' => true,
                'slugParams' => [
                    'url' => '/deniedRoute/test',
                    'route_name' => 'test_route',
                    'route_parameters' => ['id' => '1']
                ],
                'expected' => [],
            ],
            'denied route in different env (not affected)' => [
                'installed' => true,
                'env' => 'prod',
                'requestType' => HttpKernelInterface::MASTER_REQUEST,
                'existingController' => false,
                'isFrontendRoute' => true,
                'slugParams' => [
                    'url' => '/deniedRoute/test',
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
        ];
    }

    /**
     * @param array $slugParams
     * @param string $slug
     */
    protected function mockSlugRepository(array $slugParams, $slug)
    {
        $slugRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        if ($slugParams['url'] !== '/') {
            $slugParams['url'] = rtrim($slugParams['url'], '/');
        }

        if ($slugParams['url'] === '/missing-slug') {
            $slugRepository->expects($this->any())
                ->method('findOneBy')
                ->with(['url' => $slugParams['url']])
                ->will($this->returnValue(null));
        } else {
            $slugRepository->expects($this->any())
                ->method('findOneBy')
                ->with(['url' => $slugParams['url']])
                ->will($this->returnValue($slug));
        }

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->any())
            ->method('getRepository')
            ->with('OroRedirectBundle:Slug')
            ->will($this->returnValue($slugRepository));

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroRedirectBundle:Slug')
            ->will($this->returnValue($em));
    }

    /**
     * @param string $routeName
     * @param array $routeParameters
     * @param string $url
     * @param string $controllerName|null
     */
    protected function mockRouter($routeName, array $routeParameters, $url, $controllerName = null)
    {
        $generator = $this->getMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');
        $generator->expects($this->any())
            ->method('generate')
            ->with($routeName, $routeParameters)
            ->willReturn($url);

        $matcher = $this->getMock('Symfony\Component\Routing\Matcher\UrlMatcherInterface');
        $matcher->expects($this->any())
            ->method('match')
            ->with($url)
            ->willReturn($controllerName ? ['_controller' => $controllerName] : []);

        $this->router->expects($this->any())
            ->method('getGenerator')
            ->willReturn($generator);
        $this->router->expects($this->any())
            ->method('getMatcher')
            ->willReturn($matcher);
    }
}
