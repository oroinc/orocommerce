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

        $options = $form->getConfig()->getOptions();

        $this->assertSame('SomeComponent', $options['page_component']);
        $this->assertSame('some_template', $options['template_name']);
        $this->assertSame(false, $options['error_bubbling']);
        $this->assertSame(true, $options['prototype']);
        $this->assertSame(true, $options['allow_add']);
        $this->assertSame(true, $options['allow_delete']);
        $this->assertSame([], $options['page_component_options']);
        $this->assertSame('__table_collection_item__', $options['prototype_name']);
        $this->assertSame(false, $options['by_reference']);
    }

    public function testView()
    {
        $form = $this->factory->create(OrderCollectionTableType::class, null, [
            'page_component' => 'SomeComponent',
            'page_component_options' => ['option' => 'value'],
            'template_name' => 'some_template',
        ]);

        $formView = $form->createView();

        $this->assertSame('some_template', $formView->vars['template_name']);
        $this->assertSame([
            'data-page-component-module' => 'SomeComponent',
            'data-page-component-options' => '{"option":"value"}'
        ], $formView->vars['attr']);
    }

    public function testViewWithParent()
    {
        $form = $this->factory->create(OrderCollectionTableType::class, null, [
            'page_component' => 'SomeComponent',
            'page_component_options' => ['option' => 'value'],
            'template_name' => 'some_template',
        ]);

        $formView = $form->createView();

        $this->assertSame('some_template', $formView->vars['template_name']);
        $this->assertSame([
            'data-page-component-module' => 'SomeComponent',
            'data-page-component-options' => '{"option":"value"}'
        ], $formView->vars['attr']);
    }

    public function testGetBlockPrefix()
    {
        $formType = new OrderCollectionTableType();
        $this->assertEquals('oro_order_collection_table', $formType->getBlockPrefix());
    }
}
