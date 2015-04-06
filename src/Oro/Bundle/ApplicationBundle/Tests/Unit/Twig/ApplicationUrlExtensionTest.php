<?php

namespace Oro\Bundle\ApplicationBundle\Tests\Unit\Twig;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Bundle\ApplicationBundle\Twig\ApplicationUrlExtension;

class ApplicationUrlExtensionTest extends \PHPUnit_Framework_TestCase
{
    const TEST_APPLICATION = 'test';
    const TEST_APPLICATION_HOST = 'http://localhost/';
    /**
     * @var ApplicationUrlExtension
     */
    protected $extension;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->extension = new ApplicationUrlExtension(
            self::TEST_APPLICATION,
            [self::TEST_APPLICATION => self::TEST_APPLICATION_HOST]
        );
    }

    public function testGetName()
    {
        $this->assertEquals(ApplicationUrlExtension::NAME, $this->extension->getName());
    }

    public function testGetHost()
    {
        $this->assertEquals(self::TEST_APPLICATION_HOST, $this->extension->getHost(self::TEST_APPLICATION));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The name of the application is not a valid. Allowed the following names: test.
     */
    public function testGetHostException()
    {
        $this->extension->getHost('not_existing_application');
    }

    public function testGetApplicationUrl()
    {
        $routerContext = $this->getMockBuilder('Symfony\Component\Routing\RequestContext')
            ->disableOriginalConstructor()
            ->getMock();
        $routerContext->expects($this->once())
            ->method('fromRequest');
        $routerContext->expects($this->once())
            ->method('getPathInfo');
        $routerContext->expects($this->once())
            ->method('setBaseUrl');
        $routerContext->expects($this->once())
            ->method('setPathInfo')
            ->with('');

        $applicationUrl = 'http://localhost/test.php/route/data';

        $router = $this->getMockBuilder('Symfony\Component\Routing\RouterInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $router->expects($this->once())
            ->method('getContext')
            ->will($this->returnValue($routerContext));
        $router->expects($this->once())
            ->method('generate')
            ->with('test', ['key' => 'value'], UrlGeneratorInterface::ABSOLUTE_URL)
            ->will($this->returnValue($applicationUrl));

        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->once())
            ->method('get')
            ->with('router')
            ->will($this->returnValue($router));

        $kernel = $this->getMockBuilder('Symfony\Component\HttpKernel\KernelInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $kernel->expects($this->once())
            ->method('getContainer')
            ->will($this->returnValue($container));

        /** @var \PHPUnit_Framework_MockObject_MockObject|ApplicationUrlExtension $extension */
        $extension = $this->getMock(
            'Oro\Bundle\ApplicationBundle\Twig\ApplicationUrlExtension',
            ['getKernel'],
            ['test', ['test' => 'http://localhost/test.php']]
        );
        $extension->expects($this->once())
            ->method('getKernel')
            ->will($this->returnValue($kernel));

        $this->assertEquals(
            $applicationUrl,
            $extension->getApplicationUrl('test', ['application' => 'test', 'key' => 'value'])
        );
    }

    public function testGetFunctions()
    {
        $expectedFunctions = [
            ['application_url', 'getApplicationUrl'],
            ['application_host', 'getHost']
        ];

        /** @var \Twig_SimpleFunction[] $functions */
        $functions = $this->extension->getFunctions();

        $this->assertSameSize($expectedFunctions, $functions);

        foreach ($functions as $twigFunction) {
            $expectedFunction = current($expectedFunctions);

            $this->assertInstanceOf('\Twig_SimpleFunction', $twigFunction);
            $this->assertEquals($expectedFunction[0], $twigFunction->getName());
            $this->assertEquals([$this->extension, $expectedFunction[1]], $twigFunction->getCallable());

            next($expectedFunctions);
        }
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Parameters must have required element `application`.
     */
    public function testGetApplicationUrlWithEmptyParameters()
    {
        $this->extension->getApplicationUrl('test', []);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The name of the application is not a valid.
     *                           Allowed the following names: application_host.test.
     */
    public function testGetHostByNotValidApplicationName()
    {
        $class = new \ReflectionClass(get_class($this->extension));
        $method = $class->getMethod('getHost');
        $method->setAccessible(true);
        $method->invokeArgs($this->extension, ['failed']);
    }
}
