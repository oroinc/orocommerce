<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\FedexShippingBundle\DependencyInjection\OroFedexShippingExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroFedexShippingExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroFedexShippingExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
    }
}
