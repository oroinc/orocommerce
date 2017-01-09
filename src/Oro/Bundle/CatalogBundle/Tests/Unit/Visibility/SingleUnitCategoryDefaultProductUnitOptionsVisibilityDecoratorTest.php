<?php

namespace Oro\Bundle\CatalogBundle\Visibility;

use Oro\Bundle\ProductBundle\Service\SingleUnitModeServiceInterface;

class SingleUnitCategoryDefaultProductUnitOptionsVisibilityDecoratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CategoryDefaultProductUnitOptionsVisibilityInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $optionsVisibility;

    /**
     * @var SingleUnitModeServiceInterface|\PHPUnit_Framework_MockObject_MockObject
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
