<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\EntityIdentifierType;
use Oro\Bundle\PromotionBundle\Form\Type\CouponAddType;
use Oro\Bundle\PromotionBundle\Form\Type\CouponAutocompleteType;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\Testing\Unit\EntityTrait;
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

        $coupon1 = $this->getEntity(Coupon::class, ['id' => 1]);
        $coupon2 = $this->getEntity(Coupon::class, ['id' => 2]);

        return [
            new PreloadedExtension(
                [
                    new EntityType(
                        [
                            'coupon1' => $coupon1,
                            'coupon2' => $coupon2,
                        ],
                        CouponAutocompleteType::NAME
                    ),
                    new EntityIdentifierType([]),
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
        $this->assertTrue($form->has('addedIds'));
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

        $data = [
            'coupon' => $form->get('coupon')->getData(),
            'addedIds' => $form->get('addedIds')->getData(),
        ];
        $this->assertEquals($expectedData, $data);
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        $coupon = $this->getEntity(Coupon::class, ['id' => 2]);

        return [
            [
                'submittedData' => [
                    'coupon' => 'coupon2',
                    'addedIds' => '',
                ],
                'expectedData' => [
                    'coupon' => $coupon,
                    'addedIds' => [],
                ],
            ],
        ];
    }
}
