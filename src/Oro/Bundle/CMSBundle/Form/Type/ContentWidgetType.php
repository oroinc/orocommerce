<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Content widget form type
 */
class ContentWidgetType extends AbstractType
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var FormFactoryInterface */
    private $formFactory;

    /** @var ContentWidgetTypeRegistry */
    private $contentWidgetTypeRegistry;

    public function __construct(
        TranslatorInterface $translator,
        FormFactoryInterface $formFactory,
        ContentWidgetTypeRegistry $contentWidgetTypeRegistry
    ) {
        $this->translator = $translator;
        $this->formFactory = $formFactory;
        $this->contentWidgetTypeRegistry = $contentWidgetTypeRegistry;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'widgetType',
                ContentWidgetTypeSelectType::class,
                [
                    'label' => 'oro.cms.contentwidget.widget_type.label',
                    'required' => true,
                    'block' => 'general',
                ]
            )
            ->add(
                'name',
                TextType::class,
                [
                    'label' => 'oro.cms.contentwidget.name.label',
                    'required' => true,
                    'block' => 'general',
                ]
            )
            ->add(
                'description',
                TextareaType::class,
                [
                    'label' => 'oro.cms.contentwidget.description.label',
                    'required' => false,
                    'block' => 'general',
                ]
            )
            ->add(
                'layout',
                ContentWidgetLayoutSelectType::class,
                [
                    'label' => 'oro.cms.contentwidget.layout.label',
                    'required' => false,
                    'block' => 'general',
                ]
            );

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $data = $event->getData();
                if (!$data instanceof ContentWidget || !$data->getWidgetType()) {
                    return;
                }

                $form = $event->getForm();

                FormUtils::replaceFieldOptionsRecursive($form, 'layout', ['widget_type' => $data->getWidgetType()]);
                $this->buildSettingsField($form, $data, $data->getWidgetType());
            }
        );

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            static function (FormEvent $event) {
                $data = $event->getData();
                if ($data instanceof ContentWidget && $data->getId()) {
                    $form = $event->getForm();
                    $form->remove('widgetType');

                    FormUtils::replaceField($form, 'name', ['disabled' => true]);
                }
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $data = $event->getData();
                if (!is_array($data) || !isset($data['widgetType'])) {
                    return;
                }

                $form = $event->getForm();
                FormUtils::replaceFieldOptionsRecursive($form, 'layout', ['widget_type' => $data['widgetType']]);

                $contentWidget = $form->getData() ?: new ContentWidget();
                $this->buildSettingsField(
                    $form,
                    $contentWidget,
                    $contentWidget->getId() && $contentWidget->getWidgetType()
                        ? $contentWidget->getWidgetType()
                        : $data['widgetType']
                );
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if (isset($view->children['widgetType'])) {
            $view->vars = array_replace_recursive(
                $view->vars,
                [
                    'attr' => [
                        'data-page-component-view' => 'orocms/js/app/views/content-widget-view',
                        'data-page-component-options' => \json_encode(
                            [
                                'formSelector' => '#' . $view->vars['id'],
                                'typeSelector' => '#' . $view->children['widgetType']->vars['id'],
                                'fieldsSets' => array_values(
                                    array_map(
                                        static function (FormView $view) {
                                            return $view->vars['full_name'];
                                        },
                                        $view->children
                                    )
                                )
                            ]
                        ),
                    ]
                ]
            );
        }

        if (isset($view->children['settings'])) {
            foreach ($view->children['settings'] as $child) {
                if (!isset($child->vars['block'])) {
                    $child->vars['block'] = 'additional_information';

                    if (!isset($view->vars['block_config']['additional_information']['title'])) {
                        $title = 'oro.cms.contentwidget.sections.additional_information.label';

                        $view->vars['block_config']['additional_information']['title'] = $title;
                    }
                }
            }
        }

        $this->updateBlockConfig($view);
    }

    private function buildSettingsField(FormInterface $form, ContentWidget $contentWidget, string $widgetType): void
    {
        $contentWidgetType = $this->contentWidgetTypeRegistry->getWidgetType($widgetType);
        if (!$contentWidgetType) {
            return;
        }

        $settingsForm = $contentWidgetType->getSettingsForm($contentWidget, $this->formFactory);
        if (!$settingsForm) {
            return;
        }

        $config = $settingsForm->getConfig();
        $type = $config->getType();

        $form->add('settings', get_class($type->getInnerType()), $config->getOptions());

        // Adds each child separately in case the settings form was built inline.
        $settings = $form->get('settings');
        foreach ($settingsForm->all() as $child) {
            $settings->add($child);
        }

        $settings->setData($settingsForm->getData());
    }

    private function updateBlockConfig(FormView $view): void
    {
        if (isset($view->vars['block_config'])) {
            foreach ($view->vars['block_config'] as $block => $config) {
                $view->vars['block_config'][$block]['title'] = isset($config['title'])
                    ? $this->translator->trans((string) $config['title'])
                    : '';
            }
        }

        foreach ($view->children as $child) {
            $this->updateBlockConfig($child);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => ContentWidget::class,
                'block_config' => [
                    'general' => [
                        'title' => 'oro.cms.contentwidget.sections.general.label',
                    ],
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_cms_content_widget';
    }
}
