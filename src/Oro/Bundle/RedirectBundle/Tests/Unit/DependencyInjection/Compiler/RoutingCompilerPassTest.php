<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\RedirectBundle\DependencyInjection\Compiler\RoutingCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class RoutingCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $compiler = new RoutingCompilerPass();

        $originalMatcherReference = $this->getMockBuilder(Reference::class)
            ->disableOriginalConstructor()
            ->getMock();

        $routerDefinition = $this->getMockBuilder(Definition::class)
            ->disableOriginalConstructor()
            ->getMock();
        $routerDefinition->expects($this->once())
            ->method('getArgument')
            ->with(0)
            ->willReturn($originalMatcherReference);

        $urlMatcherDefinition = $this->getMockBuilder(Definition::class)
            ->disableOriginalConstructor()
            ->getMock();
        $urlMatcherDefinition->expects($this->once())
            ->method('replaceArgument')
            ->with(0, $originalMatcherReference);

        $routerDefinition->expects($this->once())
            ->method('replaceArgument')
            ->with(0, $urlMatcherDefinition);

        $container = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->exactly(2))
            ->method('getDefinition')
            ->withConsecutive(
                ['router_listener'],
                ['oro_redirect.routing.slug_url_mathcer']
            )
            ->willReturnOnConsecutiveCalls(
                $routerDefinition,
                $urlMatcherDefinition
            );

        $compiler->process($container);
    }
}
