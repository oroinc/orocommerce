<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Form\Type\ContentWidgetType;
use Oro\Bundle\CMSBundle\Tests\Unit\Form\Type\Stub\ContentWidgetTypeStub;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
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
    /** @var string */
    private const FORM_NAME = 'test_form';

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var ContentWidgetType */
    private $formType;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                static function ($key) {
                    return str_replace('not ', '', $key);
                }
            );

        $this->formType = new ContentWidgetType($this->translator);

        parent::setUp();
    }

    public function testGetBlockPrefix(): void
    {
        $this->assertEquals('oro_cms_content_widget', $this->formType->getBlockPrefix());
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param FormInterface $widgetTypeForm
     * @param ContentWidget|null $defaultData
     * @param array|null $submittedData
     * @param ContentWidget $expectedData
     */
    public function testSubmit(
        FormInterface $widgetTypeForm,
        ?ContentWidget $defaultData,
        ?array $submittedData,
        ContentWidget $expectedData
    ): void {
        $form = $this->factory->create(
            ContentWidgetType::class,
            $defaultData,
            ['widget_type_form' => $widgetTypeForm]
        );

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($defaultData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider(): array
    {
        $formBuilder = Forms::createFormFactory()
            ->createNamedBuilder(self::FORM_NAME, FormType::class)
            ->add('param', TextType::class);

        $expected = $this->createContentWidget(['param' => 'value'])
            ->setWidgetType('test type')
            ->setName('test name')
            ->setDescription('test description')
            ->setTemplate('test template');

        return [
            'inline created form without default data and without submitted data' => [
                'widgetTypeForm' => $formBuilder->getForm(),
                'defaultData' => null,
                'submittedData' => null,
                'expectedData' => $this->createContentWidget(['param' => null]),
            ],
            'inline created form without default data and with submitted data' => [
                'widgetTypeForm' => $formBuilder->getForm(),
                'defaultData' => null,
                'submittedData' => [
                    'widgetType' => $expected->getWidgetType(),
                    'name' => $expected->getName(),
                    'description' => $expected->getDescription(),
                    'template' => $expected->getTemplate(),
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
                    'template' => $expected->getTemplate(),
                    'settings' => $expected->getSettings(),
                ],
                'expectedData' => $expected,
            ],
            'form based on form type without default data and without submitted data' => [
                'widgetTypeForm' => Forms::createFormFactory()->create(ContentWidgetTypeStub::class),
                'defaultData' => null,
                'submittedData' => null,
                'expectedData' => $this->createContentWidget(['param' => null]),
            ],
            'form based on form type without default data and with submitted data' => [
                'widgetTypeForm' => Forms::createFormFactory()->create(ContentWidgetTypeStub::class),
                'defaultData' => null,
                'submittedData' => [
                    'widgetType' => $expected->getWidgetType(),
                    'name' => $expected->getName(),
                    'description' => $expected->getDescription(),
                    'template' => $expected->getTemplate(),
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
                    'template' => $expected->getTemplate(),
                    'settings' => $expected->getSettings(),
                ],
                'expectedData' => $expected,
            ],
        ];
    }

    /**
     * @param array $settings
     * @return ContentWidget
     */
    private function createContentWidget(array $settings = []): ContentWidget
    {
        $contentWidget = new ContentWidget();

        return $contentWidget->setSettings($settings);
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
                'title' => 'not translated general label',
            ],
            'additional_information' => [
                'title' => 'not translated additional_information label',
            ]
        ];

        $this->formType->finishView($formView, $this->createMock(FormInterface::class), []);

        $this->assertArrayHasKey('block_config', $formView->vars);
        $this->assertEquals(
            [
                'general' => [
                    'title' => 'translated general label',
                ],
                'additional_information' => [
                    'title' => 'translated additional_information label',
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
                'title' => 'not translated general label',
            ],
            'additional_information' => [
                'title' => 'not translated additional_information label',
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
                    'widget_type_form' => null,
                    'block_config' => [
                        'general' => [
                            'title' => 'oro.cms.contentwidget.sections.general.label',
                        ],
                        'additional_information' => [
                            'title' => 'oro.cms.contentwidget.sections.additional_information.label',
                        ]
                    ],
                ]
            );
        $resolver->expects($this->once())
            ->method('setAllowedTypes')
            ->with('widget_type_form', ['null', FormInterface::class]);

        $this->formType->configureOptions($resolver);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions(): array
    {
        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension(
                    [
                        $this->formType,
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
