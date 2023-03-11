<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\PaymentTermBundle\DependencyInjection\OroPaymentTermExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroPaymentTermExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroPaymentTermExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
    }
}
