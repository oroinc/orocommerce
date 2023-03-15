<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\OroWebsiteSearchExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroWebsiteSearchExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroWebsiteSearchExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());

        self::assertEquals('orm:', $container->getParameter('oro_website_search.engine_dsn'));
        self::assertSame([], $container->getParameter('oro_website_search.engine_parameters'));
        self::assertSame(100, $container->getParameter('oro_website_search.indexer_batch_size'));
    }
}
