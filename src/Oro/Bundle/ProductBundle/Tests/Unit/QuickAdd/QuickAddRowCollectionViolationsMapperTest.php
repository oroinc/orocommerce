<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\QuickAdd;

use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\ProductBundle\QuickAdd\QuickAddRowCollectionViolationsMapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;

final class QuickAddRowCollectionViolationsMapperTest extends TestCase
{
    private QuickAddRowCollectionViolationsMapper $mapper;

    #[\Override]
    protected function setUp(): void
    {
        $this->mapper = new QuickAddRowCollectionViolationsMapper();
    }

    /**
     * @dataProvider mapViolationsDataProvider
     */
    public function testMapViolations(array $violations, bool $errorBubbling, array $expected): void
    {
        $quickAddRowCollection = new QuickAddRowCollection();

        $this->mapper->mapViolations($quickAddRowCollection, $violations, $errorBubbling);

        self::assertEquals($expected, $quickAddRowCollection->getErrors());
    }

    public function mapViolationsDataProvider(): array
    {
        $constraint = $this->createMock(Constraint::class);

        return [
            'no violations' => ['violations' => [], 'errorBubbling' => false, 'expected' => []],
            'no property path and message template' => [
                'violations' => [
                    new ConstraintViolation(
                        'sample message',
                        null,
                        ['{{ sample_key }}' => 'sample_value'],
                        \stdClass::class,
                        null,
                        '',
                        constraint: $constraint
                    ),
                ],
                'errorBubbling' => false,
                'expected' => [
                    ['message' => 'sample message', 'parameters' => ['{{ sample_key }}' => 'sample_value']],
                ],
            ],
            'no property path' => [
                'violations' => [
                    new ConstraintViolation(
                        'sample message',
                        'sample message template',
                        ['{{ sample_key }}' => 'sample_value'],
                        \stdClass::class,
                        null,
                        '',
                        constraint: $constraint
                    ),
                ],
                'errorBubbling' => false,
                'expected' => [
                    ['message' => 'sample message template', 'parameters' => ['{{ sample_key }}' => 'sample_value']],
                ],
            ],
            'with invalid property path' => [
                'violations' => [
                    new ConstraintViolation(
                        'sample message',
                        'sample message template',
                        ['{{ sample_key }}' => 'sample_value'],
                        \stdClass::class,
                        'foobar',
                        '',
                        constraint: $constraint
                    ),
                ],
                'errorBubbling' => false,
                'expected' => [
                    ['message' => 'sample message template', 'parameters' => ['{{ sample_key }}' => 'sample_value']],
                ],
            ],
            'with non-existent index in property path' => [
                'violations' => [
                    new ConstraintViolation(
                        'sample message',
                        'sample message template',
                        ['{{ sample_key }}' => 'sample_value'],
                        \stdClass::class,
                        '[999]',
                        '',
                        constraint: $constraint
                    ),
                ],
                'errorBubbling' => false,
                'expected' => [],
            ],
            'with property path and error bubbling' => [
                'violations' => [
                    new ConstraintViolation(
                        'sample message',
                        null,
                        ['{{ sample_key }}' => 'sample_value'],
                        \stdClass::class,
                        '[0].quantity',
                        '',
                        constraint: $constraint
                    ),
                ],
                'errorBubbling' => true,
                'expected' => [
                    ['message' => 'sample message', 'parameters' => ['{{ sample_key }}' => 'sample_value']],
                ],
            ],
        ];
    }

    /**
     * @dataProvider mapViolationsWithItemsDataProvider
     */
    public function testMapViolationsWithItems(array $violations, array $expected): void
    {
        $quickAddRow = new QuickAddRow(1, 'sku1', 42, 'kg');
        $quickAddRowCollection = new QuickAddRowCollection([$quickAddRow]);

        $this->mapper->mapViolations($quickAddRowCollection, $violations);

        self::assertEquals([], $quickAddRowCollection->getErrors());
        self::assertEquals($expected, $quickAddRow->getErrors());
    }

    /**
     * @dataProvider mapWarningViolationsWithItemsDataProvider
     */
    public function testMapWarningViolationsWithItems(array $violations, array $expected): void
    {
        $quickAddRow = new QuickAddRow(1, 'sku1', 42, 'kg');
        $quickAddRowCollection = new QuickAddRowCollection([$quickAddRow]);

        $this->mapper->mapViolationsAgainstGroups(
            $quickAddRowCollection,
            $violations,
            ['group1', 'group2']
        );

        self::assertEquals($expected, $quickAddRow->getWarnings());
    }

    public function mapViolationsWithItemsDataProvider(): array
    {
        $constraint = $this->createMock(Constraint::class);
        $constraint->groups = ['oro_quick_order_to_checkout', 'oro_quick_order_to_quote'];

        return [
            'no violations' => ['violations' => [], 'expected' => []],
            'with non-existent index in property path' => [
                'violations' => [
                    new ConstraintViolation(
                        'sample message',
                        'sample message template',
                        ['{{ sample_key }}' => 'sample_value'],
                        \stdClass::class,
                        '[999]',
                        '',
                        constraint: $constraint
                    ),
                ],
                'expected' => [],
            ],
            'with correct index in property path, without property name, without message template' => [
                'violations' => [
                    new ConstraintViolation(
                        'sample message',
                        null,
                        ['{{ sample_key }}' => 'sample_value'],
                        \stdClass::class,
                        '[0]',
                        '',
                        constraint: $constraint
                    ),
                ],
                'expected' => [
                    [
                        'message' => 'sample message',
                        'parameters' => [
                            '{{ sample_key }}' => 'sample_value',
                            '{{ index }}' => 1,
                            '{{ sku }}' => 'sku1',
                        ],
                        'propertyPath' => '',
                    ],
                ],
            ],
            'with correct index in property path, without property name' => [
                'violations' => [
                    new ConstraintViolation(
                        'sample message',
                        'sample message template',
                        ['{{ sample_key }}' => 'sample_value'],
                        \stdClass::class,
                        '[0]',
                        '',
                        constraint: $constraint
                    ),
                ],
                'expected' => [
                    [
                        'message' => 'sample message template',
                        'parameters' => [
                            '{{ sample_key }}' => 'sample_value',
                            '{{ index }}' => 1,
                            '{{ sku }}' => 'sku1',
                        ],
                        'propertyPath' => '',
                    ],
                ],
            ],
            'with correct index in property path' => [
                'violations' => [
                    new ConstraintViolation(
                        'sample message',
                        'sample message template',
                        ['{{ sample_key }}' => 'sample_value'],
                        \stdClass::class,
                        '[0].quantity',
                        '',
                        constraint: $constraint
                    ),
                ],
                'expected' => [
                    [
                        'message' => 'sample message template',
                        'parameters' => [
                            '{{ sample_key }}' => 'sample_value',
                            '{{ index }}' => 1,
                            '{{ sku }}' => 'sku1',
                        ],
                        'propertyPath' => 'quantity',
                    ],
                ],
            ],
        ];
    }

    public function mapWarningViolationsWithItemsDataProvider(): array
    {
        $constraint = $this->createMock(Constraint::class);
        $constraint->groups = ['oro_quick_order_to_checkout'];

        return [
            'warning with correct index in property path, without property name, without message template' => [
                'violations' => [
                    new ConstraintViolation(
                        'sample message',
                        null,
                        ['{{ sample_key }}' => 'sample_value'],
                        \stdClass::class,
                        '[0]',
                        '',
                        constraint: $constraint
                    ),
                ],
                'expected' => [
                    [
                        'message' => 'sample message',
                        'parameters' => [
                            '{{ sample_key }}' => 'sample_value',
                            '{{ index }}' => 1,
                            '{{ sku }}' => 'sku1',
                        ],
                        'propertyPath' => '',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider mapViolationsWithErrorBubblingAndItemsDataProvider
     */
    public function testMapViolationsWithErrorBubblingAndItems(array $violations, array $expected): void
    {
        $quickAddRow = new QuickAddRow(1, 'sku1', 42, 'kg');
        $quickAddRowCollection = new QuickAddRowCollection([$quickAddRow]);

        $this->mapper->mapViolations($quickAddRowCollection, $violations, true);

        self::assertEquals($expected, $quickAddRowCollection->getErrors());
        self::assertEquals([], $quickAddRow->getErrors());
    }

    public function mapViolationsWithErrorBubblingAndItemsDataProvider(): array
    {
        $constraint = $this->createMock(Constraint::class);

        return [
            'no violations' => ['violations' => [], 'expected' => []],
            'with non-existent index in property path' => [
                'violations' => [
                    new ConstraintViolation(
                        'sample message',
                        'sample message template',
                        ['{{ sample_key }}' => 'sample_value'],
                        \stdClass::class,
                        '[999]',
                        '',
                        constraint: $constraint
                    ),
                ],
                'expected' => [
                    [
                        'message' => 'sample message template',
                        'parameters' => ['{{ sample_key }}' => 'sample_value'],
                    ],
                ],
            ],
            'with correct index in property path, without property name, without message template' => [
                'violations' => [
                    new ConstraintViolation(
                        'sample message',
                        null,
                        ['{{ sample_key }}' => 'sample_value'],
                        \stdClass::class,
                        '[0]',
                        '',
                        constraint: $constraint
                    ),
                ],
                'expected' => [
                    [
                        'message' => 'sample message',
                        'parameters' => ['{{ sample_key }}' => 'sample_value'],
                    ],
                ],
            ],
            'with correct index in property path, without property name' => [
                'violations' => [
                    new ConstraintViolation(
                        'sample message',
                        'sample message template',
                        ['{{ sample_key }}' => 'sample_value'],
                        \stdClass::class,
                        '[0]',
                        '',
                        constraint: $constraint
                    ),
                ],
                'expected' => [
                    [
                        'message' => 'sample message template',
                        'parameters' => ['{{ sample_key }}' => 'sample_value'],
                    ],
                ],
            ],
            'with correct index in property path' => [
                'violations' => [
                    new ConstraintViolation(
                        'sample message',
                        'sample message template',
                        ['{{ sample_key }}' => 'sample_value'],
                        \stdClass::class,
                        '[0].quantity',
                        '',
                        constraint: $constraint
                    ),
                ],
                'expected' => [
                    [
                        'message' => 'sample message template',
                        'parameters' => ['{{ sample_key }}' => 'sample_value'],
                    ],
                ],
            ],
        ];
    }

    public function testThatTwoConstraintsGrouped(): void
    {
        $constraint1 = $this->createMock(Constraint::class);
        $constraint1->payload = ['quick_order_form_group_key' => 'group_key'];
        $constraint1->groups = ['oro_quick_order_to_checkout'];

        $constraint2 = $this->createMock(Constraint::class);
        $constraint2->payload = ['quick_order_form_group_key' => 'group_key'];
        $constraint2->groups = ['oro_quick_order_to_quote'];


        $violations = [
            new ConstraintViolation(
                'sample message1',
                'sample message template1',
                ['{{ sample_key }}' => 'sample_value'],
                \stdClass::class,
                '[0].quantity',
                '',
                constraint: $constraint1
            ),
            new ConstraintViolation(
                'sample message2',
                'sample message template2',
                ['{{ sample_key }}' => 'sample_value'],
                \stdClass::class,
                '[0].quantity',
                '',
                constraint: $constraint2
            ),
        ];


        $quickAddRow = new QuickAddRow(1, 'sku1', 42, 'kg');
        $quickAddRowCollection = new QuickAddRowCollection([$quickAddRow]);

        $this->mapper->mapViolations($quickAddRowCollection, $violations);

        self::assertEquals(
            [
                [
                    'message' => 'sample message template1',
                    'parameters' => [
                        '{{ sample_key }}' => 'sample_value',
                        '{{ index }}' => 1,
                        '{{ sku }}' => 'sku1',
                    ],
                    'propertyPath' => 'quantity',
                ],
                [
                    'message' => 'sample message template2',
                    'parameters' => [
                        '{{ sample_key }}' => 'sample_value',
                        '{{ index }}' => 1,
                        '{{ sku }}' => 'sku1',
                    ],
                    'propertyPath' => 'quantity',
                ],
            ],
            $quickAddRow->getErrors()
        );
    }

    public function testExcludedComponentsFromMultiGroupValidation(): void
    {
        $constraint = $this->createMock(Constraint::class);
        $constraint->payload = ['constraint_group_key' => 'inventory_status'];
        $constraint->groups = [
            'oro_shopping_list_to_checkout_quick_add_processor',
            'oro_rfp_quick_add_processor',
        ];

        $violations = [
            new ConstraintViolation(
                'inventory status message',
                'inventory status message template',
                ['{{ status }}' => 'out_of_stock'],
                \stdClass::class,
                '[0].product',
                '',
                constraint: $constraint
            ),
        ];

        $quickAddRow = new QuickAddRow(1, 'sku1', 42, 'kg');
        $quickAddRowCollection = new QuickAddRowCollection([$quickAddRow]);

        $this->mapper->setExcludedGroupsFromMultiGroupValidation(['oro_shopping_list_quick_add_processor']);

        $this->mapper->mapViolationsAgainstGroups(
            $quickAddRowCollection,
            $violations,
            [
                'oro_shopping_list_to_checkout_quick_add_processor',
                'oro_rfp_quick_add_processor',
                'oro_shopping_list_quick_add_processor'
            ]
        );

        self::assertCount(1, $quickAddRow->getErrors());
        self::assertEquals([], $quickAddRow->getWarnings());

        $errors = $quickAddRow->getErrors();
        self::assertEquals('inventory status message template', $errors[0]['message']);
    }
}
