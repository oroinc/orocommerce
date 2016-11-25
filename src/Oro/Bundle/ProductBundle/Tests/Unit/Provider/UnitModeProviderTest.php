<?php

namespace Oro\Bundle\ProductBundle\Tests\UnitProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Provider\UnitModeProvider;

class UnitModeProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UnitModeProvider $unitModeProvider
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

        $this->unitModeProvider = new UnitModeProvider($this->configManager);
    }

    public function testIsSingleUnitMode()
    {
        $single_unit_mode = true;
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_product.single_unit_mode')
            ->willReturn($single_unit_mode);

        $this->assertEquals($single_unit_mode, $this->unitModeProvider->isSingleUnitMode());
    }

    public function testIsSingleUnitModeCodeVisible()
    {
        $single_unit_mode = true;
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_product.single_unit_mode_show_code')
            ->willReturn($single_unit_mode);

        $this->assertEquals($single_unit_mode, $this->unitModeProvider->isSingleUnitModeCodeVisible());
    }
}
