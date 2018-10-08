<?php

namespace Oro\Bundle\CatalogBundle\Visibility;

use Oro\Bundle\ProductBundle\Service\SingleUnitModeServiceInterface;

class SingleUnitCategoryDefaultProductUnitOptionsVisibilityDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CategoryDefaultProductUnitOptionsVisibilityInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $optionsVisibility;

    /**
     * @var SingleUnitModeServiceInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $singleUnitModeService;

    /**
     * @var SingleUnitCategoryDefaultProductUnitOptionsVisibilityDecorator
     */
    private $decorator;

    public function setUp()
    {
        $this->optionsVisibility = $this->createMock(CategoryDefaultProductUnitOptionsVisibilityInterface::class);
        $this->singleUnitModeService = $this->createMock(SingleUnitModeServiceInterface::class);

        $this->decorator = new SingleUnitCategoryDefaultProductUnitOptionsVisibilityDecorator(
            $this->optionsVisibility,
            $this->singleUnitModeService
        );
    }

    public function testIsDefaultUnitPrecisionSelectionAvailable()
    {
        $this->singleUnitModeService->expects($this->once())
            ->method('isSingleUnitMode')
            ->willReturn(true);

        $this->optionsVisibility->expects($this->never())
            ->method('isDefaultUnitPrecisionSelectionAvailable');

        $this->assertFalse($this->decorator->isDefaultUnitPrecisionSelectionAvailable());
    }

    public function testIsDefaultUnitPrecisionSelectionAvailableFalse()
    {
        $this->singleUnitModeService->expects($this->once())
            ->method('isSingleUnitMode')
            ->willReturn(false);

        $this->optionsVisibility->expects($this->once())
            ->method('isDefaultUnitPrecisionSelectionAvailable')
            ->willReturn(true);

        $this->assertTrue($this->decorator->isDefaultUnitPrecisionSelectionAvailable());
    }
}
