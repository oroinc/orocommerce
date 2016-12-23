<?php

namespace Oro\Bundle\ProductBundle\Tests\Service;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Provider\DefaultProductUnitProviderInterface;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeService;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;

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

    /**
     * @var DefaultProductUnitProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $unitProvider;

    public function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()->getMock();

        $this->unitProvider = $this->getMockBuilder(DefaultProductUnitProviderInterface::class)
            ->disableOriginalConstructor()->getMock();

        $this->unitModeProvider = new SingleUnitModeService($this->configManager, $this->unitProvider);
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

        $product = $this->getProductWithPrimaryUnit('item');

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
        $defaultUnit = $this->createMock(ProductUnit::class);
        $defaultUnitPrecision = $this->createMock(ProductUnitPrecision::class);
        $defaultUnitPrecision->expects(static::once())
            ->method('getUnit')
            ->willReturn($defaultUnit);

        $this->unitProvider->expects(static::once())
            ->method('getDefaultProductUnitPrecision')
            ->willReturn($defaultUnitPrecision);

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

    public function testIsProductPrimaryUnitDefault()
    {
        $this->configManager->expects(static::once())
            ->method('get')
            ->with('oro_product.default_unit')
            ->willReturn('each');

        $product = (new Product)->setPrimaryUnitPrecision($this->getEntity(ProductUnitPrecision::class, [
            'unit' => $this->getEntity(ProductUnit::class, [
                'code' => 'each',
            ]),
        ]));

        static::assertTrue($this->unitModeProvider->isProductPrimaryUnitDefault($product));
    }

    public function testIsDefaultPrimaryUnit()
    {
        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->with('oro_product.default_unit')
            ->willReturn('each');

        static::assertTrue($this->unitModeProvider->isDefaultPrimaryUnit('each'));
        static::assertFalse($this->unitModeProvider->isDefaultPrimaryUnit('otherUnit'));
    }
}
