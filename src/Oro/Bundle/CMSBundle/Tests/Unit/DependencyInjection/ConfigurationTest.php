<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CMSBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConfigTreeBuilder(): void
    {
        $configuration = new Configuration(['secure', 'unsecure']);

        $treeBuilder = $configuration->getConfigTreeBuilder();
        self::assertInstanceOf(TreeBuilder::class, $treeBuilder);
    }

    /**
     * @dataProvider configDataProvider
     */
    public function testProcessConfiguration(array $treeConfig, array $expected): void
    {
        $configuration = new Configuration(['secure', 'unsecure']);
        $processor = new Processor();

        $config = $processor->processConfiguration($configuration, $treeConfig);
        self::assertEquals($expected, $config);
    }

    public function configDataProvider(): array
    {
        $configSettings = [
            'resolved' => 1,
            Configuration::DIRECT_URL_PREFIX => [
                'value' => '',
                'scope' => 'app',
            ],
            Configuration::IS_SANITIZE_WYSIWYG_NOTICE_SHOWN => [
                'value' => false,
                'scope' => 'app',
            ],
        ];

        return [
            'if all options not set' => [
                'treeConfig' => [],
                'expected' => [
                    'settings' => $configSettings,
                    Configuration::DIRECT_EDITING => [
                        Configuration::LOGIN_PAGE_CSS_FIELD_OPTION => false,
                    ],
                ],
            ],
            'if empty login_page_css_field and direct_editing option' => [
                'treeConfig' => [
                    'oro_cms' => [],
                ],
                'expected' => [
                    'settings' => $configSettings,
                    Configuration::DIRECT_EDITING => [Configuration::LOGIN_PAGE_CSS_FIELD_OPTION => false],
                ],
            ],
            'if empty login_page_css_field option' => [
                'treeConfig' => [
                    'oro_cms' => [
                        Configuration::DIRECT_EDITING => [],
                    ],
                ],
                'expected' => [
                    'settings' => $configSettings,
                    Configuration::DIRECT_EDITING => [Configuration::LOGIN_PAGE_CSS_FIELD_OPTION => false],
                ],
            ],
            'if all options set' => [
                'treeConfig' => [
                    'oro_cms' => [
                        Configuration::DIRECT_EDITING => [Configuration::LOGIN_PAGE_CSS_FIELD_OPTION => false],
                        'content_restrictions' => [
                            'lax_restrictions' => [
                                'ROLE' => [
                                    \stdClass::class => ['content'],
                                ],
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    'settings' => $configSettings,
                    Configuration::DIRECT_EDITING => [Configuration::LOGIN_PAGE_CSS_FIELD_OPTION => false],
                    'content_restrictions' => [
                        'mode' => 'secure',
                        'lax_restrictions' => [
                            'ROLE' => [
                                \stdClass::class => ['content'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
