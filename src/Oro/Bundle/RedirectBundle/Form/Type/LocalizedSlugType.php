<?php

namespace Oro\Bundle\RedirectBundle\Form\Type;

use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocalizedSlugType extends AbstractType
{
    const NAME = 'oro_redirect_localized_slug';

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
        return LocalizedFallbackValueCollectionType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'source_field' => 'titles',
            'slugify_component' => 'ororedirect/js/app/components/localized-field-slugify-component',
            'slugify_route' => 'oro_api_slugify_slug',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['slugify_component'] = $options['slugify_component'];
        $view->vars['slugify_component_options'] = [
            'source' => '[name^="'.$view->parent->vars['full_name'].'['.$options['source_field'].']'.'[values]"]',
            'target' => '[name^="'.$view->vars['full_name'].'[values]"]',
            'slugify_route' => $options['slugify_route'],
        ];
    }
}
