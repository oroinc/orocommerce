<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\SingleUnitModeProvider;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeService;

class SingleUnitModeProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @internal */
    const SINGLE_UNIT_MODE = true;

    /** @internal */
    const CODE_VISIBLE = false;

    /** @internal */
    const PRODUCT_PRIMARY_UNIT = true;

    /** @var SingleUnitModeProvider */
    private $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|SingleUnitModeService */
    private $singleUnitService;

    public function setUp()
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

    public function testIsProductPrimaryUnitSingleAndDefault()
    {
        $this->singleUnitService->expects($this->once())
            ->method('isProductPrimaryUnitSingleAndDefault')
            ->willReturn(self::PRODUCT_PRIMARY_UNIT);

        $this->assertSame(
            self::PRODUCT_PRIMARY_UNIT,
            $this->provider->isProductPrimaryUnitSingleAndDefault(new Product())
        );
    }
}
