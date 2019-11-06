<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WYSIWYGTypeTest extends FormIntegrationTestCase
{
    public function testGetParent()
    {
        $htmlTagProvider = $this->createMock(HtmlTagProvider::class);

        $type = new WYSIWYGType($htmlTagProvider);
        $this->assertEquals(TextareaType::class, $type->getParent());
    }

    public function testConfigureOptions()
    {
        $htmlTagProvider = $this->createMock(HtmlTagProvider::class);
        $htmlTagProvider->expects($this->once())
            ->method('getAllowedElements')
            ->willReturn(['h1', 'h2', 'h3']);

        /* @var $resolver OptionsResolver|\PHPUnit\Framework\MockObject\MockObject */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'page-component' => [
                    'module' => 'oroui/js/app/components/view-component',
                    'options' => [
                        'view' => 'orocms/js/app/grapesjs/grapesjs-editor-view',
                        'allow_tags' => ['h1', 'h2', 'h3']
                    ]
                ],
                'auto_render' => true,
            ])
            ->will($this->returnSelf());

        $type = new WYSIWYGType($htmlTagProvider);
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
        $htmlTagProvider = $this->createMock(HtmlTagProvider::class);

        $view = new FormView();
        $form = $this->factory->create(WYSIWYGType::class);
        $type = new WYSIWYGType($htmlTagProvider);
        $type->finishView($view, $form, [
            'page-component' => [
                'module' => 'component/module',
                'options' => ['view' => 'app/view']
            ],
            'auto_render' => true,
        ]);

        $this->assertEquals('component/module', $view->vars['attr']['data-page-component-module']);
        $this->assertEquals(
            '{"view":"app\/view","autoRender":true,"stylesInputSelector":"[data-grapesjs-styles=\"wysiwyg_style\"]",'
            . '"propertiesInputSelector":"[data-grapesjs-properties=\"wysiwyg_properties\"]"}',
            $view->vars['attr']['data-page-component-options']
        );
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $htmlTagProvider = $this->createMock(HtmlTagProvider::class);

        return [
            new PreloadedExtension(
                [
                    WYSIWYGType::class => new WYSIWYGType($htmlTagProvider),
                ],
                []
            )
        ];
    }
}
