<?php

namespace Oro\Bundle\PromotionBundle\Twig;

use Oro\Bundle\PromotionBundle\CouponGeneration\Code\CodeGeneratorInterface;
use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CodeGenerationOptions;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to generate a coupon code:
 *   - oro_promotion_generate_coupon_code
 */
class CouponPreviewExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

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
     * @return CodeGeneratorInterface
     */
    protected function getCouponCodeGenerator()
    {
        return $this->container->get('oro_promotion.coupon_generation.code_generator');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_promotion.coupon_generation.code_generator' => CodeGeneratorInterface::class,
        ];
    }
}
