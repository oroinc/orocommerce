<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Unit;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use OroB2B\Bundle\WebsiteBundle\OroB2BWebsiteBundle;

class OroB2BWebsiteBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        $strategyCompilerClass = 'OroB2B\Bundle\WebsiteBundle\DependencyInjection\CompilerPass\TranslationStrategyPass';
        $twigSandboxConfigurationPass =
            'OroB2B\Bundle\WebsiteBundle\DependencyInjection\CompilerPass\TwigSandboxConfigurationPass';

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

        $bundle = new OroB2BWebsiteBundle();
        $bundle->build($container);
    }
}
