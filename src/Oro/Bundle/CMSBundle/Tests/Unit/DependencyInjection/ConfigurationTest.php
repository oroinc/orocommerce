<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CMSBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
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

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function configDataProvider(): array
    {
        return [
            'if all options not set' => [
                'treeConfig' => [],
                'expected' => [
                    'settings' => [
                        'resolved' => 1,
                        Configuration::DIRECT_URL_PREFIX => [
                            'value' => '',
                            'scope' => 'app'
                        ],
                        Configuration::HOME_PAGE => [
                            'value' => null,
                            'scope' => 'app'
                        ]
                    ],
                    Configuration::DIRECT_EDITING => [
                        Configuration::LOGIN_PAGE_CSS_FIELD_OPTION => false,
                    ]
                ]
            ],
            'if empty login_page_css_field, direct_editing option and home_page' => [
                'treeConfig' => [
                    'oro_cms' => [],
                ],
                'expected' => [
                    'settings' => [
                        'resolved' => 1,
                        Configuration::DIRECT_URL_PREFIX => [
                            'value' => '',
                            'scope' => 'app'
                        ],
                        Configuration::HOME_PAGE => [
                            'value' => null,
                            'scope' => 'app'
                        ]
                    ],
                    Configuration::DIRECT_EDITING => [Configuration::LOGIN_PAGE_CSS_FIELD_OPTION => false]
                ]
            ],
            'if empty login_page_css_field option' => [
                'treeConfig' => [
                    'oro_cms' => [
                        Configuration::DIRECT_EDITING => []
                    ],
                ],
                'expected' => [
                    'settings' => [
                        'resolved' => 1,
                        Configuration::DIRECT_URL_PREFIX => [
                            'value' => '',
                            'scope' => 'app'
                        ],
                        Configuration::HOME_PAGE => [
                            'value' => null,
                            'scope' => 'app'
                        ]
                    ],
                    Configuration::DIRECT_EDITING => [Configuration::LOGIN_PAGE_CSS_FIELD_OPTION => false]
                ]
            ],
            'if all options set' => [
                'treeConfig' => [
                    'oro_cms' => [
                        Configuration::DIRECT_EDITING => [Configuration::LOGIN_PAGE_CSS_FIELD_OPTION => true],
                        'content_restrictions' => [
                            'lax_restrictions' => [
                                'ROLE' => [
                                    \stdClass::class => ['content']
                                ]
                            ]
                        ],
                        'settings' => [
                            Configuration::DIRECT_URL_PREFIX => ['value' => 'prefix'],
                            Configuration::HOME_PAGE => ['value' => 1],
                        ]
                    ],
                ],
                'expected' => [
                    'settings' => [
                        'resolved' => 1,
                        Configuration::DIRECT_URL_PREFIX => [
                            'value' => 'prefix',
                            'scope' => 'app'
                        ],
                        Configuration::HOME_PAGE => [
                            'value' => 1,
                            'scope' => 'app'
                        ]
                    ],
                    Configuration::DIRECT_EDITING => [Configuration::LOGIN_PAGE_CSS_FIELD_OPTION => true],
                    'content_restrictions' => [
                        'mode' => 'secure',
                        'lax_restrictions' => [
                            'ROLE' => [
                                \stdClass::class => ['content']
                            ]
                        ]
                    ]
                ]
            ],
        ];
    }
}
