<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CheckoutBundle\Form\Type\AvailableGroupingFieldsConfigType;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\FieldsOptionsProvider;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AvailableGroupingFieldsConfigTypeTest extends FormIntegrationTestCase
{
    private FieldsOptionsProvider|\PHPUnit\Framework\MockObject\MockObject $optionsProvider;

    protected function setUp(): void
    {
        $this->optionsProvider = $this->createMock(FieldsOptionsProvider::class);
        parent::setUp();
    }

    public function testConfigureOptions()
    {
        $formType = new AvailableGroupingFieldsConfigType($this->optionsProvider);

        $choices = [
            'Product' => [
                'Owner' => 'product.owner',
                'Category' => 'product.category',
                'Sku' => 'product.sku'
            ]
        ];

        $this->optionsProvider->expects($this->once())
            ->method('getAvailableFieldsForGroupingFormOptions')
            ->willReturn($choices);

        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'label' => null,
                'choices' => $choices
            ])
            ->willReturnSelf();

        $formType->configureOptions($resolver);
    }

    public function testGetBlockPrefix()
    {
        $formType = new AvailableGroupingFieldsConfigType($this->optionsProvider);
        $this->assertEquals('oro_checkout_available_grouping_fields', $formType->getBlockPrefix());
    }

    public function testGetName()
    {
        $formType = new AvailableGroupingFieldsConfigType($this->optionsProvider);
        $this->assertEquals('oro_checkout_available_grouping_fields', $formType->getName());
    }

    public function testGetParent()
    {
        $formType = new AvailableGroupingFieldsConfigType($this->optionsProvider);
        $this->assertEquals(ChoiceType::class, $formType->getParent());
    }
}
