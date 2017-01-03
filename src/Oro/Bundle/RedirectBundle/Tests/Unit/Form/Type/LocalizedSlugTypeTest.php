<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocalizedSlugTypeTest extends FormIntegrationTestCase
{
    /**
     * @var LocalizedSlugType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $registry = $this->createMock(ManagerRegistry::class);
        $this->formType = new LocalizedSlugType($registry);
    }

    public function testGetName()
    {
        $this->assertEquals(LocalizedSlugType::NAME, $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(LocalizedSlugType::NAME, $this->formType->getBlockPrefix());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())->method('setDefaults')->with(
            $this->callback(
                function (array $options) {
                    $this->assertEquals(
                        $options['slugify_component'],
                        'ororedirect/js/app/components/localized-field-slugify-component'
                    );
                    $this->assertEquals($options['slugify_route'], 'oro_api_slugify_slug');

                    return true;
                }
            )
        );
        $resolver->expects($this->once())->method('setRequired')->with('source_field');

        $this->formType->configureOptions($resolver);
    }

    public function testBuildView()
    {
        $form = $this->createMock(FormInterface::class);

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
        $this->assertEquals(
            '[name^="form-name[source-name][values]"]',
            $view->vars['slugify_component_options']['source']
        );
        $this->assertEquals(
            '[name^="form-name[target-name][values]"]',
            $view->vars['slugify_component_options']['target']
        );
        $this->assertEquals('some-route', $view->vars['slugify_component_options']['slugify_route']);
    }
}
