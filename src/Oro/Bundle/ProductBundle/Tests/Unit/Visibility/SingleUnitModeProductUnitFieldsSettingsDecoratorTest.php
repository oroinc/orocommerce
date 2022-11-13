<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Visibility;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Exception\DefaultUnitNotFoundException;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeServiceInterface;
use Oro\Bundle\ProductBundle\Visibility\ProductUnitFieldsSettingsInterface;
use Oro\Bundle\ProductBundle\Visibility\SingleUnitModeProductUnitFieldsSettingsDecorator;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SingleUnitModeProductUnitFieldsSettingsDecoratorTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_UNIT_CODE = 'each';
    private const TEST_DEFAULT_UNIT_CODE = 'set';

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ProductUnitFieldsSettingsInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $decoratedSettings;

    /** @var SingleUnitModeServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $singleUnitService;

    /** @var SingleUnitModeProductUnitFieldsSettingsDecorator */
    private $settings;

    /** @var ProductUnitRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $productUnitRepo;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->productUnitRepo = $this->createMock(EntityRepository::class);
        $this->singleUnitService = $this->createMock(SingleUnitModeServiceInterface::class);
        $this->decoratedSettings = $this->createMock(ProductUnitFieldsSettingsInterface::class);

        $this->doctrineHelper->expects(self::any())
            ->method('getEntityRepository')
            ->willReturn($this->productUnitRepo);

        $this->settings = new SingleUnitModeProductUnitFieldsSettingsDecorator(
            $this->decoratedSettings,
            $this->singleUnitService,
            $this->doctrineHelper
        );
    }

    public function testIsProductUnitSelectionVisibleDefault()
    {
        $product = $this->createMock(Product::class);

        $this->singleUnitService->expects($this->once())
            ->method('isSingleUnitMode')
            ->willReturn(false);

        $this->decoratedSettings->expects($this->once())
            ->method('isProductUnitSelectionVisible')
            ->with($product)
            ->willReturn(true);

        $this->assertTrue($this->settings->isProductUnitSelectionVisible($product));
    }

    /**
     * @dataProvider productUnitsDataProvider
     * @param string $primaryUnitCode
     * @param string $defaultUnitCode
     * @param array $additionalUnits
     * @param bool $expectedVisibility
     */
    public function testIsProductUnitSelectionVisible(
        $primaryUnitCode,
        $defaultUnitCode,
        array $additionalUnits,
        $expectedVisibility
    ) {
        $product = $this->createMock(Product::class);

        $this->singleUnitService->expects($this->once())
            ->method('isSingleUnitMode')
            ->willReturn(true);

        $this->decoratedSettings->expects($this->never())
            ->method('isProductUnitSelectionVisible');

        $unit = $this->createMock(ProductUnit::class);
        $unit->expects($this->once())
            ->method('getCode')
            ->willReturn($primaryUnitCode);

        $unitPrecision = $this->createMock(ProductUnitPrecision::class);
        $unitPrecision->expects($this->any())
            ->method('getUnit')
            ->willReturn($unit);

        $product->expects($this->once())
            ->method('getPrimaryUnitPrecision')
            ->willReturn($unitPrecision);

        $product->expects(self::any())
            ->method('getAdditionalUnitPrecisions')
            ->willReturn(new ArrayCollection($additionalUnits));

        $this->singleUnitService->expects($this->once())
            ->method('getDefaultUnitCode')
            ->willReturn($defaultUnitCode);

        $this->assertEquals($expectedVisibility, $this->settings->isProductUnitSelectionVisible($product));
    }

    public function testIsProductPrimaryUnitVisibleDefault()
    {
        $product = $this->createMock(Product::class);

        $this->singleUnitService->expects($this->once())
            ->method('isSingleUnitMode')
            ->willReturn(false);

        $this->decoratedSettings->expects($this->once())
            ->method('isProductPrimaryUnitVisible')
            ->with($product)
            ->willReturn(true);

        $this->assertTrue($this->settings->isProductPrimaryUnitVisible($product));
    }

    public function testIsProductPrimaryUnitVisibleNullProduct()
    {
        $this->singleUnitService->expects($this->once())
            ->method('isSingleUnitMode')
            ->willReturn(true);

        $this->decoratedSettings->expects($this->never())
            ->method('isProductPrimaryUnitVisible');

        $this->assertFalse($this->settings->isProductPrimaryUnitVisible());
    }

    /**
     * @dataProvider productUnitsDataProvider
     * @param string $primaryUnitCode
     * @param string $defaultUnitCode
     * @param array $additionalUnits
     * @param bool $expectedVisibility
     */
    public function testIsProductPrimaryUnitVisible(
        $primaryUnitCode,
        $defaultUnitCode,
        array $additionalUnits,
        $expectedVisibility
    ) {
        $product = $this->createMock(Product::class);

        $this->singleUnitService->expects($this->once())
            ->method('isSingleUnitMode')
            ->willReturn(true);

        $this->decoratedSettings->expects($this->never())
            ->method('isProductPrimaryUnitVisible');

        $unit = $this->createMock(ProductUnit::class);
        $unit->expects($this->once())
            ->method('getCode')
            ->willReturn($primaryUnitCode);

        $unitPrecision = $this->createMock(ProductUnitPrecision::class);
        $unitPrecision->expects($this->any())
            ->method('getUnit')
            ->willReturn($unit);

        $product->expects($this->once())
            ->method('getPrimaryUnitPrecision')
            ->willReturn($unitPrecision);

        $product->expects(self::any())
            ->method('getAdditionalUnitPrecisions')
            ->willReturn(new ArrayCollection($additionalUnits));

        $this->singleUnitService->expects($this->once())
            ->method('getDefaultUnitCode')
            ->willReturn($defaultUnitCode);

        $this->assertEquals($expectedVisibility, $this->settings->isProductPrimaryUnitVisible($product));
    }

    public function productUnitsDataProvider(): array
    {
        $sellAdditionalUnitPrecision = $this->createMock(ProductUnitPrecision::class);
        $sellAdditionalUnitPrecision->expects($this->once())
            ->method('isSell')
            ->willReturn(true);

        $notSellAdditionalUnitPrecision = $this->createMock(ProductUnitPrecision::class);
        $notSellAdditionalUnitPrecision->expects($this->once())
            ->method('isSell')
            ->willReturn(false);

        return [
            'primary default and empty additional' => [
                'primaryUnitCode' => self::TEST_UNIT_CODE,
                'defaultUnitCode' => self::TEST_UNIT_CODE,
                'additionalUnits' => [],
                'expectedVisibility' => false,
            ],
            'primary not default and empty additional' => [
                'primaryUnitCode' => self::TEST_UNIT_CODE,
                'defaultUnitCode' => self::TEST_DEFAULT_UNIT_CODE,
                'additionalUnits' => [],
                'expectedVisibility' => true,
            ],
            'primary default and not empty additional' => [
                'primaryUnitCode' => self::TEST_UNIT_CODE,
                'defaultUnitCode' => self::TEST_UNIT_CODE,
                'additionalUnits' => [$sellAdditionalUnitPrecision],
                'expectedVisibility' => true,
            ],
            'primary not default and not empty additional' => [
                'primaryUnitCode' => self::TEST_UNIT_CODE,
                'defaultUnitCode' => self::TEST_DEFAULT_UNIT_CODE,
                'additionalUnits' => [$sellAdditionalUnitPrecision],
                'expectedVisibility' => true,
            ],
            'primary default and not empty not sell additional' => [
                'primaryUnitCode' => 'each',
                'defaultUnitCode' => 'each',
                'additionalUnits' => [$notSellAdditionalUnitPrecision],
                'expectedVisibility' => false,
            ],
        ];
    }

    /**
     * @dataProvider productsDataProvider
     */
    public function testIsAddingAdditionalUnitsToProductAvailable(Product $product = null)
    {
        $this->singleUnitService->expects($this->once())
            ->method('isSingleUnitMode')
            ->willReturn(true);

        $this->decoratedSettings->expects($this->never())
            ->method('isAddingAdditionalUnitsToProductAvailable');

        $this->assertFalse($this->settings->isAddingAdditionalUnitsToProductAvailable($product));
    }

    public function productsDataProvider(): array
    {
        return [
            [null],
            [$this->createMock(Product::class)],
        ];
    }

    public function testIsAddingAdditionalUnitsToProductAvailableFalse()
    {
        $product = $this->createMock(Product::class);
        $this->singleUnitService->expects($this->once())
            ->method('isSingleUnitMode')
            ->willReturn(false);

        $this->decoratedSettings->expects($this->once())
            ->method('isAddingAdditionalUnitsToProductAvailable')
            ->with($product)
            ->willReturn(true);

        $this->assertTrue($this->settings->isAddingAdditionalUnitsToProductAvailable($product));
    }

    public function testGetAvailablePrimaryUnitChoicesFalse()
    {
        $product = $this->createMock(Product::class);

        $units = [
            $this->createMock(ProductUnit::class),
            $this->createMock(ProductUnit::class),
            $this->createMock(ProductUnit::class),
        ];

        $this->singleUnitService->expects($this->once())
            ->method('isSingleUnitMode')
            ->willReturn(false);

        $this->decoratedSettings->expects($this->once())
            ->method('getAvailablePrimaryUnitChoices')
            ->with($product)
            ->willReturn($units);

        $this->assertSame($units, $this->settings->getAvailablePrimaryUnitChoices($product));
    }

    public function testGetAvailablePrimaryUnitChoicesNull()
    {
        $this->singleUnitService->expects($this->once())
            ->method('isSingleUnitMode')
            ->willReturn(true);

        $this->decoratedSettings->expects($this->never())
            ->method('getAvailablePrimaryUnitChoices');
        $this->expectException(DefaultUnitNotFoundException::class);

        $this->assertSame([], $this->settings->getAvailablePrimaryUnitChoices());
    }

    public function testGetAvailablePrimaryUnitChoicesNullPrimary()
    {
        $this->expectException(DefaultUnitNotFoundException::class);
        $product = $this->createMock(Product::class);
        $product->expects($this->never())
            ->method('getPrimaryUnitPrecision')
            ->willReturn(null);

        $this->singleUnitService->expects($this->once())
            ->method('isSingleUnitMode')
            ->willReturn(true);

        $this->decoratedSettings->expects($this->never())
            ->method('getAvailablePrimaryUnitChoices');

        $this->assertSame([], $this->settings->getAvailablePrimaryUnitChoices($product));
    }

    public function testGetAvailablePrimaryUnitChoicesPrimaryDefault()
    {
        [$product, $unit] = $this->prepareProduct(self::TEST_UNIT_CODE);

        $this->productUnitRepo->expects($this->once())
            ->method('find')
            ->with(self::TEST_UNIT_CODE)
            ->willReturn($unit);

        $this->decoratedSettings->expects($this->never())
            ->method('getAvailablePrimaryUnitChoices');
        $this->assertSame([$unit], $this->settings->getAvailablePrimaryUnitChoices($product));
    }

    public function testGetAvailablePrimaryUnitChoices()
    {
        [$product, $unit] = $this->prepareProduct(self::TEST_DEFAULT_UNIT_CODE);

        $defaultUnit = $this->createMock(ProductUnit::class);

        $this->productUnitRepo->expects($this->once())
            ->method('find')
            ->with(self::TEST_DEFAULT_UNIT_CODE)
            ->willReturn($defaultUnit);

        $this->decoratedSettings->expects($this->never())
            ->method('getAvailablePrimaryUnitChoices');
        $this->assertSame([$defaultUnit, $unit], $this->settings->getAvailablePrimaryUnitChoices($product));
    }

    private function prepareProduct(string $defaultUnitCode): array
    {
        $product = $this->createMock(Product::class);

        $unit = $this->createMock(ProductUnit::class);
        $unit->expects($this->once())
            ->method('getCode')
            ->willReturn(self::TEST_UNIT_CODE);

        $unitPrecision = $this->createMock(ProductUnitPrecision::class);
        $unitPrecision->expects($this->any())
            ->method('getUnit')
            ->willReturn($unit);

        $product->expects($this->once())
            ->method('getPrimaryUnitPrecision')
            ->willReturn($unitPrecision);

        $this->singleUnitService->expects($this->once())
            ->method('isSingleUnitMode')
            ->willReturn(true);

        $this->singleUnitService->expects($this->once())
            ->method('getDefaultUnitCode')
            ->willReturn($defaultUnitCode);

        return [$product, $unit];
    }
}
