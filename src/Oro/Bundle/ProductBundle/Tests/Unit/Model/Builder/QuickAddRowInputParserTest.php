<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Model\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Model\Builder\QuickAddRowInputParser;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;

class QuickAddRowInputParserTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var ProductUnitsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productUnitsProvider;

    /** @var ProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $productRepository;

    /** @var QuickAddRowInputParser */
    private $quickAddRowInputParser;

    public function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->productUnitsProvider = $this->createMock(ProductUnitsProvider::class);

        $this->registry->method('getRepository')->willReturnMap([
            [Product::class, null, $this->productRepository],
        ]);

        $this->productRepository->method('getPrimaryUnitPrecisionCode')
            ->willReturn('item');

        $this->productUnitsProvider->method('getAvailableProductUnits')
            ->willReturn(
                [
                    'Element' => 'item',
                    'Stunde' => 'hour',
                ]
            );

        $this->quickAddRowInputParser = new QuickAddRowInputParser($this->registry, $this->productUnitsProvider);
    }

    /**
     * @param array $input
     * @param array $expected
     *
     * @dataProvider exampleRow
     */
    public function testCreateFromFileLine($input, $expected)
    {
        $index = 0;

        $result = $this->quickAddRowInputParser->createFromFileLine($input, $index++);

        $this->assertEquals($expected[0], $result->getSku());
        $this->assertEquals($expected[1], $result->getQuantity());
        $this->assertEquals($expected[2], $result->getUnit());

        $this->assertEquals(1, $index);
    }

    /**
     * @param $input
     * @param $expected
     *
     * @dataProvider exampleRow
     */
    public function testCreateFromRequest($input, $expected)
    {
        $index = 0;

        $result = $this->quickAddRowInputParser->createFromCopyPasteTextLine($input, $index++);

        $this->assertEquals($expected[0], $result->getSku());
        $this->assertEquals($expected[1], $result->getQuantity());
        $this->assertEquals($expected[2], $result->getUnit());

        $this->assertEquals(1, $index);
    }

    /**
     * @param $input
     * @param $expected
     *
     * @dataProvider exampleRow
     */
    public function testCreateFromPasteTextLine($input, $expected)
    {
        $index = 0;

        $result = $this->quickAddRowInputParser->createFromCopyPasteTextLine($input, $index++);

        $this->assertEquals($expected[0], $result->getSku());
        $this->assertEquals($expected[1], $result->getQuantity());
        $this->assertEquals($expected[2], $result->getUnit());

        $this->assertEquals(1, $index);
    }

    /**
     * @return array
     */
    public function exampleRow()
    {
        return [
            [
                'input' => [
                    ' SKU5  ',
                    ' 4.5',
                    'item '
                ],
                'expected' => [
                    'SKU5',
                    '4.5',
                    'item'
                ]
            ],
            [
                'input' => [
                    'ss2',
                    '   6 ',
                    'liter'
                ],
                'expected' => [
                    'ss2',
                    '6',
                    'liter'
                ]
            ],
            [
                'input' => [
                    'ss2',
                    '   6 ',
                ],
                'expected' => [
                    'ss2',
                    '6',
                    'item'
                ]
            ],
            [
                'input' => [
                    ' SKU5  ',
                    ' 4.5',
                    'Stunde '
                ],
                'expected' => [
                    'SKU5',
                    '4.5',
                    'hour'
                ]
            ],
            [
                'input' => [
                    ' SKU5  ',
                    ' 4.5',
                    'ELEMENT '
                ],
                'expected' => [
                    'SKU5',
                    '4.5',
                    'item'
                ]
            ],
        ];
    }
}
