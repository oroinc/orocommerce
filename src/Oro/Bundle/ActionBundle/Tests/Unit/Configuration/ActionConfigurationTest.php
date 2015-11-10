<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Configuration;

use Oro\Bundle\ActionBundle\Configuration\ActionConfiguration;

class ActionConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ActionConfiguration
     */
    protected $configuration;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->configuration = new ActionConfiguration();
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider processValidConfigurationProvider
     */
    public function testProcessValidConfiguration(array $inputData, array $expectedData)
    {
        $this->assertEquals(
            $expectedData,
            $this->configuration->processConfiguration($inputData)
        );
    }

    /**
     * @param array $inputData
     *
     * @dataProvider processInvalidConfigurationProvider
     * @expectedException Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testProcessInvalidConfiguration(array $inputData)
    {
        $this->configuration->processConfiguration($inputData);
    }

    /**
     * @return array
     */
    public function processValidConfigurationProvider()
    {
        return [
            'min valid configuration' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label 1',
                    ],
                ],
                'expected' => [
                    'label' => 'Test Label 1',
                    'applications' => [],
                    'extend_strategy' => 'add',
                    'entities' => [],
                    'routes' => [],
                    'enabled' => true,
                ],
            ],
            'full valid configuration' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label 2',
                        'applications' => ['app1', 'app2', 'app3'],
                        'extend' => 'parent_config_id',
                        'extend_priority' => 100,
                        'extend_strategy' => 'replace',
                        'entities' => ['Entity1', 'Entity2'],
                        'routes' => ['route_1', 'route_2'],
                        'order' => 15,
                        'enabled' => false,
                        'frontend_options' => [
                            'icon' => 'icon',
                            'class' => 'class',
                            'template' => 'template',
                        ],
                    ],
                ],
                'expected' => [
                    'label' => 'Test Label 2',
                    'applications' => ['app1', 'app2', 'app3'],
                    'extend' => 'parent_config_id',
                    'extend_priority' => 100,
                    'extend_strategy' => 'replace',
                    'entities' => ['Entity1', 'Entity2'],
                    'routes' => ['route_1', 'route_2'],
                    'order' => 15,
                    'enabled' => false,
                    'frontend_options' => [
                        'icon' => 'icon',
                        'class' => 'class',
                        'template' => 'template',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function processInvalidConfigurationProvider()
    {
        return [
            'incorrect root' => [
                'input' => [
                    'action' => 'not array value',
                ],
            ],
            'empty action[label]' => [
                'input' => [
                    'action' => [],
                ],
            ],
            'incorrect action[application]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'applications' => 'not array value',
                    ],
                ],
            ],
            'incorrect action[extend_priority]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'applications' => [],
                        'extend_priority' => 'not integer value',
                    ],
                ],
            ],
            'incorrect action[extend_strategy]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'applications' => [],
                        'extend_priority' => 100,
                        'extend_strategy' => 'not valid value',
                    ],
                ],
            ],
            'incorrect action[entities]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'applications' => [],
                        'extend_priority' => 100,
                        'extend_strategy' => 'add',
                        'entities' => 'not array value',
                    ],
                ],
            ],
            'incorrect action[routes]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'applications' => [],
                        'extend_priority' => 100,
                        'extend_strategy' => 'add',
                        'entities' => [],
                        'routes' => 'not array value',
                    ],
                ],
            ],
            'incorrect action[order]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'applications' => [],
                        'extend_priority' => 100,
                        'extend_strategy' => 'add',
                        'entities' => [],
                        'routes' => [],
                        'order' => 'not integer value',
                    ],
                ],
            ],
            'incorrect action[enabled]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'applications' => [],
                        'extend_priority' => 100,
                        'extend_strategy' => 'add',
                        'entities' => [],
                        'routes' => [],
                        'order' => 10,
                        'enabled' => 'not bool value',
                    ],
                ],
            ],
            'incorrect action[frontend_options]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'applications' => [],
                        'extend_priority' => 100,
                        'extend_strategy' => 'add',
                        'entities' => [],
                        'routes' => [],
                        'order' => 10,
                        'enabled' => true,
                        'frontend_options' => 'not array value',
                    ],
                ],
            ],
        ];
    }
}
