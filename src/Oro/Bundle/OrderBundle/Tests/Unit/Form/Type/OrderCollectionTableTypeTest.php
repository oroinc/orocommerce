<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\OrderBundle\Form\Type\OrderCollectionTableType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class OrderCollectionTableTypeTest extends FormIntegrationTestCase
{
    public function testRequiredOptionsPageComponentRequired()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "page_component" is missing.');

        $this->factory->create(OrderCollectionTableType::class, null, ['template_name' => 'some_template']);
    }

    public function testRequiredOptionsTemplateNameRequired()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "template_name" is missing.');

        $this->factory->create(OrderCollectionTableType::class, null, ['page_component' => 'some_component']);
    }

    public function testDefaultOptions()
    {
        $form = $this->factory->create(OrderCollectionTableType::class, null, [
            'page_component' => 'SomeComponent',
            'template_name' => 'some_template'
        ]);

        $this->assertArraySubset(
            [
                'page_component' => 'SomeComponent',
                'template_name' => 'some_template',
                'error_bubbling' => false,
                'prototype' => true,
                'allow_add' => true,
                'allow_delete' => true,
                'page_component_options' => [],
                'prototype_name' => '__table_collection_item__',
                'by_reference' => false
            ],
            $form->getConfig()->getOptions()
        );
    }

    public function testView()
    {
        $form = $this->factory->create(OrderCollectionTableType::class, null, [
            'page_component' => 'SomeComponent',
            'page_component_options' => ['option' => 'value'],
            'template_name' => 'some_template',
        ]);

        $formView = $form->createView();

        $this->assertArraySubset([
            'template_name' => 'some_template',
            'attr' => [
                'data-page-component-module' => 'SomeComponent',
                'data-page-component-options' => '{"option":"value"}'
            ]
        ], $formView->vars);
    }

    public function testViewWithParent()
    {
        $form = $this->factory->create(OrderCollectionTableType::class, null, [
            'page_component' => 'SomeComponent',
            'page_component_options' => ['option' => 'value'],
            'template_name' => 'some_template',
        ]);

        $formView = $form->createView();

        $this->assertArraySubset([
            'template_name' => 'some_template',
            'attr' => [
                'data-page-component-module' => 'SomeComponent',
                'data-page-component-options' => '{"option":"value"}'
            ]
        ], $formView->vars);
    }

    public function testGetBlockPrefix()
    {
        $formType = new OrderCollectionTableType();
        $this->assertEquals('oro_order_collection_table', $formType->getBlockPrefix());
    }
}
