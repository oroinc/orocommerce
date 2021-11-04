<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Service;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Provider\DefaultProductUnitProviderInterface;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeService;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SingleUnitModeServiceTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var SingleUnitModeService $unitModeProvider
     */
    protected $unitModeProvider;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configManager;

    /**
     * @var DefaultProductUnitProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $unitProvider;

    protected function setUp(): void
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
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

    public function testGetConfigDefaultUnit()
    {
        $defaultUnit = 'each';
        $this->configManager->expects(static::once())
            ->method('get')
            ->with('oro_product.default_unit')
            ->willReturn($defaultUnit);

        $this->assertEquals($defaultUnit, $this->unitModeProvider->getDefaultUnitCode());
    }
}
