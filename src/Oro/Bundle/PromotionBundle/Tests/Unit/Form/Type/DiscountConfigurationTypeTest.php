<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Form\Type\DiscountConfigurationType;
use Oro\Bundle\PromotionBundle\Provider\DiscountFormTypeProvider;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\ScopeStub;
use Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type\Stub\ScopeCollectionTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormView;

class DiscountConfigurationTypeTest extends FormIntegrationTestCase
{
    private DiscountFormTypeProvider $discountFormTypeProvider;

    protected function setUp(): void
    {
        $this->discountFormTypeProvider = new DiscountFormTypeProvider();
        parent::setUp();
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(mixed $defaultData, array $submittedData, DiscountConfiguration $expectedData)
    {
        $this->discountFormTypeProvider->addFormType('discount_type', ScopeCollectionTypeStub::class);
        $this->discountFormTypeProvider->setDefaultFormType(ScopeCollectionTypeStub::class);

        $form = $this->factory->create(DiscountConfigurationType::class, $defaultData);

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($defaultData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $result = $form->getData();
        $this->assertEquals($expectedData, $result);
    }

    public function submitDataProvider(): array
    {
        $scope = (new ScopeStub())->setLocale('zu');
        $discountType = 'discount_type';
        $discountOptions = [
            'locale' => [
                'locale' => 'zu',
            ]
        ];

        $expectedConfiguration = new DiscountConfiguration();
        $expectedConfiguration->setType($discountType);
        $expectedConfiguration->setOptions(['locale' => $scope]);

        $existingConfiguration = new DiscountConfiguration();
        $existingConfiguration->setType('existing_type');
        $existingConfiguration->setOptions(['locale' => $scope]);

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
        $this->discountFormTypeProvider->addFormType('discount_type', TextType::class);
        $this->discountFormTypeProvider->setDefaultFormType(TextType::class);

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

    public function testGetBlockPrefix()
    {
        $formType = new DiscountConfigurationType(new DiscountFormTypeProvider());

        $this->assertEquals(DiscountConfigurationType::NAME, $formType->getBlockPrefix());
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
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
