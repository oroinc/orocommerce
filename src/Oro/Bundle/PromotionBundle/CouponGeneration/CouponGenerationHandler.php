<?php

namespace Oro\Bundle\PromotionBundle\CouponGeneration;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\PromotionBundle\CouponGeneration\Coupon\CouponGeneratorInterface;
use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CouponGenerationOptions;
use Symfony\Component\Form\FormInterface;

/**
 * Service that handles Coupon Generation operation request
 *
 * Gets CouponGenerationType form data (filled by user) and generates coupons based on it
 */
class CouponGenerationHandler
{
    /**
     * @var CouponGeneratorInterface
     */
    protected $generator;

    /**
     * @param CouponGeneratorInterface $generator
     */
    public function __construct(CouponGeneratorInterface $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Process Coupon Generation operation request
     *
     * @param CouponGenerationOptions $options
     */
    public function process(CouponGenerationOptions $options)
    {
        $this->generator->generateAndSave($options);
    }
}
