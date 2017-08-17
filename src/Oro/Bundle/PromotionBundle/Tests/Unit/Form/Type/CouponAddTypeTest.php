<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Form\Type\CouponAddType;
use Oro\Bundle\PromotionBundle\Form\Type\CouponAutocompleteType;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityIdentifierType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class CouponAddTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @var CouponAddType
     */
    protected $formType;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new CouponAddType();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigProvider $configProvider */
        $configProvider = $this->createMock(ConfigProvider::class);
        /** @var \PHPUnit_Framework_MockObject_MockObject|Translator $translator */
        $translator = $this->createMock(Translator::class);
        /** @var Coupon $coupon */
        $coupon1 = $this->getEntity(Coupon::class, ['id' => 1]);

        /** @var Coupon $coupon */
        $coupon2 = $this->getEntity(Coupon::class, ['id' => 2]);

        return [
            new PreloadedExtension(
                [
                    new EntityType(['coupon1' => $coupon1], CouponAutocompleteType::NAME),
                    new EntityIdentifierType([1 => $coupon1, 2 => $coupon2]),
                ],
                [
                    'form' => [
                        new TooltipFormExtension($configProvider, $translator),
                    ],
                ]
            ),
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->formType);

        $this->assertTrue($form->has('coupon'));
        $this->assertTrue($form->has('addedCoupons'));
    }

    public function testGetName()
    {
        $this->assertEquals(CouponAddType::NAME, $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(CouponAddType::NAME, $this->formType->getBlockPrefix());
    }

    /**
     * @dataProvider submitProvider
     *
     * @param array $submittedData
     * @param array $expectedData
     */
    public function testSubmit(array $submittedData, array $expectedData)
    {
        $form = $this->factory->create($this->formType);
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'empty data' => [
                'submittedData' => [
                    'coupon' => 'coupon1',
                    'addedCoupons' => '',
                ],
                'expectedData' => [],
            ],
            'two coupons added' => [
                'submittedData' => [
                    'coupon' => '',
                    'addedCoupons' => [1, 2],
                ],
                'expectedData' => [
                    $this->getEntity(Coupon::class, ['id' => 1]),
                    $this->getEntity(Coupon::class, ['id' => 2])
                ],
            ]
        ];
    }
}
