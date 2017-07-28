<?php

namespace Oro\Bundle\PromotionBundle\CouponGeneration;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CouponGenerationOptions;
use Oro\Bundle\PromotionBundle\Manager\CouponGenerationManager;
use Symfony\Component\Form\FormInterface;

/**
 * Service that handles Coupon Generation operation request
 *
 * Gets CouponGenerationType form data (filled by user) and generates coupons based on it
 */
class CouponGenerationHandler
{
    /**
     * @var CouponGenerationManager
     */
    protected $couponGenerationManager;

    /**
     * @param CouponGenerationManager $couponGenerationManager
     */
    public function __construct(CouponGenerationManager $couponGenerationManager)
    {
        $this->couponGenerationManager = $couponGenerationManager;
    }

    /**
     * Process Coupon Generation operation request
     *
     * @param FormInterface $form
     */
    public function process(FormInterface $form)
    {
        /** @var ActionData $actionData */
        $actionData = $form->getData();
        /** @var CouponGenerationOptions $couponGenerationOptions */
        $couponGenerationOptions = $actionData->get('couponGenerationOptions');
        $this->couponGenerationManager->generateCoupons($couponGenerationOptions);
    }
}
