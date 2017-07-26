<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\PromotionBundle\Form\Type\CouponGenerationType;
use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CouponGenerationParams;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class CouponGenerationTypeTest extends FormIntegrationTestCase
{
    /**
     * @var TokenAccessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tokenAccessor;

    /**
     * @var CouponGenerationType
     */
    protected $couponGenerationType;

    protected function setUp()
    {
        parent::setUp();
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->couponGenerationType = new CouponGenerationType($this->tokenAccessor);
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver * */
        $resolver = $this->createMock(OptionsResolver::class);

        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => CouponGenerationParams::class
                ]
            );
        $this->couponGenerationType->configureOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(CouponGenerationType::NAME, $this->couponGenerationType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(CouponGenerationType::NAME, $this->couponGenerationType->getBlockPrefix());
    }
}
