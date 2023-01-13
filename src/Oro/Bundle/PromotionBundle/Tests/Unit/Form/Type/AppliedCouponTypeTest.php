<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Form\Type\AppliedCouponType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

class AppliedCouponTypeTest extends FormIntegrationTestCase
{
    public function testBuildForm()
    {
        $form = $this->factory->create(AppliedCouponType::class);

        $this->assertTrue($form->has('couponCode'));
        $this->assertTrue($form->has('sourcePromotionId'));
        $this->assertTrue($form->has('sourceCouponId'));
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(AppliedCoupon $defaultData, array $submittedData, AppliedCoupon $expectedData)
    {
        $form = $this->factory->create(AppliedCouponType::class, $defaultData);
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitProvider(): array
    {
        return [
            'new data' => [
                'defaultData' => new AppliedCoupon(),
                'submittedData' => [
                    'couponCode' => 'code',
                    'sourcePromotionId' => 1,
                    'sourceCouponId' => 2,
                ],
                'expectedData' => (new AppliedCoupon())
                    ->setCouponCode('code')
                    ->setSourcePromotionId(1)
                    ->setSourceCouponId(2),
            ],
            'update data' => [
                'defaultData' => (new AppliedCoupon())
                    ->setCouponCode('code')
                    ->setSourcePromotionId(1)
                    ->setSourceCouponId(2),
                'submittedData' => [
                    'couponCode' => 'new-code',
                    'sourcePromotionId' => 333,
                    'sourceCouponId' => 555,
                ],
                'expectedData' => (new AppliedCoupon())
                    ->setCouponCode('new-code')
                    ->setSourcePromotionId(333)
                    ->setSourceCouponId(555),
            ]
        ];
    }

    public function testDefaultOptions()
    {
        $form = $this->factory->create(AppliedCouponType::class);

        $this->assertSame(AppliedCoupon::class, $form->getConfig()->getOptions()['data_class']);
    }

    public function testGetName()
    {
        $formType = new AppliedCouponType();
        $this->assertEquals(AppliedCouponType::NAME, $formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $formType = new AppliedCouponType();
        $this->assertEquals(AppliedCouponType::NAME, $formType->getBlockPrefix());
    }
}
