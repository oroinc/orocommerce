<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Model\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Model\Builder\QuickAddRowInputParser;

class QuickAddRowInputParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var ProductUnitRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productUnitRepository;

    /**
     * @var ProductRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productRepository;

    /**
     * @var QuickAddRowInputParser
     */
    private $quickAddRowInputParser;

    public function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->productRepository = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productUnitRepository = $this->getMockBuilder(ProductUnitRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry->method('getRepository')->willReturnMap([
            [Product::class, null, $this->productRepository],
            [ProductUnit::class, null, $this->productUnitRepository]
        ]);

        $this->productUnitRepository->method('findOneBy')
            ->willReturnCallback(function ($where) {
                if (isset($where['code'])) {
                    $unit = new ProductUnit();
                    $unit->setCode($where['code']);

                    return $unit;
                }

                return null;
            });

        $this->productRepository->method('getPrimaryUnitPrecisionCode')
            ->willReturn('item');

        $this->quickAddRowInputParser = new QuickAddRowInputParser($this->registry);
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
            ]
        ];
    }
}
