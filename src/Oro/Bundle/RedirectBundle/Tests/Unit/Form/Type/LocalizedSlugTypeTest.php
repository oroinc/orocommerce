<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
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

    public function testBuildForm()
    {
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->any())
                ->method('addEventListener');
        $builder->expects($this->at(1))
                ->method('addEventListener')
                ->with(FormEvents::PRE_SET_DATA, [$this->formType, 'preSetData']);

        $this->formType->buildForm($builder, []);
    }

    public function testOnPreSetDataForUpdate()
    {
        /** @var FormConfigInterface|\PHPUnit_Framework_MockObject_MockObject $formConfig */
        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects($this->any())
            ->method('getOption')
            ->with('create_redirect_enabled')
            ->will($this->returnValue(true));
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
             ->method('getConfig')
             ->will($this->returnValue($formConfig));
        $form->expects($this->once())
             ->method('add')
             ->with(
                 LocalizedSlugType::CREATE_REDIRECT_OPTION_NAME,
                 CheckboxType::class,
                 [
                     'label' => 'oro.redirect.confirm_slug_change.checkbox_label',
                     'data' => true,
                 ]
             );

        /** @var FormEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(FormEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $data = $this->createPersistentCollection();
        $event->expects($this->any())
             ->method('getData')
             ->will($this->returnValue($data));
        $event->expects($this->any())
              ->method('getForm')
              ->will($this->returnValue($form));

        $this->formType->preSetData($event);
    }

    public function testOnPreSetDataForCreate()
    {
        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects($this->any())
                   ->method('getOption')
                   ->with('create_redirect_enabled')
                   ->will($this->returnValue(true));
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
             ->method('getConfig')
             ->will($this->returnValue($formConfig));
        $form->expects($this->never())
              ->method('add');

        /** @var FormEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(FormEvent::class)
                      ->disableOriginalConstructor()
                      ->getMock();
        $event->expects($this->any())
              ->method('getData')
              ->will($this->returnValue(new ArrayCollection()));
        $event->expects($this->any())
              ->method('getForm')
              ->will($this->returnValue($form));

        $this->formType->preSetData($event);
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())->method('setDefaults')->with(
            $this->callback(
                function (array $options) {
                    $this->assertEquals(
                        'ororedirect/js/app/components/localized-slug-component',
                        $options['localized_slug_component']
                    );
                    $this->assertEquals('oro_api_slugify_slug', $options['slugify_route']);
                    $this->assertFalse($options['slug_suggestion_enabled']);
                    $this->assertFalse($options['create_redirect_enabled']);

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
            'localized_slug_component' => 'some-component-path',
            'slugify_route' => 'some-route',
            'slug_suggestion_enabled' => true,
            'create_redirect_enabled' => false,
        ];

        $this->formType->buildView($view, $form, $options);

        $this->assertEquals('some-component-path', $view->vars['localized_slug_component']);
        $this->assertEquals(
            '[name^="form-name[source-name][values]"]',
            $view->vars['localized_slug_component_options']['slugify_component_options']['source']
        );
        $this->assertEquals(
            '[name^="form-name[target-name][values]"]',
            $view->vars['localized_slug_component_options']['slugify_component_options']['target']
        );
        $this->assertEquals(
            'some-route',
            $view->vars['localized_slug_component_options']['slugify_component_options']['slugify_route']
        );
    }

    public function testBuildViewForConfirmationComponent()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $data = $this->createPersistentCollection();
        $form->expects($this->any())
          ->method('getData')
          ->will($this->returnValue($data));

        $view = new FormView();
        $view->vars['full_name'] = 'form-name[target-name]';
        $options = [
            'localized_slug_component' => 'some-component-path',
            'create_redirect_enabled' => true,
            'slug_suggestion_enabled' => false,
        ];

        $this->formType->buildView($view, $form, $options);

        $this->assertEquals('some-component-path', $view->vars['localized_slug_component']);
        $this->assertEquals(
            '[name^="form-name[target-name][values]"]',
            $view->vars['localized_slug_component_options']['confirmation_component_options']['slugFields']
        );
        $this->assertEquals(
            '[name^="form-name[target-name]['.LocalizedSlugType::CREATE_REDIRECT_OPTION_NAME.']"]',
            $view->vars['localized_slug_component_options']['confirmation_component_options']['createRedirectCheckbox']
        );
    }

    public function testBuildViewWithComponentsDisabled()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);

        $view = new FormView();
        $view->vars['full_name'] = 'form-name[target-name]';
        $options = [
            'create_redirect_enabled' => false,
            'slug_suggestion_enabled' => false,
        ];

        $this->formType->buildView($view, $form, $options);

        $this->assertArrayNotHasKey('slugify_component', $view->vars);
        $this->assertArrayNotHasKey('confirmation_component', $view->vars);
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
