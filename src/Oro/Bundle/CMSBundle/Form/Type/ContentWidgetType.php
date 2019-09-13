<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'widgetType',
                TextType::class,
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
                'template',
                TextType::class,
                [
                    'label' => 'oro.cms.contentwidget.template.label',
                    'required' => false,
                    'block' => 'general',
                ]
            );

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            static function (FormEvent $event) use ($options) {
                /** @var FormInterface $widgetTypeForm */
                $widgetTypeForm = $options['widget_type_form'];
                if (!$widgetTypeForm) {
                    return;
                }

                $config = $widgetTypeForm->getConfig();
                $type = $config->getType();

                $form = $event->getForm();
                $form->add('settings', get_class($type->getInnerType()), $config->getOptions());

                // Adds each child separately in case the settings form was built inline.
                $settings = $form->get('settings');
                foreach ($widgetTypeForm->all() as $child) {
                    $settings->add($child);
                }

                $settings->setData($widgetTypeForm->getData());
            }
        );

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            static function (FormEvent $event) {
                $data = $event->getData();
                if ($data instanceof ContentWidget && $data->getId()) {
                    $event->getForm()->remove('widgetType');
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (isset($view->children['settings'])) {
            foreach ($view->children['settings'] as $child) {
                if (!isset($child->vars['block'])) {
                    $child->vars['block'] = 'additional_information';
                }
            }
        } else {
            unset($view->vars['block_config']['additional_information']);
        }

        $this->updateBlockConfig($view);
    }

    /**
     * @param FormView $view
     */
    private function updateBlockConfig(FormView $view): void
    {
        if (isset($view->vars['block_config'])) {
            foreach ($view->vars['block_config'] as $block => $config) {
                $view->vars['block_config'][$block]['title'] = $this->translator->trans($config['title']);
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

        $resolver->setAllowedTypes('widget_type_form', ['null', FormInterface::class]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_cms_content_widget';
    }
}
