<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\PaymentBundle\DependencyInjection\Compiler\PaymentGuestAccessUrlPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PaymentGuestAccessUrlPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();
        $urlProviderDef = $container->register('oro_frontend.guest_access.provider.guest_access_urls_provider');

        $compiler = new PaymentGuestAccessUrlPass();
        $compiler->process($container);

        self::assertEquals(
            [
                ['addAllowedUrlPattern', ['^/payment/callback/notify/']]
            ],
            $urlProviderDef->getMethodCalls()
        );
    }
}
