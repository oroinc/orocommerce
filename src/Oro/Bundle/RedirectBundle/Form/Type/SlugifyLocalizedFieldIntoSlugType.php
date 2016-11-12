<?php

namespace Oro\Bundle\RedirectBundle\Form\Type;

use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SlugifyLocalizedFieldIntoSlugType extends LocalizedFallbackValueCollectionType
{
    use SlugifyFieldIntoSlugTrait;

    const NAME = 'oro_redirect_slugify_localized_field_into_slug';
    const COMPONENT = 'ororedirect/js/app/components/localized-field-slugify-component';

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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

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
