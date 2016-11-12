<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Entity;

use Oro\Bundle\RedirectBundle\Form\Type\SlugifyFieldIntoSlugTrait;
use Symfony\Component\Form\FormView;

class SlugifyFieldIntoSlugTraitTest extends \PHPUnit_Framework_TestCase
{
    use SlugifyFieldIntoSlugTrait;

    public function testAddComponentOptions()
    {
        $viewParent = new FormView();
        $viewParent->vars['full_name'] = 'parent-full-name';
        $view = new FormView($viewParent);
        $view->vars['full_name'] = 'view-full-name';
        $options = ['target_field_name' => 'target-full-name'];

        $this->addComponentOptions($view, $options);

        $this->assertEquals('some-component-path', $view->vars['slugify_component']);
        $this->assertEquals('parent-full-name[target-full-name]', $view->vars['slugify_component_options']['target']);
        $this->assertEquals('view-full-name', $view->vars['slugify_component_options']['recipient']);
    }

    public function getComponent()
    {
        return 'some-component-path';
    }
}
