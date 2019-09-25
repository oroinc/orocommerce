<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WYSIWYGTypeTest extends FormIntegrationTestCase
{
    public function testGetParent()
    {
        $type = new WYSIWYGType();
        $this->assertEquals(TextareaType::class, $type->getParent());
    }

    public function testConfigureOptions()
    {
        /* @var $resolver OptionsResolver|\PHPUnit\Framework\MockObject\MockObject */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'page-component' => [
                    'module' => 'oroui/js/app/components/view-component',
                    'options' => [
                        'view' => 'orocms/js/app/views/grapesjs-editor-view'
                    ]
                ]
            ])
            ->will($this->returnSelf());

        $type = new WYSIWYGType();
        $type->configureOptions($resolver);
    }

    public function testSubmit()
    {
        $form = $this->factory->create(WYSIWYGType::class);
        $form->submit('<h1>Heading text</h1><p>Body text</p>');
        $this->assertEquals('<h1>Heading text</h1><p>Body text</p>', $form->getData());
    }

    public function testFinishView()
    {
        $view = new FormView();
        $form = $this->factory->create(WYSIWYGType::class);
        $type = new WYSIWYGType();
        $type->finishView($view, $form, ['page-component' => [
            'module' => 'component/module',
            'options' => ['view' => 'app/view']
        ]]);

        $this->assertEquals('component/module', $view->vars['attr']['data-page-component-module']);
        $this->assertEquals(
            '{"view":"app\/view","stylesInputSelector":"[data-grapesjs-styles=\"wysiwyg_style\"]"}',
            $view->vars['attr']['data-page-component-options']
        );
    }
}
