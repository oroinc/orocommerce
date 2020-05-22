<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Layout\DataProvider\SingleUnitModeProvider;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeService;

class SingleUnitModeProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @internal */
    const SINGLE_UNIT_MODE = true;

    /** @internal */
    const CODE_VISIBLE = false;

    /** @internal */
    const CONFIG_DEFAULT_UNIT = 'item';

    /** @var SingleUnitModeProvider */
    private $provider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|SingleUnitModeService */
    private $singleUnitService;

    protected function setUp(): void
    {
        $this->singleUnitService = $this->getMockBuilder('Oro\Bundle\ProductBundle\Service\SingleUnitModeService')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new SingleUnitModeProvider($this->singleUnitService);
    }

    public function testIsSingleUnitMode()
    {
        $this->singleUnitService->expects($this->once())
            ->method('isSingleUnitMode')
            ->willReturn(self::SINGLE_UNIT_MODE);

        $this->assertSame(self::SINGLE_UNIT_MODE, $this->provider->isSingleUnitMode());
    }

    public function testIsSingleUnitModeCodeVisible()
    {
        $this->singleUnitService->expects($this->once())
            ->method('isSingleUnitModeCodeVisible')
            ->willReturn(self::CODE_VISIBLE);

        $this->assertSame(self::CODE_VISIBLE, $this->provider->isSingleUnitModeCodeVisible());
    }

    public function testGetConfigDefaultUnit()
    {
        $this->singleUnitService->expects($this->once())
            ->method('getDefaultUnitCode')
            ->willReturn(self::CONFIG_DEFAULT_UNIT);

        $this->assertSame(
            self::CONFIG_DEFAULT_UNIT,
            $this->provider->getDefaultUnitCode()
        );
    }
}
