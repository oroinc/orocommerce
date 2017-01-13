<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Visibility;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeServiceInterface;
use Oro\Bundle\ProductBundle\Visibility\ProductUnitFieldsSettingsInterface;
use Oro\Bundle\ProductBundle\Visibility\SingleUnitModeProductUnitFieldsSettingsDecorator;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SingleUnitModeProductUnitFieldsSettingsDecoratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var ProductUnitFieldsSettingsInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $decoratedSettings;

    /**
     * @var SingleUnitModeServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $singleUnitService;

    /**
     * @var SingleUnitModeProductUnitFieldsSettingsDecorator
     */
    private $settings;

    public function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()->getMock();

        $this->singleUnitService = $this->getMockBuilder(SingleUnitModeServiceInterface::class)
            ->disableOriginalConstructor()->getMock();

        $this->decoratedSettings = $this->createMock(ProductUnitFieldsSettingsInterface::class);

        $this->settings = new SingleUnitModeProductUnitFieldsSettingsDecorator(
            $this->decoratedSettings,
            $this->singleUnitService,
            $this->doctrineHelper
        );
    }

    public function testIsProductUnitSelectionVisibleDefault()
    {
        /** @var Product $product */
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
        /** @var Product|\PHPUnit_Framework_MockObject_MockObject $product */
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

        $product->method('getAdditionalUnitPrecisions')
            ->willReturn(new ArrayCollection($additionalUnits));

        $this->singleUnitService->expects($this->once())
            ->method('getDefaultUnitCode')
            ->willReturn($defaultUnitCode);

        $this->assertEquals($expectedVisibility, $this->settings->isProductUnitSelectionVisible($product));
    }

    public function testIsProductPrimaryUnitVisibleDefault()
    {
        /** @var Product $product */
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
        /** @var Product|\PHPUnit_Framework_MockObject_MockObject $product */
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

        $product->method('getAdditionalUnitPrecisions')
            ->willReturn(new ArrayCollection($additionalUnits));

        $this->singleUnitService->expects($this->once())
            ->method('getDefaultUnitCode')
            ->willReturn($defaultUnitCode);

        $this->assertEquals($expectedVisibility, $this->settings->isProductPrimaryUnitVisible($product));
    }

    /**
     * @return array
     */
    public function productUnitsDataProvider()
    {
        return [
            'primary default and empty additional' => [
                'primaryUnitCode' => 'each',
                'defaultUnitCode' => 'each',
                'additionalUnits' => [],
                'expectedVisibility' => false,
            ],
            'primary not default and empty additional' => [
                'primaryUnitCode' => 'each',
                'defaultUnitCode' => 'set',
                'additionalUnits' => [],
                'expectedVisibility' => true,
            ],
            'primary default and not empty additional' => [
                'primaryUnitCode' => 'each',
                'defaultUnitCode' => 'each',
                'additionalUnits' => [$this->createMock(ProductUnitPrecision::class)],
                'expectedVisibility' => true,
            ],
            'primary not default and not empty additional' => [
                'primaryUnitCode' => 'each',
                'defaultUnitCode' => 'set',
                'additionalUnits' => [$this->createMock(ProductUnitPrecision::class)],
                'expectedVisibility' => true,
            ],
        ];
    }

    /**
     * @dataProvider productsDataProvider
     * @param Product|null $product
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

    /**
     * @return array
     */
    public function productsDataProvider()
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

        $this->assertSame([], $this->settings->getAvailablePrimaryUnitChoices());
    }

    public function testGetAvailablePrimaryUnitChoicesNullPrimary()
    {
        $product = $this->createMock(Product::class);
        $product->expects($this->once())
            ->method('getPrimaryUnitPrecision')
            ->willReturn(null);

        $this->singleUnitService->expects($this->once())
            ->method('isSingleUnitMode')
            ->willReturn(true);

        $this->decoratedSettings->expects($this->never())
            ->method('getAvailablePrimaryUnitChoices');

        $this->assertSame([], $this->settings->getAvailablePrimaryUnitChoices($product));
    }

    public function testGetAvailablePrimaryUnitChoicesNullReference()
    {
        $product = $this->createMock(Product::class);

        $unitCode = 'each';

        $unit = $this->createMock(ProductUnit::class);
        $unit->expects($this->once())
            ->method('getCode')
            ->willReturn($unitCode);

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
            ->willReturn($unitCode);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with(ProductUnit::class, $unitCode)
            ->willReturn(null);

        $this->decoratedSettings->expects($this->never())
            ->method('getAvailablePrimaryUnitChoices');
        $this->assertSame([$unit], $this->settings->getAvailablePrimaryUnitChoices($product));
    }

    public function testGetAvailablePrimaryUnitChoicesPrimaryDefault()
    {
        $product = $this->createMock(Product::class);

        $unitCode = 'each';

        $unit = $this->createMock(ProductUnit::class);
        $unit->expects($this->once())
            ->method('getCode')
            ->willReturn($unitCode);

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
            ->willReturn($unitCode);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with(ProductUnit::class, $unitCode)
            ->willReturn($unit);

        $this->decoratedSettings->expects($this->never())
            ->method('getAvailablePrimaryUnitChoices');
        $this->assertSame([$unit], $this->settings->getAvailablePrimaryUnitChoices($product));
    }

    public function testGetAvailablePrimaryUnitChoices()
    {
        $product = $this->createMock(Product::class);

        $unitCode = 'each';

        $unit = $this->createMock(ProductUnit::class);
        $unit->expects($this->once())
            ->method('getCode')
            ->willReturn($unitCode);

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

        $defaultUnitCode = 'set';

        $this->singleUnitService->expects($this->once())
            ->method('getDefaultUnitCode')
            ->willReturn($defaultUnitCode);

        $defaultUnit = $this->createMock(ProductUnit::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with(ProductUnit::class, $defaultUnitCode)
            ->willReturn($defaultUnit);

        $this->decoratedSettings->expects($this->never())
            ->method('getAvailablePrimaryUnitChoices');
        $this->assertSame([$unit, $defaultUnit], $this->settings->getAvailablePrimaryUnitChoices($product));
    }
}
