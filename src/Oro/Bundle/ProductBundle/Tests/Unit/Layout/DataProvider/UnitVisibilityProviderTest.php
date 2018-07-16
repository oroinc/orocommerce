<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Visibility\UnitVisibilityInterface;

class UnitVisibilityProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UnitVisibilityInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $unitVisibility;

    /**
     * @var UnitVisibilityProvider
     */
    private $provider;

    public function setUp()
    {
        $this->unitVisibility = $this->createMock(UnitVisibilityInterface::class);
        $this->provider = new UnitVisibilityProvider($this->unitVisibility);
    }

    public function testIsUnitCodeVisible()
    {
        $code = 'each';

        $this->unitVisibility->expects($this->once())
            ->method('isUnitCodeVisible')
            ->with($code)
            ->willReturn(true);

        $this->assertTrue($this->provider->isUnitCodeVisible($code));
    }
}
