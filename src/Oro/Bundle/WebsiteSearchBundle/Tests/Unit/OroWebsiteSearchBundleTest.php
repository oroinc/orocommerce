<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit;

use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\Compiler\WebsiteSearchCompilerPass;
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

        $containerBuilder
            ->expects($this->once())
            ->method('addCompilerPass')
            ->with($websiteSearchCompilerPass);

        $bundle = new OroWebsiteSearchBundle();

        $bundle->build($containerBuilder);
    }
}
