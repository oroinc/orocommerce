<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\StorefrontEmbeddedBundle\DependencyInjection\StorefrontEmbeddedConfiguration;
use Oro\Bundle\StorefrontEmbeddedBundle\DependencyInjection\StorefrontEmbeddedConfigurationLoader;
use Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Stub\StorefrontEmbeddedTestBundleFour\StorefrontEmbeddedTestBundleFour;
use Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Stub\StorefrontEmbeddedTestBundleOne\StorefrontEmbeddedTestBundleOne;
use Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Stub\StorefrontEmbeddedTestBundleThree\StorefrontEmbeddedTestBundleThree;
use Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Stub\StorefrontEmbeddedTestBundleTwo\StorefrontEmbeddedTestBundleTwo;
use Oro\Component\Config\CumulativeResourceManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class StorefrontEmbeddedConfigurationLoaderTest extends TestCase
{
    private StorefrontEmbeddedConfigurationLoader $loader;

    #[\Override]
    protected function setUp(): void
    {
        $this->loader = new StorefrontEmbeddedConfigurationLoader();
    }

    #[\Override]
    protected function tearDown(): void
    {
        CumulativeResourceManager::getInstance()->clear();
    }

    public function testLoadMergesAndDeduplicatesRoutesTwoBundlesContributeForTheSameBlock(): void
    {
        $bundleOne = new StorefrontEmbeddedTestBundleOne();
        $bundleTwo = new StorefrontEmbeddedTestBundleTwo();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundleOne->getName() => get_class($bundleOne),
                $bundleTwo->getName() => get_class($bundleTwo),
            ]);

        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $config = $this->loader->load($container);

        self::assertSame(['embedded_shared_block' => 'oro_test_shared_route'], $config[StorefrontEmbeddedConfiguration::BLOCKS]);
        self::assertSame(
            ['embedded_shared_block' => ['oro_test_proxy_route_a', 'oro_test_proxy_route_b']],
            $config[StorefrontEmbeddedConfiguration::PROXIED_ROUTES]
        );
        self::assertSame(
            ['any_block' => ['oro_test_file_route_a', 'oro_test_file_route_b']],
            $config[StorefrontEmbeddedConfiguration::FILE_ROUTES]
        );
    }

    public function testLoadThrowsLogicExceptionWhenTwoBundlesMapTheSameBlockIdToDifferentRoutes(): void
    {
        $bundleThree = new StorefrontEmbeddedTestBundleThree();
        $bundleFour = new StorefrontEmbeddedTestBundleFour();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundleThree->getName() => get_class($bundleThree),
                $bundleFour->getName() => get_class($bundleFour),
            ]);

        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches(
            '/(?=.*embedded_conflicting_block)(?=.*oro_test_conflict_route_one)(?=.*oro_test_conflict_route_two)/s'
        );

        $this->loader->load($container);
    }
}
