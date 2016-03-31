<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\LocaleBundle\DependencyInjection\OroLocaleExtension;

use OroB2B\Bundle\FrontendBundle\DependencyInjection\OroB2BFrontendExtension;

class OroB2BFrontendExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testLoad()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ContainerBuilder $container */
        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->once())
            ->method('prependExtensionConfig')
            ->with(OroB2BFrontendExtension::ALIAS, $this->isType('array'));

        $container->expects($this->once())
            ->method('getParameter')
            ->with(OroLocaleExtension::PARAMETER_ADDRESS_FORMATS)
            ->willReturn([]);

        $extension = new OroB2BFrontendExtension();
        $extension->load([], $container);
    }

    public function testGetAlias()
    {
        $extension = new OroB2BFrontendExtension();

        $this->assertEquals(OroB2BFrontendExtension::ALIAS, $extension->getAlias());
    }
}
