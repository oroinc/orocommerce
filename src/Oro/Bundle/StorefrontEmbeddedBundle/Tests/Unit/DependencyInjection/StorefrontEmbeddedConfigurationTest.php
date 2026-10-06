<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\StorefrontEmbeddedBundle\DependencyInjection\StorefrontEmbeddedConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

final class StorefrontEmbeddedConfigurationTest extends TestCase
{
    public function testProcessConfigurationDefaultsToNoFragmentsWhenNothingIsConfigured(): void
    {
        $processed = (new Processor())->processConfiguration(new StorefrontEmbeddedConfiguration(), []);

        self::assertSame(
            [
                StorefrontEmbeddedConfiguration::FRAGMENTS => [],
            ],
            $processed
        );
    }

    public function testProcessConfigurationExpandsShortFormWithFragmentNameAsLayoutBlockId(): void
    {
        $config = [
            StorefrontEmbeddedConfiguration::ROOT_NODE => [
                StorefrontEmbeddedConfiguration::FRAGMENTS => ['embedded_foo' => 'oro_test_foo_route'],
            ],
        ];

        $processed = (new Processor())->processConfiguration(new StorefrontEmbeddedConfiguration(), $config);

        self::assertSame(
            [
                StorefrontEmbeddedConfiguration::FRAGMENTS => [
                    'embedded_foo' => ['route_name' => 'oro_test_foo_route', 'layout_block_ids' => ['embedded_foo']],
                ],
            ],
            $processed
        );
    }

    public function testProcessConfigurationKeepsExpandedForm(): void
    {
        $config = [
            StorefrontEmbeddedConfiguration::ROOT_NODE => [
                StorefrontEmbeddedConfiguration::FRAGMENTS => [
                    'embedded_foo' => [
                        'route_name' => 'oro_test_foo_route',
                        'layout_block_ids' => ['foo_block', 'bar_block'],
                    ],
                ],
            ],
        ];

        $processed = (new Processor())->processConfiguration(new StorefrontEmbeddedConfiguration(), $config);

        self::assertSame(
            [
                StorefrontEmbeddedConfiguration::FRAGMENTS => [
                    'embedded_foo' => [
                        'route_name' => 'oro_test_foo_route',
                        'layout_block_ids' => ['foo_block', 'bar_block'],
                    ],
                ],
            ],
            $processed
        );
    }

    /**
     * @dataProvider processConfigurationDefaultsLayoutBlockIdsToFragmentNameProvider
     *
     * @param array<string, mixed> $fragment
     */
    public function testProcessConfigurationDefaultsLayoutBlockIdsToFragmentName(array $fragment): void
    {
        $config = [
            StorefrontEmbeddedConfiguration::ROOT_NODE => [
                StorefrontEmbeddedConfiguration::FRAGMENTS => ['embedded_foo' => $fragment],
            ],
        ];

        $processed = (new Processor())->processConfiguration(new StorefrontEmbeddedConfiguration(), $config);

        self::assertSame(
            [
                StorefrontEmbeddedConfiguration::FRAGMENTS => [
                    'embedded_foo' => ['route_name' => 'oro_test_foo_route', 'layout_block_ids' => ['embedded_foo']],
                ],
            ],
            $processed
        );
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function processConfigurationDefaultsLayoutBlockIdsToFragmentNameProvider(): iterable
    {
        yield 'missing layout block ids' => [['route_name' => 'oro_test_foo_route']];
        yield 'empty layout block ids' => [['route_name' => 'oro_test_foo_route', 'layout_block_ids' => []]];
    }

    /**
     * @dataProvider processConfigurationRejectsInvalidFragmentNameProvider
     */
    public function testProcessConfigurationRejectsInvalidFragmentName(string $fragmentName): void
    {
        $config = [
            StorefrontEmbeddedConfiguration::ROOT_NODE => [
                StorefrontEmbeddedConfiguration::FRAGMENTS => [$fragmentName => 'oro_test_foo_route'],
            ],
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Embedded fragment names must match "/^embedded_\\w+$/D".');

        (new Processor())->processConfiguration(new StorefrontEmbeddedConfiguration(), $config);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function processConfigurationRejectsInvalidFragmentNameProvider(): iterable
    {
        yield 'no prefix' => ['not_an_embedded_fragment'];
        yield 'prefix only' => ['embedded_'];
        yield 'slash' => ['embedded_foo/bar'];
        yield 'newline' => ["embedded_foo\n"];
    }

    public function testProcessConfigurationRejectsEmptyRouteName(): void
    {
        $config = [
            StorefrontEmbeddedConfiguration::ROOT_NODE => [
                StorefrontEmbeddedConfiguration::FRAGMENTS => ['embedded_foo' => ''],
            ],
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'The path "oro_storefront_embedded.fragments.embedded_foo.route_name" cannot contain an empty value,'
            . ' but got "".'
        );

        (new Processor())->processConfiguration(new StorefrontEmbeddedConfiguration(), $config);
    }

    public function testProcessConfigurationRejectsEmptyLayoutBlockId(): void
    {
        $config = [
            StorefrontEmbeddedConfiguration::ROOT_NODE => [
                StorefrontEmbeddedConfiguration::FRAGMENTS => [
                    'embedded_foo' => ['route_name' => 'oro_test_foo_route', 'layout_block_ids' => ['']],
                ],
            ],
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'The path "oro_storefront_embedded.fragments.embedded_foo.layout_block_ids.0" cannot contain an empty'
            . ' value, but got "".'
        );

        (new Processor())->processConfiguration(new StorefrontEmbeddedConfiguration(), $config);
    }

    public function testProcessConfigurationRejectsExpandedFragmentWithoutRouteName(): void
    {
        $config = [
            StorefrontEmbeddedConfiguration::ROOT_NODE => [
                StorefrontEmbeddedConfiguration::FRAGMENTS => [
                    'embedded_foo' => ['layout_block_ids' => ['foo_block']],
                ],
            ],
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'The child config "route_name" under "oro_storefront_embedded.fragments.embedded_foo" must be configured'
        );

        (new Processor())->processConfiguration(new StorefrontEmbeddedConfiguration(), $config);
    }

    public function testProcessConfigurationRejectsLayoutBlockIdsThatAreNotList(): void
    {
        $config = [
            StorefrontEmbeddedConfiguration::ROOT_NODE => [
                StorefrontEmbeddedConfiguration::FRAGMENTS => [
                    'embedded_foo' => ['route_name' => 'oro_test_foo_route', 'layout_block_ids' => 'foo_block'],
                ],
            ],
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'Invalid type for path "oro_storefront_embedded.fragments.embedded_foo.layout_block_ids".'
            . ' Expected "array", but got "string"'
        );

        (new Processor())->processConfiguration(new StorefrontEmbeddedConfiguration(), $config);
    }
}
