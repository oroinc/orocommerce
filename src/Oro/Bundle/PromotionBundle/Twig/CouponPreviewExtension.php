<?php

namespace Oro\Bundle\PromotionBundle\Twig;

use Oro\Bundle\PromotionBundle\CouponGeneration\Code\CodeGenerator;
use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CodeGenerationOptions;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CouponPreviewExtension extends \Twig_Extension
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_promotion_generate_coupon_code', [$this, 'generateCouponCode']),
        ];
    }

    /**
     * @param CodeGenerationOptions $codeGenerationOptions
     * @return string
     */
    public function generateCouponCode(CodeGenerationOptions $codeGenerationOptions)
    {
        return $this->getCouponCodeGenerator()->generateOne($codeGenerationOptions);
    }

    /**
     * @return CodeGenerator
     */
    protected function getCouponCodeGenerator()
    {
        return $this->container->get('oro_promotion.coupon_generation.code_generator');
    }
}
