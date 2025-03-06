<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Form\Type\AppliedPromotionType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

class AppliedPromotionTypeTest extends FormIntegrationTestCase
{
    public function testBuildForm()
    {
        $form = $this->factory->create(AppliedPromotionType::class);

        $this->assertTrue($form->has('active'));
        $this->assertTrue($form->has('removed'));
        $this->assertTrue($form->has('sourcePromotionId'));
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(AppliedPromotion $defaultData, array $submittedData, AppliedPromotion $expectedData)
    {
        $form = $this->factory->create(AppliedPromotionType::class, $defaultData);
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

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
            'new data with removed' => [
                'defaultData' => new AppliedPromotion(),
                'submittedData' => [
                    'active' => '1',
                    'removed' => '1',
                ],
                'expectedData' => (new AppliedPromotion())
                    ->setActive(true)
                    ->setRemoved(true),
            ],
            'update data' => [
                'defaultData' => (new AppliedPromotion())
                    ->setActive(true),
                'submittedData' => [
                    'active' => '0',
                ],
                'expectedData' => (new AppliedPromotion())
                    ->setActive(false),
            ],
            'update data with removed' => [
                'defaultData' => (new AppliedPromotion())
                    ->setActive(true),
                'submittedData' => [
                    'active' => '0',
                    'removed' => '1',
                ],
                'expectedData' => (new AppliedPromotion())
                    ->setActive(false)
                    ->setRemoved(true),
            ]
        ];
    }

    public function testDefaultOptions()
    {
        $form = $this->factory->create(AppliedPromotionType::class);

        $this->assertSame(AppliedPromotion::class, $form->getConfig()->getOptions()['data_class']);
    }

    public function testGetName()
    {
        $formType = new AppliedPromotionType();
        $this->assertEquals(AppliedPromotionType::NAME, $formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $formType = new AppliedPromotionType();
        $this->assertEquals(AppliedPromotionType::NAME, $formType->getBlockPrefix());
    }
}
