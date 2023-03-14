<?php

namespace Oro\Bundle\FallbackBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\FallbackBundle\DependencyInjection\OroFallbackExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroFallbackExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroFallbackExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
    }
}
