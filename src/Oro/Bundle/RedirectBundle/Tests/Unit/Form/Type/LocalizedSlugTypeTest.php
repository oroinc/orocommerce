<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
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

        $this->formType = new LocalizedSlugType();
    }

    public function testGetName()
    {
        $this->assertEquals(LocalizedSlugType::NAME, $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(LocalizedSlugType::NAME, $this->formType->getBlockPrefix());
    }

    public function testBuildForm()
    {
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('addEventListener')
            ->with(
                FormEvents::POST_SUBMIT,
                function () {
                }
            );

        $this->formType->buildForm($builder, []);
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())->method('setDefaults')->with(
            $this->callback(
                function (array $options) {
                    $this->assertEquals('oro_api_slugify_slug', $options['slugify_route']);
                    $this->assertFalse($options['slug_suggestion_enabled']);

                    return true;
                }
            )
        );
        $resolver->expects($this->once())->method('setDefined')->with('source_field');

        $this->formType->configureOptions($resolver);
    }

    public function testBuildViewForSlugifyComponent()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);

        $viewParent = new FormView();
        $viewParent->vars['full_name'] = 'form-name';
        $view = new FormView($viewParent);
        $view->vars['full_name'] = 'form-name[target-name]';
        $options = [
            'source_field' => 'source-name',
            'slugify_route' => 'some-route',
            'slug_suggestion_enabled' => true,
        ];

        $this->formType->buildView($view, $form, $options);

        $this->assertArrayHasKey('slugify_component_options', $view->vars);
        $this->assertEquals(
            '[name^="form-name[source-name][values]"]',
            $view->vars['slugify_component_options']['source']
        );
        $this->assertEquals(
            '[name^="form-name[target-name][values]"]',
            $view->vars['slugify_component_options']['target']
        );
        $this->assertEquals(
            'some-route',
            $view->vars['slugify_component_options']['slugify_route']
        );
    }

    public function testBuildViewWithComponentsDisabled()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $data = $this->createPersistentCollection();
        $form->expects($this->any())
            ->method('getData')
            ->willReturn($data);

        $view = new FormView();
        $view->vars['full_name'] = 'form-name[target-name]';
        $options = [
            'source_field' => 'test',
            'slug_suggestion_enabled' => false,
        ];

        $this->formType->buildView($view, $form, $options);

        $this->assertArrayNotHasKey('slugify_component_options', $view->vars);
    }

    /**
     * @return PersistentCollection
     */
    protected function createPersistentCollection()
    {
        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        /** @var ClassMetadata $classMetadata */
        $classMetadata = $this->getMockBuilder(ClassMetadata::class)->disableOriginalConstructor()->getMock();
        $collection = new ArrayCollection(['some-entry']);

        return new PersistentCollection($em, $classMetadata, $collection);
    }
}
