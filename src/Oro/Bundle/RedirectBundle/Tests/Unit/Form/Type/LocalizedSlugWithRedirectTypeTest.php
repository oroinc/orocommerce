<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\SlugPrototypesWithRedirect;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugWithRedirectType;
use Oro\Bundle\ValidationBundle\Validator\Constraints\UrlSafe;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocalizedSlugWithRedirectTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var LocalizedSlugWithRedirectType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new LocalizedSlugWithRedirectType($this->configManager);
    }

    public function testGetName()
    {
        $this->assertEquals(LocalizedSlugWithRedirectType::NAME, $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(LocalizedSlugWithRedirectType::NAME, $this->formType->getBlockPrefix());
    }

    public function testBuildForm()
    {
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('add')
            ->with(
                LocalizedSlugWithRedirectType::SLUG_PROTOTYPES_FIELD_NAME,
                LocalizedSlugType::NAME,
                [
                    'required' => false,
                    'options' => ['constraints' => [new UrlSafe()]],
                    'label' => false,
                    'source_field' => 'field',
                    'slug_suggestion_enabled' => true,
                ]
            );
        $builder->expects($this->once())
            ->method('addEventListener')
            ->with(FormEvents::PRE_SET_DATA, [$this->formType, 'preSetData']);

        $this->formType->buildForm(
            $builder,
            ['source_field' => 'field', 'slug_suggestion_enabled' => true]
        );
    }

    public function testOnPreSetDataForUpdateConfirmationEnabled()
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_redirect.redirect_generation_strategy')
            ->willReturn(Configuration::STRATEGY_ASK);

        /** @var FormConfigInterface|\PHPUnit_Framework_MockObject_MockObject $formConfig */
        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects($this->any())
            ->method('getOptions')
            ->with()
            ->willReturn(['create_redirect_enabled' => true]);
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($formConfig));
        $form->expects($this->once())
            ->method('add')
            ->with(LocalizedSlugWithRedirectType::CREATE_REDIRECT_FIELD_NAME);

        /** @var FormEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(FormEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $data = new SlugPrototypesWithRedirect($this->createPersistentCollection());
        $event->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($data));
        $event->expects($this->any())
            ->method('getForm')
            ->will($this->returnValue($form));

        $this->formType->preSetData($event);
    }

    /**
     * @dataProvider disabledConfirmationStrategiesDataProvider
     * @param string $strategy
     */
    public function testOnPreSetDataForUpdateConfirmationDisabled($strategy)
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_redirect.redirect_generation_strategy')
            ->willReturn($strategy);

        /** @var FormConfigInterface|\PHPUnit_Framework_MockObject_MockObject $formConfig */
        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects($this->any())
            ->method('getOptions')
            ->with()
            ->willReturn(['create_redirect_enabled' => true]);
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($formConfig));
        $form->expects($this->never())
            ->method('add')
            ->with(LocalizedSlugWithRedirectType::CREATE_REDIRECT_FIELD_NAME);

        /** @var FormEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(FormEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $data = new SlugPrototypesWithRedirect($this->createPersistentCollection());
        $event->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($data));
        $event->expects($this->any())
            ->method('getForm')
            ->will($this->returnValue($form));

        $this->formType->preSetData($event);
    }

    /**
     * @return array
     */
    public function disabledConfirmationStrategiesDataProvider()
    {
        return [
            [Configuration::STRATEGY_ALWAYS],
            [Configuration::STRATEGY_NEVER]
        ];
    }

    public function testOnPreSetDataForUpdateConfirmationDisabledByOption()
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_redirect.redirect_generation_strategy')
            ->willReturn(Configuration::STRATEGY_ASK);

        /** @var FormConfigInterface|\PHPUnit_Framework_MockObject_MockObject $formConfig */
        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects($this->any())
            ->method('getOptions')
            ->with()
            ->willReturn(['create_redirect_enabled' => false]);
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($formConfig));
        $form->expects($this->never())
            ->method('add')
            ->with(LocalizedSlugWithRedirectType::CREATE_REDIRECT_FIELD_NAME);

        /** @var FormEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(FormEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $data = new SlugPrototypesWithRedirect($this->createPersistentCollection());
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
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_redirect.redirect_generation_strategy')
            ->willReturn(Configuration::STRATEGY_ASK);

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects($this->any())
            ->method('getOptions')
            ->willReturn(['create_redirect_enabled' => true]);
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($formConfig));

        $form->expects($this->never())
            ->method('add')
            ->with(LocalizedSlugWithRedirectType::CREATE_REDIRECT_FIELD_NAME);

        /** @var FormEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(FormEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())
            ->method('getData')
            ->will($this->returnValue(new SlugPrototypesWithRedirect(new ArrayCollection())));
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
                    $this->assertEquals(SlugPrototypesWithRedirect::class, $options['data_class']);
                    $this->assertFalse($options['slug_suggestion_enabled']);
                    $this->assertFalse($options['create_redirect_enabled']);

                    return true;
                }
            )
        );
        $resolver->expects($this->once())->method('setRequired')->with('source_field');

        $this->formType->configureOptions($resolver);
    }

    public function testBuildViewForConfirmationComponent()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('has')
            ->with(LocalizedSlugWithRedirectType::CREATE_REDIRECT_FIELD_NAME)
            ->willReturn(true);

        $view = new FormView();
        $view->vars['full_name'] = 'form-name[target-name]';
        $options = [
            'source_field' => 'test',
            'create_redirect_enabled' => true,
            'slug_suggestion_enabled' => false,
        ];

        $this->formType->buildView($view, $form, $options);

        $this->assertArrayHasKey('confirm_slug_change_component_options', $view->vars);
        $this->assertEquals(
            '[name^="form-name[target-name]['.LocalizedSlugWithRedirectType::SLUG_PROTOTYPES_FIELD_NAME.'][values]"]',
            $view->vars['confirm_slug_change_component_options']['slugFields']
        );
        $this->assertEquals(
            '[name^="form-name[target-name]['.LocalizedSlugWithRedirectType::CREATE_REDIRECT_FIELD_NAME.']"]',
            $view->vars['confirm_slug_change_component_options']['createRedirectCheckbox']
        );
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
