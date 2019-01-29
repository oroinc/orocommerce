<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CMSBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConfigTreeBuilder(): void
    {
        $configuration = new Configuration();

        $treeBuilder = $configuration->getConfigTreeBuilder();
        self::assertInstanceOf(TreeBuilder::class, $treeBuilder);
    }

    /**
     * @param array $treeConfig
     *
     * @dataProvider configDataProvider
     */
    public function testProcessConfiguration(array $treeConfig): void
    {
        $configuration = new Configuration();
        $processor = new Processor();

        $expected = [
            'settings' => [
                'resolved' => 1,
                Configuration::DIRECT_URL_PREFIX => [
                    'value' => '',
                    'scope' => 'app'
                ]
            ],
            Configuration::DIRECT_EDITING => [
                Configuration::LOGIN_PAGE_CSS_FIELD_OPTION => true,
            ]
        ];

        $config = $processor->processConfiguration($configuration, $treeConfig);
        self::assertEquals($expected, $config);
    }

    /**
     * @return array
     */
    public function configDataProvider(): array
    {
        return [
            [
                'if all options not set' => [],
            ],
            [
                'if empty login_page_css_field and direct_editing option' => [
                    'oro_cms' => [],
                ]
            ],
            [
                'if empty login_page_css_field option' => [
                    'oro_cms' =>
                        [
                            Configuration::DIRECT_EDITING => []
                        ],
                ]
            ],
            [
                'if all options set' => [
                    'oro_cms' =>
                        [
                            Configuration::DIRECT_EDITING =>
                                [
                                    Configuration::LOGIN_PAGE_CSS_FIELD_OPTION => true
                                ]
                        ],
                ]

            ],
        ];
    }
}
