<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\QuickAdd;

use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\ProductBundle\QuickAdd\QuickAddRowCollectionViolationsMapper;
use Symfony\Component\Validator\ConstraintViolation;

class QuickAddRowCollectionViolationsMapperTest extends \PHPUnit\Framework\TestCase
{
    private QuickAddRowCollectionViolationsMapper $mapper;

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
                        ''
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
                        ''
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
                        ''
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
                        ''
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
                        ''
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

    public function mapViolationsWithItemsDataProvider(): array
    {
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
                        ''
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
                        ''
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
                        ''
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
                        ''
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
                        ''
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
                        ''
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
                        ''
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
                        ''
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
}
