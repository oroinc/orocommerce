<?php

namespace Oro\Bundle\PromotionBundle\Twig;

use Oro\Bundle\PromotionBundle\CouponGeneration\Code\CodeGenerator;
use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CodeGenerationOptions;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to generate a coupon code:
 *   - oro_promotion_generate_coupon_code
 */
class CouponPreviewExtension extends AbstractExtension
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
            new TwigFunction('oro_promotion_generate_coupon_code', [$this, 'generateCouponCode']),
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
