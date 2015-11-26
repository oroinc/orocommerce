<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Configuration;

use Oro\Bundle\ActionBundle\Configuration\ActionDefinitionConfiguration;

class ActionDefinitionConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ActionDefinitionConfiguration
     */
    protected $configuration;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->configuration = new ActionDefinitionConfiguration();
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
     * @dataProvider processInvalidConfigurationProvider
     *
     * @param array $inputData
     * @param string $expectedExceptionMessage
     */
    public function testProcessInvalidConfiguration(array $inputData, $expectedExceptionMessage)
    {
        $this->setExpectedException(
            'Symfony\Component\Config\Definition\Exception\InvalidConfigurationException',
            $expectedExceptionMessage
        );

        $this->configuration->processConfiguration($inputData);
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
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
                    'entities' => [],
                    'routes' => [],
                    'order' => 0,
                    'enabled' => true,
                    'prefunctions' => [],
                    'preconditions' => [],
                    'postfunctions' => [],
                    'attributes' => []
                ],
            ],
            'full valid configuration' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label 2',
                        'applications' => ['app1', 'app2', 'app3'],
                        'entities' => ['Entity1', 'Entity2'],
                        'routes' => ['route_1', 'route_2'],
                        'order' => 15,
                        'enabled' => false,
                        'frontend_options' => [
                            'icon' => 'icon',
                            'class' => 'class',
                            'template' => 'template',
                        ],
                        'form_options' => [
                            'attribute_fields' => [
                                'attribute_1' => [
                                    'form_type' => 'test type',
                                    'options' => [
                                        'class' => 'testClass',
                                    ]
                                ]
                            ],
                            'attribute_default_values' => [
                                'attribute_1' => 'value 1',
                            ]
                        ],
                        'prefunctions' => [
                            '@create_date' => [],
                        ],
                        'preconditions' => [
                            '@equal' => '1',
                        ],
                        'postfunctions' => [
                            '@call_method' => [],
                        ],
                        'attributes' => [
                            'test_attribute' => [
                                'type' => 'string',
                                'label' => 'Test Attribute Label'
                            ]
                        ]
                    ],
                ],
                'expected' => [
                    'label' => 'Test Label 2',
                    'applications' => ['app1', 'app2', 'app3'],
                    'entities' => ['Entity1', 'Entity2'],
                    'routes' => ['route_1', 'route_2'],
                    'order' => 15,
                    'enabled' => false,
                    'frontend_options' => [
                        'icon' => 'icon',
                        'class' => 'class',
                        'template' => 'template',
                    ],
                    'form_options' => [
                        'attribute_fields' => [
                            'attribute_1' => [
                                'form_type' => 'test type',
                                'options' => [
                                    'class' => 'testClass',
                                ],
                            ]
                        ],
                        'attribute_default_values' => [
                            'attribute_1' => 'value 1',
                        ]
                    ],
                    'prefunctions' => [
                        '@create_date' => [],
                    ],
                    'preconditions' => [
                        '@equal' => '1',
                    ],
                    'postfunctions' => [
                        '@call_method' => [],
                    ],
                    'attributes' => [
                        'test_attribute' => [
                            'type' => 'string',
                            'label' => 'Test Attribute Label',
                            'property_path' => null,
                            'options' => []
                        ]
                    ]
                ],
            ],
        ];
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processInvalidConfigurationProvider()
    {
        return [
            'incorrect root' => [
                'input' => [
                    'action' => 'not array value',
                ],
                'message' => 'Invalid type for path "action". Expected array, but got string'
            ],
            'empty action[label]' => [
                'input' => [
                    'action' => [],
                ],
                'message' => 'The child node "label" at path "action" must be configured'
            ],
            'incorrect action[application]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'applications' => 'not array value',
                    ],
                ],
                'message' => 'Invalid type for path "action.applications". Expected array, but got string'
            ],
            'incorrect action[entities]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'applications' => [],
                        'entities' => 'not array value',
                    ],
                ],
                'message' => 'Invalid type for path "action.entities". Expected array, but got string'
            ],
            'incorrect action[routes]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'applications' => [],
                        'entities' => [],
                        'routes' => 'not array value',
                    ],
                ],
                'message' => 'Invalid type for path "action.routes". Expected array, but got string'
            ],
            'incorrect action[order]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'applications' => [],
                        'entities' => [],
                        'routes' => [],
                        'order' => 'not integer value',
                    ],
                ],
                'message' => 'Invalid type for path "action.order". Expected int, but got string'
            ],
            'incorrect action[enabled]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'applications' => [],
                        'entities' => [],
                        'routes' => [],
                        'order' => 10,
                        'enabled' => 'not bool value',
                    ],
                ],
                'message' => 'Invalid type for path "action.enabled". Expected boolean, but got string'
            ],
            'incorrect action[frontend_options]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'applications' => [],
                        'entities' => [],
                        'routes' => [],
                        'order' => 10,
                        'enabled' => true,
                        'frontend_options' => 'not array value',
                    ],
                ],
                'message' => 'Invalid type for path "action.frontend_options". Expected array, but got string'
            ],
            'incorrect action[attribute]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'attributes' => 'not array value',
                    ],
                ],
                'message' => 'Invalid type for path "action.attributes". Expected array, but got string'
            ],
            'incorrect action[attribute][type]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'attributes' => [
                            'test' => [
                                'type' => []
                            ]
                        ],
                    ],
                ],
                'message' => 'Invalid type for path "action.attributes.test.type". Expected scalar, but got array'
            ],
            'incorrect action[attribute][label]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'attributes' => [
                            'test' => [
                                'type' => 'type',
                                'label' => []
                            ]
                        ],
                    ],
                ],
                'message' => 'Invalid type for path "action.attributes.test.label". Expected scalar, but got array'
            ],
            'incorrect action[attribute][options]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'attributes' => [
                            'test' => [
                                'type' => 'type',
                                'label' => 'label',
                                'options' => 1
                            ]
                        ],
                    ],
                ],
                'message' => 'Invalid type for path "action.attributes.test.options". Expected array, but got integer'
            ],
            'incorrect action[form_options]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'applications' => [],
                        'entities' => [],
                        'routes' => [],
                        'order' => 10,
                        'enabled' => true,
                        'form_options' => [
                            'attribute_fields' => [
                                'attribute_1' => [
                                    'form_type' => 'test type',
                                    'options' => 'not array value',
                                ]
                            ],
                        ],
                    ],
                ],
                'message' => 'Invalid type for path "action.form_options.attribute_fields.attribute_1.options"'
            ],
        ];
    }
}
