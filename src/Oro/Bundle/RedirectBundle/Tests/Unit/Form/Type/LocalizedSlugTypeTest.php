<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Type;

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

        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
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
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
                 ->method('setDefaults')
                 ->with($this->callback(function (array $options) {
                     $this->assertEquals($options['source_field'], 'titles');
                     $this->assertEquals(
                         $options['slugify_component'],
                         'ororedirect/js/app/components/localized-field-slugify-component'
                     );
                     $this->assertEquals($options['slugify_route'], 'oro_api_slugify_slug');

                     return true;
                 }))
        ;

        $this->formType->configureOptions($resolver);
    }

    public function testBuildView()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

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
