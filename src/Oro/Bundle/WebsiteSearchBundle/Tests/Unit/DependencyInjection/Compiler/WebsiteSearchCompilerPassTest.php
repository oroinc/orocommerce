<?php
namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\Compiler\WebsiteSearchCompilerPass;

class WebsiteSearchCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $containerBuilder
     */
    private $containerBuilder;

    protected function setUp()
    {
        $this->containerBuilder = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function runCompilerPass()
    {
        $pass = new WebsiteSearchCompilerPass();
        $pass->process($this->containerBuilder);
    }

    public function testProcessWhenPlaceholderRegistryDefinitionNotExists()
    {
        $this->containerBuilder
            ->expects($this->once())
            ->method('has')
            ->with(WebsiteSearchCompilerPass::WEBSITE_SEARCH_PLACEHOLDER_REGISTRY)
            ->willReturn(false);

        $this->containerBuilder
            ->expects($this->never())
            ->method('findTaggedServiceIds');

        $this->containerBuilder
            ->expects($this->never())
            ->method('addMethodCall');

        $this->runCompilerPass();
    }

    public function testProcessWhenPlaceholderRegistryDefinitionExists()
    {
        $this->containerBuilder
            ->expects($this->once())
            ->method('has')
            ->with(WebsiteSearchCompilerPass::WEBSITE_SEARCH_PLACEHOLDER_REGISTRY)
            ->willReturn(true);

        $placeholderRegistryDefinition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();

        $this->containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with(WebsiteSearchCompilerPass::WEBSITE_SEARCH_PLACEHOLDER_REGISTRY)
            ->will($this->returnValue($placeholderRegistryDefinition));

        $services = ['LocalizationIdPlaceholder' => []];

        $this->containerBuilder->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(WebsiteSearchCompilerPass::WEBSITE_SEARCH_PLACEHOLDER_TAG)
            ->willReturn($services);

        $placeholderRegistryDefinition->expects($this->once())
            ->method('addMethodCall')
            ->with('addPlaceholder', [new Reference('LocalizationIdPlaceholder')]);

        $this->runCompilerPass();
    }
}
