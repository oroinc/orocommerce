<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Form\Type\DiscountConfigurationType;
use Oro\Bundle\PromotionBundle\Provider\DiscountFormTypeProvider;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\FormView;

class DiscountConfigurationTypeTest extends FormIntegrationTestCase
{
    /**
     * @var DiscountFormTypeProvider
     */
    private $discountFormTypeProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->discountFormTypeProvider = new DiscountFormTypeProvider();
        parent::setUp();
    }

    /**
     * @dataProvider submitDataProvider
     * @param mixed $defaultData
     * @param array $submittedData
     * @param DiscountConfiguration $expectedData
     */
    public function testSubmit($defaultData, array $submittedData, DiscountConfiguration $expectedData)
    {
        $this->discountFormTypeProvider->addFormType('discount_type', 'text');
        $this->discountFormTypeProvider->setDefaultFormType('text');

        $form = $this->factory->create(DiscountConfigurationType::class, $defaultData);

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($defaultData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        $result = $form->getData();
        $this->assertEquals($expectedData, $result);
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $discountType = 'discount_type';
        $discountOptions = [
            'option' => 'value'
        ];

        $expectedConfiguration = new DiscountConfiguration();
        $expectedConfiguration->setType($discountType);
        $expectedConfiguration->setOptions($discountOptions);

        $existingConfiguration = new DiscountConfiguration();
        $existingConfiguration->setType('existing_type');
        $existingConfiguration->setOptions([
            'existing_type_option' => 'some_value'
        ]);

        return [
            'new discount configuration' => [
                'defaultData' => null,
                'submittedData' => [
                    'type' => $discountType,
                    'options' => $discountOptions
                ],
                'expectedData' => $expectedConfiguration
            ],
            'edit existing discount configuration' => [
                'defaultData' => $existingConfiguration,
                'submittedData' => [
                    'type' => $discountType,
                    'options' => $discountOptions
                ],
                'expectedData' => $expectedConfiguration
            ]
        ];
    }

    public function testBuildView()
    {
        $this->discountFormTypeProvider->addFormType('discount_type', 'text');
        $this->discountFormTypeProvider->setDefaultFormType('text');

        $existingConfiguration = new DiscountConfiguration();
        $existingConfiguration->setType('existing_type');
        $existingConfiguration->setOptions([
            'existing_type_option' => 'some_value'
        ]);

        $form = $this->factory->create(DiscountConfigurationType::class, $existingConfiguration);

        $formView = $form->createView();

        $this->assertArrayHasKey('discount_type', $formView->vars['prototypes']);
        $this->assertInstanceOf(FormView::class, $formView->vars['prototypes']['discount_type']);
    }

    public function testGetName()
    {
        $formType = new DiscountConfigurationType(new DiscountFormTypeProvider());

        $this->assertEquals(DiscountConfigurationType::NAME, $formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $formType = new DiscountConfigurationType(new DiscountFormTypeProvider());

        $this->assertEquals(DiscountConfigurationType::NAME, $formType->getBlockPrefix());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    DiscountConfigurationType::class => new DiscountConfigurationType($this->discountFormTypeProvider)
                ],
                []
            ),
        ];
    }
}
