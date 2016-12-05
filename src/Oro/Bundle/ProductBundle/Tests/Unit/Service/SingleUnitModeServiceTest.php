<?php

namespace Oro\Bundle\ProductBundle\Tests\Service;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeService;
use Oro\Component\Testing\Unit\EntityTrait;

class SingleUnitModeServiceTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var SingleUnitModeService $unitModeProvider
     */
    protected $unitModeProvider;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    public function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()->getMock();

        $this->unitModeProvider = new SingleUnitModeService($this->configManager);
    }

    public function testIsSingleUnitMode()
    {
        $singleUnitMode = true;

        $this->configManager->expects(static::once())
            ->method('get')
            ->with('oro_product.single_unit_mode')
            ->willReturn($singleUnitMode);

        $this->assertEquals($singleUnitMode, $this->unitModeProvider->isSingleUnitMode());
    }

    public function testIsSingleUnitModeCodeVisible()
    {
        $showCode = true;

        $this->configManager->expects(static::at(0))
            ->method('get')
            ->with('oro_product.single_unit_mode')
            ->willReturn(true);

        $this->configManager->expects(static::at(1))
            ->method('get')
            ->with('oro_product.single_unit_mode_show_code')
            ->willReturn($showCode);

        $this->assertEquals($showCode, $this->unitModeProvider->isSingleUnitModeCodeVisible());
    }

    public function testIsSingleUnitModeCodeVisibleMultipleUnitMode()
    {
        $this->configManager->expects(static::once())
            ->method('get')
            ->with('oro_product.single_unit_mode')
            ->willReturn(false);

        static::assertTrue($this->unitModeProvider->isSingleUnitModeCodeVisible());
    }

    public function testIsProductPrimaryUnitSingleAndDefault()
    {
        $unit = 'each';

        $this->configManager->expects(static::once())
            ->method('get')
            ->with('oro_product.default_unit')
            ->willReturn($unit);

        $product = $this->getProductWithPrimaryUnit($unit);

        static::assertTrue($this->unitModeProvider->isProductPrimaryUnitSingleAndDefault($product));
    }

    public function testIsProductPrimaryUnitSingleAndDefaultDiffUnit()
    {
        $this->configManager->expects(static::once())
            ->method('get')
            ->with('oro_product.default_unit')
            ->willReturn('each');

        $product = (new Product)->setPrimaryUnitPrecision($this->getEntity(ProductUnitPrecision::class, [
            'unit' => $this->getEntity(ProductUnit::class, [
                'code' => 'item',
            ]),
        ]));

        static::assertFalse($this->unitModeProvider->isProductPrimaryUnitSingleAndDefault($product));
    }

    public function testIsProductPrimaryUnitSingleAndDefaultAdditionalUnits()
    {
        $unit = 'each';
        $this->configManager->expects(static::once())
            ->method('get')
            ->with('oro_product.default_unit')
            ->willReturn($unit);

        $product = $this->getProductWithPrimaryUnit($unit)
            ->addAdditionalUnitPrecision($this->getEntity(ProductUnitPrecision::class, [
                'unit' => $this->getEntity(ProductUnit::class, [
                    'code' => 'item',
                ]),
            ]));

        static::assertFalse($this->unitModeProvider->isProductPrimaryUnitSingleAndDefault($product));
    }

    public function testGetConfigDefaultUnit()
    {
        $defaultUnit = 'item';

        $this->configManager->expects(static::once())
            ->method('get')
            ->with('oro_product.default_unit')
            ->willReturn($defaultUnit);

        $this->assertEquals($defaultUnit, $this->unitModeProvider->getConfigDefaultUnit());
    }

    /**
     * @param string $unitCode
     * @return Product
     */
    private function getProductWithPrimaryUnit($unitCode)
    {
        return (new Product)->setPrimaryUnitPrecision(
            $this->getEntity(ProductUnitPrecision::class, [
                'unit' => $this->getEntity(ProductUnit::class, [
                    'code' => $unitCode,
                ]),
            ])
        );
    }
}
