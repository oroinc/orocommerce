<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\CMSBundle\Provider\ContentWidgetTypeProvider;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Covers logic of selecting content widget type.
 */
class ContentWidgetTypeSelectType extends AbstractType
{
    /** @var ContentWidgetTypeProvider */
    private $contentWidgetTypeProvider;

    public function __construct(ContentWidgetTypeProvider $contentWidgetTypeProvider)
    {
        $this->contentWidgetTypeProvider = $contentWidgetTypeProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (empty($options['choices'])) {
            $options['configs']['placeholder'] = 'oro.cms.contentwidget.form.no_available_content_widget_types';
        }

        $view->vars = array_replace_recursive($view->vars, ['configs' => $options['configs']]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'choices' => $this->contentWidgetTypeProvider->getAvailableContentWidgetTypes(),
                'placeholder' => 'oro.cms.contentwidget.form.choose_content_widget_type',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): string
    {
        return Select2ChoiceType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_cms_content_widget_type_select';
    }
}
