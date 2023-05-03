<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ValidationBundle\DependencyInjection\OroValidationExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroValidationExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroValidationExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
    }
}
