<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Extension\DateTimeExtension;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Form\Type\CouponType;
use Oro\Bundle\PromotionBundle\Form\Type\PromotionSelectType;
use Oro\Bundle\PromotionBundle\Form\Type\PromotionType;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class CouponTypeTest extends FormIntegrationTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    PromotionSelectType::class => new EntityTypeStub(
                        [
                            'promotion1' => $this->getPromotion(1),
                            'promotion2' => $this->getPromotion(2)
                        ],
                        [
                            'autocomplete_alias' => PromotionType::class,
                            'grid_name' => 'promotion-for-coupons-select-grid',
                        ]
                    ),
                    new OroDateTimeType(),
                ],
                [
                    FormType::class => [new TooltipFormExtensionStub($this)],
                    DateTimeType::class => [new DateTimeExtension()]
                ]
            ),
        ];
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(array $submittedData, Coupon $expectedData)
    {
        $form = $this->factory->create(CouponType::class);
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        /** @var Coupon $data */
        $data = $form->getData();

        $this->assertEquals($expectedData, $data);
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(CouponType::class, $this->createCoupon('test'));

        $this->assertTrue($form->has('code'));
        $this->assertTrue($form->has('promotion'));
        $this->assertTrue($form->has('enabled'));
        $this->assertTrue($form->has('usesPerPerson'));
        $this->assertTrue($form->has('usesPerCoupon'));
        $this->assertTrue($form->has('validUntil'));
    }

    public function submitProvider(): array
    {
        $validFromDate = '2010-01-01T12:00:00Z';
        $validUntilDate = '2020-01-01T12:00:00Z';

        return [
            'coupon with promotion' => [
                'submittedData' => [
                    'code' => 'test1234',
                    'enabled' => false,
                    'promotion' => 'promotion2',
                    'usesPerPerson' => 2,
                    'usesPerCoupon' => 3,
                    'validFrom' => $validFromDate,
                    'validUntil' => $validUntilDate,
                ],
                'expectedData' => $this->createCoupon(
                    'test1234',
                    false,
                    2,
                    3,
                    $this->getPromotion(2),
                    new \DateTime($validFromDate),
                    new \DateTime($validUntilDate)
                ),
            ],
            'coupon without promotion' => [
                'submittedData' => [
                    'code' => 'test1234',
                    'enabled' => true,
                    'promotion' => null,
                    'usesPerPerson' => 2,
                    'usesPerCoupon' => 3,
                    'validUntil' => null,
                ],
                'expectedData' => $this->createCoupon('test1234', true, 2, 3),
            ],
        ];
    }

    private function createCoupon(
        string $couponCode,
        bool $enabled = false,
        int $usesPerPerson = null,
        int $usesPerCoupon = null,
        Promotion $promotion = null,
        \DateTime $validFrom = null,
        \DateTime $validUntil = null
    ): Coupon {
        return (new Coupon())
            ->setCode($couponCode)
            ->setEnabled($enabled)
            ->setUsesPerPerson($usesPerPerson)
            ->setUsesPerCoupon($usesPerCoupon)
            ->setPromotion($promotion)
            ->setValidFrom($validFrom)
            ->setValidUntil($validUntil);
    }

    private function getPromotion(int $id): Promotion
    {
        $promotion = new Promotion();
        ReflectionUtil::setId($promotion, $id);

        return $promotion;
    }
}
