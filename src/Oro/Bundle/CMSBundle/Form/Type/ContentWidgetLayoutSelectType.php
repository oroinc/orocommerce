<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\CMSBundle\Provider\ContentWidgetLayoutProvider;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Covers logic of selecting content widget layout.
 */
class ContentWidgetLayoutSelectType extends AbstractType
{
    /** @var ContentWidgetLayoutProvider */
    private $widgetLayoutProvider;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(ContentWidgetLayoutProvider $widgetLayoutProvider, TranslatorInterface $translator)
    {
        $this->widgetLayoutProvider = $widgetLayoutProvider;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'widget_type' => null,
                'placeholder' => 'oro.cms.contentwidget.form.choose_content_widget_layout',
                'required' => false,
            ]
        );
        $resolver->setRequired('widget_type');
        $resolver->setAllowedTypes('widget_type', ['string', 'null']);
        $resolver->setNormalizer(
            'choices',
            function (Options $options) {
                $widgetType = $options['widget_type'];
                if (!$widgetType) {
                    return [];
                }

                $layouts = $this->widgetLayoutProvider->getWidgetLayouts($widgetType);

                $choices = array_map(
                    function (?string $label, string $layoutName) {
                        return !empty($label) ? $this->translator->trans($label) : $layoutName;
                    },
                    $layouts,
                    array_keys($layouts)
                );

                return array_combine($choices, array_keys($layouts));
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if (!$view->vars['choices']) {
            $class = 'hide ';
            $class .= $view->vars['attr']['class'] ?? '';

            $view->vars['attr']['class'] = $class;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): string
    {
        return Select2ChoiceType::class;
    }
}
