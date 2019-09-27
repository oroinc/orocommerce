<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGStylesType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class WYSIWYGStylesTypeTest extends FormIntegrationTestCase
{
    public function testGetParent()
    {
        $type = new WYSIWYGStylesType();
        $this->assertEquals(HiddenType::class, $type->getParent());
    }

    public function testSubmit()
    {
        $form = $this->factory->create(WYSIWYGStylesType::class);
        $form->submit('h1 { color: black; }');
        $this->assertEquals('h1 { color: black; }', $form->getData());
    }

    public function testFinishView()
    {
        $view = new FormView();
        $form = $this->factory->create(WYSIWYGStylesType::class);
        $type = new WYSIWYGStylesType();
        $type->finishView($view, $form, []);

        $this->assertEquals('wysiwyg_styles', $view->vars['attr']['data-grapesjs-styles']);
    }
}
