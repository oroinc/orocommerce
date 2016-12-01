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

    public function testIsSingleUnitMode()
    {
        $provider = $this->getSingleUnitModeProvider();

        $this->assertSame(self::SINGLE_UNIT_MODE, $provider->isSingleUnitMode());
    }

    public function testIsSingleUnitModeCodeVisible()
    {
        $provider = $this->getSingleUnitModeProvider();

        $this->assertSame(self::CODE_VISIBLE, $provider->isSingleUnitModeCodeVisible());
    }

    public function testIsProductPrimaryUnitSingleAndDefault()
    {
        $provider = $this->getSingleUnitModeProvider();

        $this->assertSame(
            self::PRODUCT_PRIMARY_UNIT,
            $provider->isProductPrimaryUnitSingleAndDefault(new Product())
        );
    }

    /**
     * @return SingleUnitModeProvider
     */
    private function getSingleUnitModeProvider()
    {
        return new SingleUnitModeProvider($this->getTestSingleUnitService());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SingleUnitModeService
     */
    private function getTestSingleUnitService()
    {
        $service = $this->getMockBuilder('Oro\Bundle\ProductBundle\Service\SingleUnitModeService')
            ->disableOriginalConstructor()
            ->getMock();

        $service->expects($this->any())
            ->method('isSingleUnitMode')
            ->willReturn(self::SINGLE_UNIT_MODE);

        $service->expects($this->any())
            ->method('isSingleUnitModeCodeVisible')
            ->willReturn(self::CODE_VISIBLE);

        $service->expects($this->any())
            ->method('isProductPrimaryUnitSingleAndDefault')
            ->willReturn(self::PRODUCT_PRIMARY_UNIT);

        return $service;
    }
}
