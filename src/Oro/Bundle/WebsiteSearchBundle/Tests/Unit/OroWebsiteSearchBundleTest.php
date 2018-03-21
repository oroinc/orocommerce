<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit;

use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\Compiler\WebsiteSearchCompilerPass;
use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\Compiler\WebsiteSearchTypeProviderCompilerPass;
use Oro\Bundle\WebsiteSearchBundle\OroWebsiteSearchBundle;
use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\OroWebsiteSearchExtension;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroWebsiteSearchBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testGetContainerExtension()
    {
        $bundle = new OroWebsiteSearchBundle();

        $this->assertInstanceOf(OroWebsiteSearchExtension::class, $bundle->getContainerExtension());
    }

    public function testBuild()
    {
        $containerBuilder = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $websiteSearchCompilerPass = new WebsiteSearchCompilerPass();
        $websiteSearchTypeProviderCompilerPass = new WebsiteSearchTypeProviderCompilerPass();

        $containerBuilder
            ->expects($this->at(0))
            ->method('addCompilerPass')
            ->with($websiteSearchCompilerPass);

        $containerBuilder
            ->expects($this->at(1))
            ->method('addCompilerPass')
            ->with($websiteSearchTypeProviderCompilerPass);

        $bundle = new OroWebsiteSearchBundle();

        $bundle->build($containerBuilder);
    }
}
