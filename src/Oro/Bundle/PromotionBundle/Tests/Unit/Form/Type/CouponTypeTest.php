<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\DateTimeExtension;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Form\Type\CouponType;
use Oro\Bundle\PromotionBundle\Form\Type\PromotionSelectType;
use Oro\Bundle\PromotionBundle\Form\Type\PromotionType;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class CouponTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * {@inheritDoc}
     */
    protected function getExtensions()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigProvider $configProvider */
        $configProvider = $this->createMock(ConfigProvider::class);
        /** @var \PHPUnit\Framework\MockObject\MockObject|Translator $translator */
        $translator = $this->createMock(Translator::class);

        $promotionSelectType = new EntityType(
            [
                'promotion1' => $this->getEntity(Promotion::class, ['id' => 1]),
                'promotion2' => $this->getEntity(Promotion::class, ['id' => 2]),
            ],
            PromotionSelectType::NAME,
            [
                'autocomplete_alias' => PromotionType::class,
                'grid_name' => 'promotion-for-coupons-select-grid',
            ]
        );

        return [
            new PreloadedExtension(
                [
                    PromotionSelectType::class => $promotionSelectType,
                    OroDateTimeType::class => new OroDateTimeType(),
                ],
                [
                    FormType::class => [
                        new TooltipFormExtension($configProvider, $translator),
                    ],
                    DateTimeType::class => [
                        new DateTimeExtension()
                    ]
                ]
            ),
        ];
    }

    /**
     * @dataProvider submitProvider
     *
     * @param array $submittedData
     * @param Coupon $expectedData
     */
    public function testSubmit($submittedData, Coupon $expectedData)
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

    /**
     * @return array
     */
    public function submitProvider()
    {
        $promotion2 = $this->getEntity(Promotion::class, ['id' => 2]);
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
                    $promotion2,
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

    /**
     * @param string $couponCode
     * @param bool $enabled
     * @param int|null $usesPerPerson
     * @param int|null $usesPerCoupon
     * @param Promotion|null $promotion
     * @param \DateTime|null $validFrom
     * @param \DateTime|null $validUntil
     * @return Coupon
     */
    public function createCoupon(
        $couponCode,
        $enabled = false,
        $usesPerPerson = null,
        $usesPerCoupon = null,
        $promotion = null,
        \DateTime $validFrom = null,
        \DateTime $validUntil = null
    ) {
        return (new Coupon())
            ->setCode($couponCode)
            ->setEnabled($enabled)
            ->setUsesPerPerson($usesPerPerson)
            ->setUsesPerCoupon($usesPerCoupon)
            ->setPromotion($promotion)
            ->setValidFrom($validFrom)
            ->setValidUntil($validUntil);
    }
}
