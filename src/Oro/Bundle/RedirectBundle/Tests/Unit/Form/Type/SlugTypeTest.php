<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\RedirectBundle\Form\Type\SlugType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SlugTypeTest extends FormIntegrationTestCase
{
    /**
     * @var SlugType
     */
    protected $formType;
    
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new SlugType();
    }

    public function testGetName()
    {
        $this->assertEquals(SlugType::NAME, $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(SlugType::NAME, $this->formType->getBlockPrefix());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->getMock(OptionsResolver::class);
        $resolver->expects($this->once())->method('setDefaults')->with(
            $this->callback(
                function (array $options) {
                    $this->assertEquals($options['source_field'], 'titles');
                    $this->assertEquals(
                        $options['slugify_component'],
                        'ororedirect/js/app/components/text-field-slugify-component'
                    );
                    $this->assertEquals($options['slugify_route'], 'oro_api_slugify_slug');

                    return true;
                }
            )
        );

        $this->formType->configureOptions($resolver);
    }

    public function testBuildView()
    {
        $form = $this->getMock(FormInterface::class);

        $viewParent = new FormView();
        $viewParent->vars['full_name'] = 'form-name';
        $view = new FormView($viewParent);
        $view->vars['full_name'] = 'form-name[target-name]';
        $options = [
            'source_field' => 'source-name',
            'slugify_component' => 'some-component-path',
            'slugify_route' => 'some-route',
        ];

        $this->formType->buildView($view, $form, $options);

        $this->assertEquals('some-component-path', $view->vars['slugify_component']);
        $this->assertEquals('[name="form-name[source-name]"]', $view->vars['slugify_component_options']['source']);
        $this->assertEquals('[name="form-name[target-name]"]', $view->vars['slugify_component_options']['target']);
        $this->assertEquals('some-route', $view->vars['slugify_component_options']['slugify_route']);
    }
}
