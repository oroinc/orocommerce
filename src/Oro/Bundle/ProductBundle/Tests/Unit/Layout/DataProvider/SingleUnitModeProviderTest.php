<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Layout\DataProvider\SingleUnitModeProvider;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeService;

class SingleUnitModeProviderTest extends \PHPUnit\Framework\TestCase
{
    private const SINGLE_UNIT_MODE = true;
    private const CODE_VISIBLE = false;
    private const CONFIG_DEFAULT_UNIT = 'item';

    /** @var \PHPUnit\Framework\MockObject\MockObject|SingleUnitModeService */
    private $singleUnitService;

    /** @var SingleUnitModeProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->singleUnitService = $this->createMock(SingleUnitModeService::class);

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
