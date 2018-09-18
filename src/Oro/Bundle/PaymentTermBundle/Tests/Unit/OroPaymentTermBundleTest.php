<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit;

use Oro\Bundle\PaymentTermBundle\DependencyInjection\Compiler\ClassMigrationPass;
use Oro\Bundle\PaymentTermBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;
use Oro\Bundle\PaymentTermBundle\OroPaymentTermBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroPaymentTermBundleTest extends \PHPUnit\Framework\TestCase
{
    public function testBuild()
    {
        /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject $containerBuilder */
        $containerBuilder = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['addCompilerPass'])
            ->getMock();

        $containerBuilder->expects($this->exactly(2))
            ->method('addCompilerPass')
            ->withConsecutive(
                [$this->isInstanceOf(ClassMigrationPass::class)],
                [$this->isInstanceOf(TwigSandboxConfigurationPass::class)]
            )
            ->willReturn($containerBuilder);

        $bundle = new OroPaymentTermBundle();
        $bundle->build($containerBuilder);
    }
}
