<?php

namespace Oro\Bundle\FrontendBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\LocaleBundle\DependencyInjection\OroLocaleExtension;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Oro\Bundle\FrontendBundle\DependencyInjection\OroFrontendExtension;

class OroFrontendExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testLoad()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ContainerBuilder $container */
        $container = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->once())
            ->method('prependExtensionConfig')
            ->with(OroFrontendExtension::ALIAS, $this->isType('array'));

        $container->expects($this->once())
            ->method('getParameter')
            ->with(OroLocaleExtension::PARAMETER_ADDRESS_FORMATS)
            ->willReturn([]);

        $extension = new OroFrontendExtension();
        $extension->load([], $container);
    }

    public function testGetAlias()
    {
        $extension = new OroFrontendExtension();

        $this->assertEquals(OroFrontendExtension::ALIAS, $extension->getAlias());
    }

    public function testPrepend()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ExtendedContainerBuilder $container */
        $container = $this->getMockBuilder(ExtendedContainerBuilder::class)->disableOriginalConstructor()->getMock();
        $configs = [
            [
                'view' => [],
            ],
            [
                'view' => [],
                'format_listener' => [],
            ],
            [
                'view' => [],
                'format_listener' => [
                    'rules' => [],
                ],
            ],            [
                'format_listener' => [
                    'rules' => [
                        ['path' => '^/api/(?!(soap|rest|doc)(/|$)+)'],
                        ['path' => '^/api/rest'],
                    ],
                ],
            ],
            [
                'view' => [],
                'format_listener' => [
                    'rules' => [
                        ['path' => '^/api/soap'],
                        [],
                    ],
                ],
            ],
        ];
        $expected = $configs;
        $expected[3]['format_listener']['rules'][0]['path'] = '^/admin/api/(?!(soap|rest|doc)(/|$)+)';

        $container->expects($this->once())->method('getExtensionConfig')->with('fos_rest')->willReturn($configs);
        $container->expects($this->once())->method('getParameter')->with('web_backend_prefix')->willReturn('/admin');
        $container->expects($this->once())->method('setExtensionConfig')->with('fos_rest', $expected);

        $extension = new OroFrontendExtension();
        $extension->prepend($container);
    }
}
