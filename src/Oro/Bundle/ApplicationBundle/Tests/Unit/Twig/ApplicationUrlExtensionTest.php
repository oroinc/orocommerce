<?php

namespace Oro\Bundle\ApplicationBundle\Tests\Unit\Twig;

use Oro\Bundle\ApplicationBundle\Twig\ApplicationUrlExtension;

class ApplicationUrlExtensionTest extends \PHPUnit_Framework_TestCase
{
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
        $this->extension = new ApplicationUrlExtension($kernel);
    }

    public function testGetName()
    {
        $this->assertEquals(ApplicationUrlExtension::NAME, $this->extension->getName());
    }

    public function testGetFunctions()
    {
        $functions = $this->extension->getFunctions();
        $this->assertCount(1, $functions);

        /** @var \Twig_SimpleFunction $function */
        $function = $functions[0];
        $this->assertInstanceOf('\Twig_SimpleFunction', $function);
        $this->assertEquals('application_url', $function->getName());
        $this->assertEquals([$this->extension, 'getApplicationUrl'], $function->getCallable());
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
