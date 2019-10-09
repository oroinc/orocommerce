<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGPropertiesType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class WYSIWYGPropertiesTypeTest extends FormIntegrationTestCase
{
    public function testGetParent(): void
    {
        $type = new WYSIWYGPropertiesType();

        $this->assertEquals(HiddenType::class, $type->getParent());
    }

    public function testSubmit(): void
    {
        $form = $this->factory->create(WYSIWYGPropertiesType::class);
        $properties = \json_encode([
            'property1' => ['value' => 'value 1'],
            'property2' => ['value' => 'value 2'],
        ]);
        $form->submit($properties);

        $this->assertJsonStringEqualsJsonString($properties, $form->getData());
    }

    public function testFinishView(): void
    {
        $view = new FormView();
        $form = $this->factory->create(WYSIWYGPropertiesType::class);
        $type = new WYSIWYGPropertiesType();
        $type->finishView($view, $form, []);

        $this->assertEquals('wysiwyg_properties', $view->vars['attr']['data-grapesjs-properties']);
    }
}
