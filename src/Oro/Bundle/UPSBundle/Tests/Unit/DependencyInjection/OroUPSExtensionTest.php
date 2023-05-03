<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\UPSBundle\DependencyInjection\OroUPSExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroUPSExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroUPSExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
    }
}
