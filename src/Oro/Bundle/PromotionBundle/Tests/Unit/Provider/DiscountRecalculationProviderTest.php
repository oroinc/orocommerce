<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Provider;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\UIBundle\Route\Router;
use Oro\Bundle\PromotionBundle\Provider\DiscountRecalculationProvider;

class DiscountRecalculationProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestStack;

    /**
     * @var DiscountRecalculationProvider
     */
    protected $discountRecalculationProvider;

    protected function setUp()
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->discountRecalculationProvider = new DiscountRecalculationProvider($this->requestStack);
    }

    public function testIsRecalculationRequiredWithoutRequest()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $this->assertTrue($this->discountRecalculationProvider->isRecalculationRequired());
    }

    public function testIsRecalculationRequiredWithoutRecalculation()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(new Request([
                Router::ACTION_PARAMETER =>
                    DiscountRecalculationProvider::SAVE_WITHOUT_DISCOUNTS_RECALCULATION_INPUT_ACTION,
            ]));

        $this->assertFalse($this->discountRecalculationProvider->isRecalculationRequired());
    }

    public function testIsRecalculationRequiredWithRecalculation()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(new Request([
                Router::ACTION_PARAMETER => 'save_and_close',
            ]));

        $this->assertTrue($this->discountRecalculationProvider->isRecalculationRequired());
    }
}
