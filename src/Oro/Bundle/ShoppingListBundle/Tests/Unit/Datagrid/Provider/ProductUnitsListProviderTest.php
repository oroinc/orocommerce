<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ShoppingListBundle\Datagrid\Provider\ProductUnitsListProvider;

class ProductUnitsListProviderTest extends \PHPUnit\Framework\TestCase
{
    private const ITEM = 'item';
    private const EACH = 'each';
    private const ITEM_LABEL = 'item_label';
    private const EACH_LABEL = 'each_label';

    /** @var UnitLabelFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $unitLabelFormatter;

    /** @var ProductUnitsListProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->unitLabelFormatter = $this->createMock(UnitLabelFormatterInterface::class);
        $this->provider = new ProductUnitsListProvider($this->unitLabelFormatter);
    }

    /**
     * @dataProvider getProductUnitsListDataProvider
     *
     * @param array $unitPrecisions
     * @param string $selectedCode
     * @param array $expectedResult
     */
    public function testGetProductUnitsList(array $unitPrecisions, string $selectedCode, array $expectedResult): void
    {
        $productUnit = $this->createMock(ProductUnit::class);
        $productUnit
            ->expects($this->once())
            ->method('getCode')
            ->willReturn($selectedCode);

        $product = $this->createMock(Product::class);
        $product
            ->expects($this->once())
            ->method('getUnitPrecisions')
            ->willReturn(new ArrayCollection($unitPrecisions));

        $this->unitLabelFormatter
            ->expects($this->atLeastOnce())
            ->method('format')
            ->willReturnMap(
                [
                    [self::ITEM, false, false, self::ITEM_LABEL],
                    [self::EACH, false, false, self::EACH_LABEL],
                ]
            );

        $this->assertEquals($expectedResult, $this->provider->getProductUnitsList($product, $productUnit));
    }

    /**
     * @return array
     */
    public function getProductUnitsListDataProvider(): array
    {
        $itemUnit = (new ProductUnit())->setCode('item');
        $eachUnit = (new ProductUnit())->setCode('each');

        return [
            'no unit precisions' => [
                'unitPrecisions' => [],
                'selectedCode' => self::ITEM,
                'expectedResult' => [
                    self::ITEM => [
                        'label' => self::ITEM_LABEL,
                        'selected' => true,
                        'disabled' => true,
                    ],
                ],
            ],
            '2 unit precisions, both enabled' => [
                'unitPrecisions' => [
                    (new ProductUnitPrecision())->setUnit($itemUnit)->setPrecision(2),
                    (new ProductUnitPrecision())->setUnit($eachUnit)->setPrecision(3),
                ],
                'selectedCode' => self::ITEM,
                'expectedResult' => [
                    self::ITEM => [
                        'label' => self::ITEM_LABEL,
                        'selected' => true,
                        'precision' => 2,
                        'disabled' => false,
                    ],
                    self::EACH => [
                        'label' => self::EACH_LABEL,
                        'selected' => false,
                        'precision' => 3,
                        'disabled' => false,
                    ],
                ],
            ],
            '1 unit precisions, the selected not present' => [
                'unitPrecisions' => [
                    (new ProductUnitPrecision())->setUnit($itemUnit)->setPrecision(2),
                ],
                'selectedCode' => self::EACH,
                'expectedResult' => [
                    self::ITEM => [
                        'label' => self::ITEM_LABEL,
                        'selected' => false,
                        'precision' => 2,
                        'disabled' => false,
                    ],
                    self::EACH => [
                        'label' => self::EACH_LABEL,
                        'selected' => true,
                        'disabled' => true,
                    ],
                ],
            ],
            '2 unit precisions, the non-selected not enabled' => [
                'unitPrecisions' => [
                    (new ProductUnitPrecision())->setUnit($itemUnit)->setPrecision(2),
                    (new ProductUnitPrecision())->setUnit($eachUnit)->setSell(false),
                ],
                'selectedCode' => self::ITEM,
                'expectedResult' => [
                    self::ITEM => [
                        'label' => self::ITEM_LABEL,
                        'selected' => true,
                        'precision' => 2,
                        'disabled' => false,
                    ],
                ],
            ],
            '2 unit precisions, the selected not enabled' => [
                'unitPrecisions' => [
                    (new ProductUnitPrecision())->setUnit($itemUnit)->setSell(false),
                    (new ProductUnitPrecision())->setUnit($eachUnit)->setPrecision(2),
                ],
                'selectedCode' => self::ITEM,
                'expectedResult' => [
                    self::ITEM => [
                        'label' => self::ITEM_LABEL,
                        'selected' => true,
                        'disabled' => true,
                    ],
                    self::EACH => [
                        'label' => self::EACH_LABEL,
                        'selected' => false,
                        'precision' => 2,
                        'disabled' => false,
                    ],
                ],
            ],
        ];
    }
}
