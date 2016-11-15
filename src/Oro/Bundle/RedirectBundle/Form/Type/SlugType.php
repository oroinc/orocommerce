<?php

namespace Oro\Bundle\RedirectBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SlugType extends AbstractType
{

    const NAME = 'oro_redirect_slug';

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return $this->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return TextType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'target_field_name' => 'titles',
            'slugify_component' => 'ororedirect/js/app/components/text-field-slugify-component',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['slugify_component'] = $options['slugify_component'];
        $targetFullName = $view->parent->vars['full_name'].'['.$options['target_field_name'].']';
        $view->vars['slugify_component_options'] = [
            'target' => $targetFullName,
            'recipient' => $view->vars['full_name']
        ];
    }
}
