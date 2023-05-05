<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Visibility;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Visibility\BasicProductUnitFieldsSettings;

class BasicProductUnitFieldsSettingsTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var BasicProductUnitFieldsSettings */
    private $settings;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->settings = new BasicProductUnitFieldsSettings($this->doctrineHelper);
    }

    public function testIsProductUnitSelectionVisible()
    {
        $product = $this->createMock(Product::class);
        $this->assertTrue($this->settings->isProductUnitSelectionVisible($product));
    }

    /**
     * @dataProvider productsDataProvider
     */
    public function testIsProductPrimaryUnitVisible(Product $product = null)
    {
        $this->assertTrue($this->settings->isProductPrimaryUnitVisible($product));
    }

    /**
     * @dataProvider productsDataProvider
     */
    public function testIsAddingAdditionalUnitsToProductAvailable(Product $product = null)
    {
        $this->assertTrue($this->settings->isAddingAdditionalUnitsToProductAvailable($product));
    }

    public function productsDataProvider(): array
    {
        return [
            [null],
            [$this->createMock(Product::class)],
        ];
    }

    public function testGetAvailablePrimaryUnitChoices()
    {
        $units = [
            $this->createMock(ProductUnit::class),
            $this->createMock(ProductUnit::class),
            $this->createMock(ProductUnit::class),
        ];

        $repository = $this->createMock(ProductUnitRepository::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(ProductUnit::class)
            ->willReturn($repository);

        $repository->expects($this->once())
            ->method('findAll')
            ->willReturn($units);

        $this->assertSame($units, $this->settings->getAvailablePrimaryUnitChoices());
    }
}
