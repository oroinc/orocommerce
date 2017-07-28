<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Manager;

use Oro\Bundle\PromotionBundle\CouponGeneration\Generator\CouponGeneratorInterface;
use Oro\Bundle\PromotionBundle\CouponGeneration\Inserter\CouponInserterInterface;
use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CouponGenerationOptions;
use Oro\Bundle\PromotionBundle\Manager\CouponGenerationManager;

class CouponGenerationManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CouponGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $couponGenerator;

    /**
     * @var CouponInserterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $couponInserter;

    /**
     * @var CouponGenerationManager
     */
    protected $couponGenerationManager;

    protected function setUp()
    {
        $this->couponGenerator = $this->createMock(CouponGeneratorInterface::class);
        $this->couponInserter = $this->createMock(CouponInserterInterface::class);
        $this->couponGenerationManager = new CouponGenerationManager($this->couponGenerator, $this->couponInserter);
    }

    public function testGenerateCoupons()
    {
        /** @var CouponGenerationOptions|\PHPUnit_Framework_MockObject_MockObject $couponGenerationOptions **/
        $couponGenerationOptions = $this->createMock(CouponGenerationOptions::class);

        //TODO: add test assertions
        $this->couponGenerationManager->generateCoupons($couponGenerationOptions);
    }
}
