<?php

namespace Oro\Bundle\WebsiteBundle\Tests\Unit;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\WebsiteBundle\OroWebsiteBundle;

class OroWebsiteBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        $strategyCompilerClass = 'Oro\Bundle\WebsiteBundle\DependencyInjection\CompilerPass\TranslationStrategyPass';
        $twigSandboxConfigurationPass =
            'Oro\Bundle\WebsiteBundle\DependencyInjection\CompilerPass\TwigSandboxConfigurationPass';

        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->at(0))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf($strategyCompilerClass))
            ->willReturn(false);
        $container->expects($this->at(1))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf($twigSandboxConfigurationPass))
            ->willReturn(false);

        $bundle = new OroWebsiteBundle();
        $bundle->build($container);
    }
}
