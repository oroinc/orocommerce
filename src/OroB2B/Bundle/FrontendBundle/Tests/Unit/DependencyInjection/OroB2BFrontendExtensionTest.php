<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Unit\EventListener;

use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use OroB2B\Bundle\FrontendBundle\DependencyInjection\OroB2BFrontendExtension;

class OroB2BFrontendExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testPrepend()
    {
        $inputSecurityConfig = [
            'firewalls' => [
                'frontend' => ['frontend_config'],
                'main' => ['main_config'],
            ]
        ];
        $expectedSecurityConfig = [
            'firewalls' => [
                'frontend' => ['frontend_config'],
                'main' => ['main_config'],
            ]
        ];

        /** @var \PHPUnit_Framework_MockObject_MockObject|ExtendedContainerBuilder $containerBuilder */
        $containerBuilder = $this->getMockBuilder('Oro\Component\DependencyInjection\ExtendedContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $containerBuilder->expects($this->once())
            ->method('getExtensionConfig')
            ->with('security')
            ->willReturn([$inputSecurityConfig]);
        $containerBuilder->expects($this->once())
            ->method('setExtensionConfig')
            ->with('security', [$expectedSecurityConfig]);

        $extension = new OroB2BFrontendExtension();
        $extension->prepend($containerBuilder);
    }
}
