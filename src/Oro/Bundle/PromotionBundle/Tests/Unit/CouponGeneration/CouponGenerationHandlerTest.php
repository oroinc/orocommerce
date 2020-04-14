<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\CouponGeneration;

use Oro\Bundle\PromotionBundle\CouponGeneration\Coupon\CouponGeneratorInterface;
use Oro\Bundle\PromotionBundle\CouponGeneration\CouponGenerationHandler;
use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CouponGenerationOptions;

class CouponGenerationHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CouponGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $couponGenerator;

    /**
     * @var CouponGenerationHandler
     */
    protected $couponGenerationHandler;

    protected function setUp(): void
    {
        $this->couponGenerator = $this->createMock(CouponGeneratorInterface::class);
        $this->couponGenerationHandler = new CouponGenerationHandler($this->couponGenerator);
    }

    public function testProcess()
    {
        $options = new CouponGenerationOptions();

        $this->couponGenerator
            ->expects($this->once())
            ->method('generateAndSave')
            ->with($this->identicalTo($options));

        $this->couponGenerationHandler->process($options);
    }
}
