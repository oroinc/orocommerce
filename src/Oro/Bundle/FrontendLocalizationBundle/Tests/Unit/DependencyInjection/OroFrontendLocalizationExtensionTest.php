<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\FrontendLocalizationBundle\DependencyInjection\OroFrontendLocalizationExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroFrontendLocalizationExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroFrontendLocalizationExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
    }
}
