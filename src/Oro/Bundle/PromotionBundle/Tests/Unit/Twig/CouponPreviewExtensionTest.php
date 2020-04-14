<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Twig;

use Oro\Bundle\PromotionBundle\CouponGeneration\Code\CodeGenerator;
use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CodeGenerationOptions;
use Oro\Bundle\PromotionBundle\Twig\CouponPreviewExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class CouponPreviewExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /**
     * @var CouponPreviewExtension
     */
    protected $couponPreviewExtension;

    /**
     * @var CodeGenerator|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $codeGenerator;

    protected function setUp(): void
    {
        $this->codeGenerator = $this->createMock(CodeGenerator::class);

        $container = $this->getContainerBuilder()
            ->add('oro_promotion.coupon_generation.code_generator', $this->codeGenerator)
            ->getContainer($this);

        $this->couponPreviewExtension = new CouponPreviewExtension($container);
    }

    public function testGenerateCouponCode()
    {
        $options = new CodeGenerationOptions();

        $this->codeGenerator->expects($this->once())
            ->method('generateOne')
            ->with($options)
            ->willReturn('coupon-code');

        self::callTwigFunction($this->couponPreviewExtension, 'oro_promotion_generate_coupon_code', [$options]);
    }
}
