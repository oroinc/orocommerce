<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\CouponGeneration;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CouponGenerationOptions;
use Oro\Bundle\PromotionBundle\Manager\CouponGenerationManager;
use Oro\Bundle\PromotionBundle\CouponGeneration\CouponGenerationHandler;

use Symfony\Component\Form\FormInterface;

class CouponGenerationHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CouponGenerationManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $couponGenerationManager;

    /**
     * @var CouponGenerationHandler
     */
    protected $couponGenerationHandler;

    protected function setUp()
    {
        $this->couponGenerationManager = $this->getMockBuilder(CouponGenerationManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->couponGenerationHandler = new CouponGenerationHandler($this->couponGenerationManager);
    }

    public function testProcess()
    {
        /** @var CouponGenerationOptions|\PHPUnit_Framework_MockObject_MockObject $couponGenerationOptions */
        $couponGenerationOptions = $this->createMock(CouponGenerationOptions::class);

        /** @var ActionData|\PHPUnit_Framework_MockObject_MockObject $actionData */
        $actionData = $this->createMock(ActionData::class);
        $actionData
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('couponGenerationOptions'))
            ->willReturn($couponGenerationOptions)
        ;

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form **/
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('getData')->willReturn($actionData);

        $this->couponGenerationManager
            ->expects($this->once())
            ->method('generateCoupons')
            ->with($this->identicalTo($couponGenerationOptions))
        ;

        $this->couponGenerationHandler->process($form);
    }
}
