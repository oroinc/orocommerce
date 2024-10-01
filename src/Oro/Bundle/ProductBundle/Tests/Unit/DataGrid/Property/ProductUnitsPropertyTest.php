<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\DataGrid\Property;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ProductBundle\DataGrid\Property\ProductUnitsProperty;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\TestCase;

class ProductUnitsPropertyTest extends TestCase
{
    private ProductUnitsProperty $property;

    #[\Override]
    protected function setUp(): void
    {
        $this->property = new ProductUnitsProperty();
    }

    /**
     * @dataProvider getProductUnitsDataProvider
     */
    public function testGetProductUnits(array $unitPrecisions, array $unitsList): void
    {
        $product = (new ProductStub())
            ->setUnitPrecisions(new ArrayCollection($unitPrecisions));

        self::assertEquals($unitsList, $this->property->getProductUnits($product));
    }

    public function getProductUnitsDataProvider(): array
    {
        $itemUnit = (new ProductUnit())->setCode('item');
        $eachUnit = (new ProductUnit())->setCode('each');

        return [
            'no unit precisions' => [
                'unitPrecisions' => [],
                'unitsList' => [],
            ],
            '2 unit precisions, both enabled' => [
                'unitPrecisions' => [
                    (new ProductUnitPrecision())->setUnit($itemUnit)->setPrecision(2),
                    (new ProductUnitPrecision())->setUnit($eachUnit)->setPrecision(3),
                ],
                'expectedResult' => [
                    'item' => ['precision' => 2],
                    'each' => ['precision' => 3],
                ],
            ],
            '2 unit precisions, one not enabled' => [
                'unitPrecisions' => [
                    (new ProductUnitPrecision())->setUnit($itemUnit)->setPrecision(2),
                    (new ProductUnitPrecision())->setUnit($eachUnit)->setSell(false),
                ],
                'expectedResult' => [
                    'item' => ['precision' => 2],
                ],
            ],
        ];
    }
}
