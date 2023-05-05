<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Visibility;

use Oro\Bundle\ProductBundle\Service\SingleUnitModeServiceInterface;
use Oro\Bundle\ProductBundle\Visibility\SingleUnitModeUnitVisibilityDecorator;
use Oro\Bundle\ProductBundle\Visibility\UnitVisibilityInterface;

class SingleUnitModeUnitVisibilityDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var UnitVisibilityInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $decoratedVisibility;

    /** @var SingleUnitModeServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $singleUnitService;

    /** @var SingleUnitModeUnitVisibilityDecorator */
    private $visibility;

    protected function setUp(): void
    {
        $this->decoratedVisibility = $this->createMock(UnitVisibilityInterface::class);
        $this->singleUnitService = $this->createMock(SingleUnitModeServiceInterface::class);

        $this->visibility = new SingleUnitModeUnitVisibilityDecorator(
            $this->decoratedVisibility,
            $this->singleUnitService
        );
    }

    /**
     * @dataProvider isUnitCodeVisibleDataProvider
     * @param bool $codeVisibility
     */
    public function testIsUnitCodeVisibleDisabled($codeVisibility)
    {
        $code = 'each';

        $this->singleUnitService->expects($this->once())
            ->method('isSingleUnitMode')
            ->willReturn(false);

        $this->decoratedVisibility->expects($this->once())
            ->method('isUnitCodeVisible')
            ->with($code)
            ->willReturn($codeVisibility);

        $this->assertSame($codeVisibility, $this->visibility->isUnitCodeVisible($code));
    }

    public function isUnitCodeVisibleDataProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }

    public function testIsUnitCodeVisibleAllCodes()
    {
        $this->singleUnitService->expects($this->once())
            ->method('isSingleUnitMode')
            ->willReturn(true);

        $this->decoratedVisibility->expects($this->never())
            ->method('isUnitCodeVisible');

        $this->singleUnitService->expects($this->once())
            ->method('isSingleUnitModeCodeVisible')
            ->willReturn(true);

        $this->singleUnitService->expects($this->never())
            ->method('getDefaultUnitCode');

        $this->assertTrue($this->visibility->isUnitCodeVisible('each'));
    }

    public function testIsUnitCodeVisibleDefaultTrue()
    {
        $code = 'each';

        $this->singleUnitService->expects($this->once())
            ->method('isSingleUnitMode')
            ->willReturn(true);

        $this->decoratedVisibility->expects($this->never())
            ->method('isUnitCodeVisible');

        $this->singleUnitService->expects($this->once())
            ->method('isSingleUnitModeCodeVisible')
            ->willReturn(false);

        $this->singleUnitService->expects($this->once())
            ->method('getDefaultUnitCode')
            ->willReturn($code);

        $this->assertFalse($this->visibility->isUnitCodeVisible($code));
    }

    public function testIsUnitCodeVisibleDefaultFalse()
    {
        $this->singleUnitService->expects($this->once())
            ->method('isSingleUnitMode')
            ->willReturn(true);

        $this->decoratedVisibility->expects($this->never())
            ->method('isUnitCodeVisible');

        $this->singleUnitService->expects($this->once())
            ->method('isSingleUnitModeCodeVisible')
            ->willReturn(false);

        $this->singleUnitService->expects($this->once())
            ->method('getDefaultUnitCode')
            ->willReturn('kg');

        $this->assertTrue($this->visibility->isUnitCodeVisible('each'));
    }
}
