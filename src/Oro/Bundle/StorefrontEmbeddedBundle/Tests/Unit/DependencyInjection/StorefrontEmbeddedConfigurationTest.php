<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\StorefrontEmbeddedBundle\DependencyInjection\StorefrontEmbeddedConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

final class StorefrontEmbeddedConfigurationTest extends TestCase
{
    public function testProcessConfigurationDefaultsToNoBlocksWhenNothingIsConfigured(): void
    {
        $processed = (new Processor())->processConfiguration(new StorefrontEmbeddedConfiguration(), []);

        self::assertSame(
            [
                StorefrontEmbeddedConfiguration::BLOCKS => [],
            ],
            $processed
        );
    }

    public function testProcessConfigurationAcceptsAMinimalValidConfiguration(): void
    {
        $config = [
            StorefrontEmbeddedConfiguration::ROOT_NODE => [
                StorefrontEmbeddedConfiguration::BLOCKS => ['embedded_foo' => 'oro_test_foo_route'],
            ],
        ];

        $processed = (new Processor())->processConfiguration(new StorefrontEmbeddedConfiguration(), $config);

        self::assertSame(
            [
                StorefrontEmbeddedConfiguration::BLOCKS => ['embedded_foo' => 'oro_test_foo_route'],
            ],
            $processed
        );
    }

    public function testProcessConfigurationRejectsABlockIdNotMatchingTheEmbeddedPrefixPattern(): void
    {
        $config = [
            StorefrontEmbeddedConfiguration::ROOT_NODE => [
                StorefrontEmbeddedConfiguration::BLOCKS => ['not_an_embedded_block_id' => 'oro_test_foo_route'],
            ],
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/Embedded block ids must match/');

        (new Processor())->processConfiguration(new StorefrontEmbeddedConfiguration(), $config);
    }

    public function testProcessConfigurationRejectsAnEmptyBlockRouteName(): void
    {
        $config = [
            StorefrontEmbeddedConfiguration::ROOT_NODE => [
                StorefrontEmbeddedConfiguration::BLOCKS => ['embedded_foo' => ''],
            ],
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'The path "oro_storefront_embedded.blocks.embedded_foo" cannot contain an empty value, but got "".'
        );

        (new Processor())->processConfiguration(new StorefrontEmbeddedConfiguration(), $config);
    }
}
