<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\EventListener\Workflow;

use Oro\Bundle\CheckoutBundle\WorkflowState\EventListener\Workflow\CheckoutConfigBuilderExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class CheckoutConfigBuilderExtensionTest extends TestCase
{
    private CheckoutConfigBuilderExtension $checkoutConfigBuilderExtension;

    #[\Override]
    protected function setUp(): void
    {
        $this->checkoutConfigBuilderExtension = new CheckoutConfigBuilderExtension();
    }

    /**
     * @dataProvider configDataProvider
     */
    public function testPrepare(array $config, array $expected)
    {
        $workflowName = 'test_workflow';
        $this->assertEquals($expected, $this->checkoutConfigBuilderExtension->prepare($workflowName, $config));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function configDataProvider(): \Generator
    {
        yield [
            [
                'metadata' => [
                    'is_checkout_workflow' => true,
                    'checkout_state_config' => [
                        'enable_state_protection' => true,
                    ],
                ],
                'attributes' => [],
                'transitions' => [
                    [
                        'frontend_options' => [
                            'is_checkout_continue' => true,
                        ],
                        'form_options' => [
                            'attribute_fields' => [
                                'field1' => null
                            ]
                        ],
                    ],
                ],
            ],
            [
                'metadata' => [
                    'is_checkout_workflow' => true,
                    'checkout_state_config' => [
                        'enable_state_protection' => true,
                    ],
                ],
                'attributes' => [
                    'state_token' => [
                        'type' => 'string',
                        'label' => 'oro.workflow.checkout.state_token.attribute_label',
                    ],
                ],
                'transitions' => [
                    [
                        'frontend_options' => [
                            'is_checkout_continue' => true,
                        ],
                        'form_options' => [
                            'attribute_fields' => [
                                'field1' => null,
                                'state_token' => [
                                    'form_type' => HiddenType::class,
                                    'label' => 'oro.workflow.checkout.state_token.attribute_label',
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        ];

        yield [
            [
                'metadata' => [
                    'is_checkout_workflow' => true,
                    'checkout_state_config' => [
                        'enable_state_protection' => false,
                    ],
                ],
                'attributes' => [],
                'transitions' => [],
            ],
            [
                'metadata' => [
                    'is_checkout_workflow' => true,
                    'checkout_state_config' => [
                        'enable_state_protection' => false,
                    ],
                ],
                'attributes' => [],
                'transitions' => [],
            ]
        ];

        yield [
            [
                'metadata' => [
                    'is_checkout_workflow' => true,
                    'checkout_state_config' => [
                        'enable_state_protection' => true,
                    ],
                ],
                'attributes' => [
                    'state_token' => [
                        'type' => 'string',
                        'label' => 'existing_label',
                    ],
                ],
                'transitions' => [
                    [
                        'frontend_options' => [
                            'is_checkout_continue' => true,
                        ],
                        'form_options' => [
                            'attribute_fields' => [
                                'state_token' => [
                                    'form_type' => HiddenType::class,
                                    'label' => 'existing_label',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'metadata' => [
                    'is_checkout_workflow' => true,
                    'checkout_state_config' => [
                        'enable_state_protection' => true,
                    ],
                ],
                'attributes' => [
                    'state_token' => [
                        'type' => 'string',
                        'label' => 'existing_label',
                    ],
                ],
                'transitions' => [
                    [
                        'frontend_options' => [
                            'is_checkout_continue' => true,
                        ],
                        'form_options' => [
                            'attribute_fields' => [
                                'state_token' => [
                                    'form_type' => HiddenType::class,
                                    'label' => 'existing_label',
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        ];
    }
}
