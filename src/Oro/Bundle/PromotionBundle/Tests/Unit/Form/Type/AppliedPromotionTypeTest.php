<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Form\Type\AppliedPromotionType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

class AppliedPromotionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var AppliedPromotionType
     */
    private $formType;

    protected function setUp()
    {
        parent::setUp();
        $this->formType = new AppliedPromotionType();
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->formType);

        $this->assertTrue($form->has('active'));
        $this->assertTrue($form->has('sourcePromotionId'));
    }

    /**
     * @dataProvider submitProvider
     *
     * @param AppliedPromotion $defaultData
     * @param array $submittedData
     * @param AppliedPromotion $expectedData
     */
    public function testSubmit(AppliedPromotion $defaultData, array $submittedData, AppliedPromotion $expectedData)
    {
        $form = $this->factory->create($this->formType, $defaultData);
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
            'new data' => [
                'defaultData' => new AppliedPromotion(),
                'submittedData' => [
                    'active' => '1',
                ],
                'expectedData' => (new AppliedPromotion())
                    ->setActive(true),
            ],
            'update data' => [
                'defaultData' => (new AppliedPromotion())
                    ->setActive(true),
                'submittedData' => [
                    'active' => '0',
                ],
                'expectedData' => (new AppliedPromotion())
                    ->setActive(false),
            ]
        ];
    }

    public function testDefaultOptions()
    {
        $form = $this->factory->create($this->formType);

        $this->assertArraySubset([
            'data_class' => AppliedPromotion::class,
        ], $form->getConfig()->getOptions());
    }

    public function testGetName()
    {
        $this->assertEquals(AppliedPromotionType::NAME, $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(AppliedPromotionType::NAME, $this->formType->getBlockPrefix());
    }
}
