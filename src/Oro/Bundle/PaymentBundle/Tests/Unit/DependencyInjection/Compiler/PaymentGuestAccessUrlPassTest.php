<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\PaymentBundle\DependencyInjection\Compiler\PaymentGuestAccessUrlPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class PaymentGuestAccessUrlPassTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PaymentGuestAccessUrlPass
     */
    private $compilerPass;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->compilerPass = new PaymentGuestAccessUrlPass();
    }

    public function testProcess()
    {
        /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject $containerBuilder */
        $containerBuilder = $this->createMock(ContainerBuilder::class);
        $definition = new Definition();
        $containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with(PaymentGuestAccessUrlPass::URL_PROVIDER)
            ->willReturn($definition);

        $this->compilerPass->process($containerBuilder);
        self::assertCount(1, $definition->getMethodCalls());
        self::assertEquals(['addAllowedUrlPattern', ['^/payment/callback/notify/']], $definition->getMethodCalls()[0]);
    }
}
