<?php

namespace Oro\Bundle\CouponBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CouponBundle\Entity\Coupon;
use Oro\Bundle\CouponBundle\Form\Type\CouponType;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\TranslationBundle\Translation\Translator;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CouponTypeTest extends FormIntegrationTestCase
{
    /**
     * @var CouponType
     */
    protected $formType;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new CouponType();
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

        return [
            new PreloadedExtension(
                [],
                [
                    'form' => [
                        new TooltipFormExtension($configProvider, $translator),
                    ]
                ]
            )
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
        $form = $this->factory->create($this->formType);
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        /** @var Coupon $data */
        $data = $form->getData();

        $this->assertEquals($expectedData, $data);
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->formType, $this->createCoupon('test'));

        $this->assertTrue($form->has('code'));
        $this->assertTrue($form->has('usesPerUser'));
        $this->assertTrue($form->has('usesPerCoupon'));
    }

    public function submitProvider()
    {
        return [
            'simple product' => [
                'submittedData' => [
                    'code' => 'test1234',
                    'usesPerUser' => 2,
                    'usesPerCoupon' => 3,
                ],
                'expectedData' => $this->createCoupon('test1234', 2, 3)
            ]
        ];
    }

    /**
     * @param string $couponCode
     * @param int|null $usesPerUser
     * @param int|null $usesPerCoupon
     * @return Coupon
     */
    public function createCoupon($couponCode, $usesPerUser = null, $usesPerCoupon = null)
    {
        return (new Coupon())->setCode($couponCode)->setUsesPerUser($usesPerUser)
            ->setUsesPerCoupon($usesPerCoupon);
    }
}
