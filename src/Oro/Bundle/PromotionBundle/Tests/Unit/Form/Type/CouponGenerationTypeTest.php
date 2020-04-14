<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\PromotionBundle\Form\Type\CouponGenerationType;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class CouponGenerationTypeTest extends FormIntegrationTestCase
{
    /**
     * @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $tokenAccessor;

    /**
     * @var CouponGenerationType
     */
    protected $couponGenerationType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->couponGenerationType = new CouponGenerationType($this->tokenAccessor);
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(CouponGenerationType::NAME, $this->couponGenerationType->getBlockPrefix());
    }
}
