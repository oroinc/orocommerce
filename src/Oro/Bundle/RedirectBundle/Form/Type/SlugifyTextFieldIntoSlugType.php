<?php

namespace Oro\Bundle\RedirectBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SlugifyTextFieldIntoSlugType extends TextType
{
    use SlugifyFieldIntoSlugTrait;

    const NAME = 'oro_redirect_slugify_text_field_into_slug';
    const COMPONENT = 'ororedirect/js/app/components/text-field-slugify-component';

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return static::NAME;
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
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'target_field_name' => ''
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $this->addComponentOptions($view, $options);
    }

    public function getComponent()
    {
        return static::COMPONENT;
    }
}
