<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Service;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeService;

class SingleUnitModeServiceTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var SingleUnitModeService */
    private $unitModeProvider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->unitModeProvider = new SingleUnitModeService($this->configManager);
    }

    public function testIsSingleUnitMode()
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_product.single_unit_mode')
            ->willReturn(true);

        self::assertTrue($this->unitModeProvider->isSingleUnitMode());
    }

    public function testIsSingleUnitModeCodeVisible()
    {
        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(
                ['oro_product.single_unit_mode'],
                ['oro_product.single_unit_mode_show_code']
            )
            ->willReturn(true);

        self::assertTrue($this->unitModeProvider->isSingleUnitModeCodeVisible());
    }

    public function testIsSingleUnitModeCodeVisibleMultipleUnitMode()
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_product.single_unit_mode')
            ->willReturn(false);

        self::assertTrue($this->unitModeProvider->isSingleUnitModeCodeVisible());
    }

    public function testGetConfigDefaultUnit()
    {
        $defaultUnit = 'each';
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_product.default_unit')
            ->willReturn($defaultUnit);

        self::assertEquals($defaultUnit, $this->unitModeProvider->getDefaultUnitCode());
    }
}
