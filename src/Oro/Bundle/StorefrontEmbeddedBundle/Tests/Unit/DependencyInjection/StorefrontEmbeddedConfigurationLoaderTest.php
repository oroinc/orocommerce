<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\DependencyInjection;

// phpcs:disable
use Oro\Bundle\StorefrontEmbeddedBundle\DependencyInjection\StorefrontEmbeddedConfiguration;
use Oro\Bundle\StorefrontEmbeddedBundle\DependencyInjection\StorefrontEmbeddedConfigurationLoader;
use Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Stub\StorefrontEmbeddedTestBundleOne\StorefrontEmbeddedTestBundleOne;
use Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Stub\StorefrontEmbeddedTestBundleThree\StorefrontEmbeddedTestBundleThree;
use Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Stub\StorefrontEmbeddedTestBundleTwo\StorefrontEmbeddedTestBundleTwo;
use Oro\Component\Config\CumulativeResourceManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

// phpcs:enable

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

    public function testLoadResetsLayoutBlockIdsToDefaultWhenLastBundleUsesShortForm(): void
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

        self::assertSame(
            [
                'embedded_shared_block' => [
                    'route_name' => 'oro_test_shared_route',
                    'layout_block_ids' => ['embedded_shared_block'],
                ],
            ],
            $config[StorefrontEmbeddedConfiguration::FRAGMENTS]
        );
    }

    public function testLoadTakesWholeFragmentDefinitionFromLastBundle(): void
    {
        $bundleOne = new StorefrontEmbeddedTestBundleOne();
        $bundleThree = new StorefrontEmbeddedTestBundleThree();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundleOne->getName() => get_class($bundleOne),
                $bundleThree->getName() => get_class($bundleThree),
            ]);

        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $config = $this->loader->load($container);

        self::assertSame(
            [
                'embedded_shared_block' => [
                    'route_name' => 'oro_test_override_route',
                    'layout_block_ids' => ['embedded_override_block', 'embedded_override_sidebar'],
                ],
            ],
            $config[StorefrontEmbeddedConfiguration::FRAGMENTS]
        );
    }
}
