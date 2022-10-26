<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\ProductBundle\Form\DataTransformer\QuickAddRowCollectionTransformer;
use Oro\Bundle\ProductBundle\Model\Builder\QuickAddRowCollectionBuilder;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;

class QuickAddRowCollectionTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var QuickAddRowCollectionBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $quickAddRowCollectionBuilder;

    private QuickAddRowCollectionTransformer $transformer;

    protected function setUp(): void
    {
        $this->quickAddRowCollectionBuilder = $this->createMock(QuickAddRowCollectionBuilder::class);
        $this->transformer = new QuickAddRowCollectionTransformer($this->quickAddRowCollectionBuilder);
    }

    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform($value, array $expected): void
    {
        self::assertEquals($expected, $this->transformer->transform($value));
    }

    public function transformDataProvider(): array
    {
        $quickAddRow = new QuickAddRow(1, 'sku1', 42, 'item');
        return [
            ['value' => null, 'expected' => []],
            ['value' => [], 'expected' => []],
            [
                'value' => [$quickAddRow],
                'expected' => [
                    [
                        'sku' => $quickAddRow->getSku(),
                        'quantity' => $quickAddRow->getQuantity(),
                        'unit' => $quickAddRow->getUnit(),
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform($value): void
    {
        $quickAddRowCollection = new QuickAddRowCollection();
        $this->quickAddRowCollectionBuilder
            ->expects(self::once())
            ->method('buildFromArray')
            ->with((array)$value)
            ->willReturn($quickAddRowCollection);

        self::assertEquals($quickAddRowCollection, $this->transformer->reverseTransform($value));
    }

    public function reverseTransformDataProvider(): array
    {
        return [
            ['value' => null],
            ['value' => []],
            ['value' => [['sample_key' => 'sample_value']]],
        ];
    }
}
