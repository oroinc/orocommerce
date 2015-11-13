<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Configuration;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider;
use Oro\Bundle\ActionBundle\Configuration\ActionDefinitionListConfiguration;

class ActionConfigurationProviderTest extends \PHPUnit_Framework_TestCase
{
    const BUNDLE1 = 'Oro\Bundle\ActionBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle1';
    const BUNDLE2 = 'Oro\Bundle\ActionBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle2';
    const BUNDLE3 = 'Oro\Bundle\ActionBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle3';

    /** @var \PHPUnit_Framework_MockObject_MockObject|ActionDefinitionListConfiguration */
    protected $definitionConfiguration;

    /** @var \PHPUnit_Framework_MockObject_MockObject|CacheProvider */
    protected $cacheProvider;

    protected function setUp()
    {
        $this->definitionConfiguration = $this
            ->getMockBuilder('Oro\Bundle\ActionBundle\Configuration\ActionDefinitionListConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $this->cacheProvider = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->setMethods(['contains', 'fetch', 'save', 'delete'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    protected function tearDown()
    {
        unset($this->definitionConfiguration, $this->cacheProvider);
    }

    public function testGetActionConfigurationWithCache()
    {
        $config = ['test' => 'config'];

        $this->cacheProvider->expects($this->once())
            ->method('contains')
            ->with(ActionConfigurationProvider::ROOT_NODE_NAME)
            ->willReturn(true);
        $this->cacheProvider->expects($this->once())
            ->method('fetch')
            ->with(ActionConfigurationProvider::ROOT_NODE_NAME)
            ->willReturn($config);

        $configurationProvider = new ActionConfigurationProvider(
            $this->definitionConfiguration,
            $this->cacheProvider,
            [],
            []
        );

        $this->assertEquals($config, $configurationProvider->getActionConfiguration());
    }

    /**
     * @dataProvider getActionConfigurationDataProvider
     *
     * @param array $rawConfig
     * @param array $expected
     */
    public function testGetActionConfigurationWithoutCache(array $rawConfig, array $expected)
    {
        $this->cacheProvider->expects($this->once())
            ->method('contains')
            ->with(ActionConfigurationProvider::ROOT_NODE_NAME)
            ->willReturn(false);
        $this->cacheProvider->expects($this->never())
            ->method('fetch')
            ->with(ActionConfigurationProvider::ROOT_NODE_NAME);
        $this->cacheProvider->expects($this->once())
            ->method('delete')
            ->with(ActionConfigurationProvider::ROOT_NODE_NAME)
            ->willReturn(true);
        $this->cacheProvider->expects($this->once())
            ->method('save')
            ->with(ActionConfigurationProvider::ROOT_NODE_NAME)
            ->willReturn(true);

        $this->definitionConfiguration->expects($this->once())
            ->method('processConfiguration')
            ->willReturnCallback(function (array $configs) {
                return $configs;
            });

        $configurationProvider = new ActionConfigurationProvider(
            $this->definitionConfiguration,
            $this->cacheProvider,
            $rawConfig,
            [self::BUNDLE1, self::BUNDLE2, self::BUNDLE3]
        );

        $configs = $configurationProvider->getActionConfiguration();

        $this->assertInternalType('array', $configs);
        $this->assertEquals($expected, $configs);
    }

    /**
     * @return array
     */
    public function getActionConfigurationDataProvider()
    {
        return [
            [
                [
                    self::BUNDLE1 => [
                        'test_action1' => [
                            'label' => 'Test Action1',
                            'replaces' => ['test'],
                            'routes' => ['test_route_bundle1']
                        ],
                        'test_action2' => [
                            'extends' => 'test_action1'
                        ],
                        'test_action4' => [
                            'label' => 'Test Action1',
                            'some_config' => [
                                'sub_config1' => 'data1',
                                'sub_config2' => 'data2',
                                'sub_config3' => 'data3',
                            ]
                        ]
                    ],
                    self::BUNDLE2 => [
                        'test_action1' => [
                            'replaces' => ['routes'],
                        ],
                        'test_action4' => [
                            'label' => 'Test Action4',
                            'some_config' => [
                                'replaces' => ['sub_config1', 'sub_config3'],
                                'sub_config3' => 'replaced data',
                            ]
                        ]
                    ],
                    self::BUNDLE3 => [
                        'test_action1' => [
                            'replaces' => ['routes'],
                            'routes' => ['test_route_bundle3']
                        ],
                        'test_action2' => [
                            'label' => 'Test Action2 Bundle3',
                            'extends' => 'test_action1',
                        ],
                        'test_action3' => [
                            'extends' => 'test_action2',
                            'routes' => ['test_route_bundle3_new']
                        ]
                    ]
                ],
                [
                    'test_action1' => [
                        'label' => 'Test Action1',
                        'routes' => ['test_route_bundle3']
                    ],
                    'test_action2' => [
                        'label' => 'Test Action2 Bundle3',
                        'routes' => ['test_route_bundle3']
                    ],
                    'test_action4' => [
                        'label' => 'Test Action4',
                        'some_config' => ['sub_config2' => 'data2', 'sub_config3' => 'replaced data']
                    ],
                    'test_action3' => [
                        'label' => 'Test Action2 Bundle3',
                        'routes' => ['test_route_bundle3', 'test_route_bundle3_new']
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider getActionConfigurationExceptionDataProvider
     *
     * @param array $rawConfig
     * @param string $expectedMessage
     */
    public function testGetActionConfigurationException(array $rawConfig, $expectedMessage)
    {
        $this->cacheProvider->expects($this->once())
            ->method('contains')
            ->with(ActionConfigurationProvider::ROOT_NODE_NAME)
            ->willReturn(false);

        $this->definitionConfiguration->expects($this->never())->method($this->anything());

        $configurationProvider = new ActionConfigurationProvider(
            $this->definitionConfiguration,
            $this->cacheProvider,
            $rawConfig,
            [self::BUNDLE1, self::BUNDLE2, self::BUNDLE3]
        );

        $this->setExpectedException(
            'Symfony\Component\Config\Definition\Exception\InvalidConfigurationException',
            $expectedMessage
        );

        $configurationProvider->getActionConfiguration();
    }

    /**
     * @return array
     */
    public function getActionConfigurationExceptionDataProvider()
    {
        return [
            [
                [
                    self::BUNDLE1 => [
                        'test_action1' => [
                            'label' => 'Test Action1',
                            'extends' => 'test_action3',
                        ]
                    ],
                    self::BUNDLE2 => [
                        'test_action2' => [
                            'label' => 'Test Action2',
                            'extends' => 'test_action1',
                        ]
                    ],
                    self::BUNDLE3 => [
                        'test_action3' => [
                            'label' => 'Test Action3',
                            'extends' => 'test_action2',
                        ]
                    ]
                ],
                'Found cyclomatic extends between test_action1 and test_action2 actions.'
            ],
            [
                [
                    self::BUNDLE2 => [
                        'test_action2' => [
                            'label' => 'Test Action2',
                            'extends' => 'test_action1',
                        ]
                    ]
                ],
                'Could not found config of test_action1 for dependant action test_action2.'
            ]
        ];
    }
}
