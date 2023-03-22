<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\PaymentBundle\DependencyInjection\OroPaymentExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroPaymentExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroPaymentExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'merchant_country' => ['value' => 'US', 'scope' => 'app']
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_payment')
        );
    }
}
