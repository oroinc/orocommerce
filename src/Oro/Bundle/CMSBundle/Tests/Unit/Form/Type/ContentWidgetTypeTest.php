<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeInterface;
use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Form\Type\ContentWidgetLayoutSelectType;
use Oro\Bundle\CMSBundle\Form\Type\ContentWidgetType;
use Oro\Bundle\CMSBundle\Form\Type\ContentWidgetTypeSelectType;
use Oro\Bundle\CMSBundle\Provider\ContentWidgetLayoutProvider;
use Oro\Bundle\CMSBundle\Provider\ContentWidgetTypeProvider;
use Oro\Bundle\CMSBundle\Tests\Unit\Form\Type\Stub\ContentWidgetTypeStub;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContentWidgetTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var string */
    private const FORM_NAME = 'test_form';

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var ContentWidgetTypeRegistry */
    private $contentWidgetTypeRegistry;

    /** @var ContentWidgetType */
    private $formType;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                static function ($key) {
                    return 'translated ' . $key;
                }
            );

        $this->contentWidgetTypeRegistry = $this->createMock(ContentWidgetTypeRegistry::class);

        $this->formType = new ContentWidgetType(
            $this->translator,
            Forms::createFormFactory(),
            $this->contentWidgetTypeRegistry
        );

        parent::setUp();
    }

    public function testGetBlockPrefix(): void
    {
        $this->assertEquals('oro_cms_content_widget', $this->formType->getBlockPrefix());
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        FormInterface $widgetTypeForm,
        ?ContentWidget $defaultData,
        ?array $submittedData,
        ContentWidget $expectedData
    ): void {
        $contentWidget = $defaultData ?: $this->createContentWidget();

        $contentWidgetType = $this->createMock(ContentWidgetTypeInterface::class);
        $contentWidgetType->expects($this->any())
            ->method('getSettingsForm')
            ->with($contentWidget, Forms::createFormFactory())
            ->willReturn($widgetTypeForm);

        $this->contentWidgetTypeRegistry->expects($this->any())
            ->method('getWidgetType')
            ->with('testType1')
            ->willReturn($contentWidgetType);

        $form = $this->factory->create(ContentWidgetType::class, $defaultData);

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($defaultData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider(): array
    {
        $formBuilder = Forms::createFormFactory()
            ->createNamedBuilder(self::FORM_NAME, FormType::class)
            ->add('param', TextType::class);

        $expected = $this->createContentWidget(['param' => 'value'])
            ->setWidgetType('testType1')
            ->setName('test name')
            ->setDescription('test description')
            ->setLayout('template2');

        return [
            'inline created form without default data and without submitted data' => [
                'widgetTypeForm' => $formBuilder->getForm(),
                'defaultData' => null,
                'submittedData' => null,
                'expectedData' => $this->createContentWidget(),
            ],
            'inline created form without default data and with submitted data' => [
                'widgetTypeForm' => $formBuilder->getForm(),
                'defaultData' => null,
                'submittedData' => [
                    'widgetType' => $expected->getWidgetType(),
                    'name' => $expected->getName(),
                    'description' => $expected->getDescription(),
                    'layout' => $expected->getLayout(),
                    'settings' => $expected->getSettings(),
                ],
                'expectedData' => $expected,
            ],
            'inline created form with default data and with submitted data' => [
                'widgetTypeForm' => $formBuilder->getForm(),
                'defaultData' => $this->createContentWidget(['param' => 'old value']),
                'submittedData' => [
                    'widgetType' => $expected->getWidgetType(),
                    'name' => $expected->getName(),
                    'description' => $expected->getDescription(),
                    'layout' => $expected->getLayout(),
                    'settings' => $expected->getSettings(),
                ],
                'expectedData' => $expected,
            ],
            'form based on form type without default data and without submitted data' => [
                'widgetTypeForm' => Forms::createFormFactory()->create(ContentWidgetTypeStub::class),
                'defaultData' => null,
                'submittedData' => null,
                'expectedData' => $this->createContentWidget(),
            ],
            'form based on form type without default data and with submitted data' => [
                'widgetTypeForm' => Forms::createFormFactory()->create(ContentWidgetTypeStub::class),
                'defaultData' => null,
                'submittedData' => [
                    'widgetType' => $expected->getWidgetType(),
                    'name' => $expected->getName(),
                    'description' => $expected->getDescription(),
                    'layout' => $expected->getLayout(),
                    'settings' => $expected->getSettings(),
                ],
                'expectedData' => $expected,
            ],
            'form based on form type with default data and with submitted data' => [
                'widgetTypeForm' => Forms::createFormFactory()->create(ContentWidgetTypeStub::class),
                'defaultData' => $this->createContentWidget(['param' => 'old value']),
                'submittedData' => [
                    'widgetType' => $expected->getWidgetType(),
                    'name' => $expected->getName(),
                    'description' => $expected->getDescription(),
                    'layout' => $expected->getLayout(),
                    'settings' => $expected->getSettings(),
                ],
                'expectedData' => $expected,
            ],
            'form based on form type with default data (include id) and with submitted data' => [
                'widgetTypeForm' => Forms::createFormFactory()->create(ContentWidgetTypeStub::class),
                'defaultData' => $this->createContentWidget(['param' => 'old value'], 42)
                    ->setWidgetType('testType1')
                    ->setName('test_name'),
                'submittedData' => [
                    'widgetType' => 'unsaved type',
                    'name' => 'unsaved name',
                    'description' => 'test description',
                    'layout' => 'template2',
                    'settings' => ['param' => 'value'],
                ],
                'expectedData' => $this->createContentWidget(['param' => 'value'], 42)
                    ->setWidgetType('testType1')
                    ->setName('test_name')
                    ->setDescription('test description')
                    ->setLayout('template2'),
            ],
        ];
    }

    private function createContentWidget(array $settings = [], ?int $id = null): ContentWidget
    {
        return $this->getEntity(ContentWidget::class, ['settings' => $settings, 'id' => $id]);
    }

    public function testFinishView(): void
    {
        $settingsField1FormView = new FormView();
        $settingsField1FormView->vars['block'] = 'general';

        $settingsField2FormView = new FormView();

        $settingsFormView = new FormView();
        $settingsFormView->children['field1'] = $settingsField1FormView;
        $settingsFormView->children['field2'] = $settingsField2FormView;

        $formView = new FormView();
        $formView->children['settings'] = $settingsFormView;
        $formView->vars['block_config'] = [
            'general' => [
                'title' => 'general label',
            ],
        ];

        $this->formType->finishView($formView, $this->createMock(FormInterface::class), []);

        $this->assertArrayHasKey('block_config', $formView->vars);
        $this->assertEquals(
            [
                'general' => [
                    'title' => 'translated general label',
                ],
                'additional_information' => [
                    'title' => 'translated oro.cms.contentwidget.sections.additional_information.label',
                ]
            ],
            $formView->vars['block_config']
        );

        $this->assertArrayHasKey('block', $settingsField1FormView->vars);
        $this->assertEquals('general', $settingsField1FormView->vars['block']);

        $this->assertArrayHasKey('block', $settingsField2FormView->vars);
        $this->assertEquals('additional_information', $settingsField2FormView->vars['block']);
    }

    public function testFinishViewWithoutSettings(): void
    {
        $formView = new FormView();
        $formView->vars['block_config'] = [
            'general' => [
                'title' => 'general label',
            ]
        ];

        $this->formType->finishView($formView, $this->createMock(FormInterface::class), []);

        $this->assertArrayHasKey('block_config', $formView->vars);
        $this->assertEquals(
            [
                'general' => [
                    'title' => 'translated general label',
                ],
            ],
            $formView->vars['block_config']
        );
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => ContentWidget::class,
                    'block_config' => [
                        'general' => [
                            'title' => 'oro.cms.contentwidget.sections.general.label',
                        ],
                    ],
                ]
            );

        $this->formType->configureOptions($resolver);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions(): array
    {
        $contentWidgetTypeProvider = $this->createMock(ContentWidgetTypeProvider::class);
        $contentWidgetTypeProvider->expects($this->any())
            ->method('getAvailableContentWidgetTypes')
            ->willReturn(
                [
                    'oro.type1.label' => 'testType1',
                    'oro.type2.label' => 'testType2',
                ]
            );

        $widgetsProvider = $this->createMock(ContentWidgetLayoutProvider::class);
        $widgetsProvider->expects($this->any())
            ->method('getWidgetLayouts')
            ->willReturn(['template1' => 'Template 1', 'template2' => 'Template 2']);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension(
                    [
                        $this->formType,
                        new ContentWidgetTypeSelectType($contentWidgetTypeProvider),
                        new ContentWidgetLayoutSelectType($widgetsProvider, $translator),
                    ],
                    []
                )
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTypeExtensions(): array
    {
        return array_merge(
            parent::getExtensions(),
            [
                new DataBlockExtension(),
            ]
        );
    }
}
