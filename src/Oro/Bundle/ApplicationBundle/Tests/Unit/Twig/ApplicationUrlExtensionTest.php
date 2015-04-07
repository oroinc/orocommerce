<?php

namespace Oro\Bundle\ApplicationBundle\Tests\Unit\Twig;

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
        $kernel = $this->getMock('Symfony\Component\HttpKernel\KernelInterface');
        $this->extension = new ApplicationUrlExtension($kernel, [self::TEST_APPLICATION => self::TEST_APPLICATION_HOST]);
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
}
