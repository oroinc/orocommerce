<?php

namespace Oro\Bundle\RedirectBundle\Form\Type;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityBundle\EntityProperty\UpdatedAtAwareInterface;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocalizedSlugType extends AbstractType
{
    const NAME = 'oro_redirect_localized_slug';
    const CREATE_REDIRECT_OPTION_NAME = 'createRedirect';

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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Change update at of owning entity on slug collection change
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                if ($form->getParent()) {
                    $data = $form->getParent()->getData();
                    if ($data instanceof UpdatedAtAwareInterface) {
                        $data->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
                    }
                }
            }
        );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        if ($event->getForm()->getConfig()->getOption('create_redirect_enabled')
            && $event->getData() instanceof Collection && $event->getData()->count()
        ) {
            $event->getForm()->add(
                self::CREATE_REDIRECT_OPTION_NAME,
                CheckboxType::class,
                [
                    'label' => 'oro.redirect.confirm_slug_change.checkbox_label',
                    'data' => true,
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'slug_suggestion_enabled' => false,
            'slugify_route' => 'oro_api_slugify_slug',
            'create_redirect_enabled' => false,
            'localized_slug_component' => 'ororedirect/js/app/components/localized-slug-component'
        ]);
        $resolver->setDefined('source_field');
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($options['slug_suggestion_enabled'] && isset($options['source_field'])) {
            $view->vars['localized_slug_component'] = $options['localized_slug_component'];
            $view->vars['localized_slug_component_options']['slugify_component_options'] = [
                'source' => '[name^="'.$view->parent->vars['full_name'].'['.$options['source_field'].']'.'[values]"]',
                'target' => '[name^="'.$view->vars['full_name'].'[values]"]',
                'slugify_route' => $options['slugify_route'],
            ];
        }
        if ($options['create_redirect_enabled']
            && $form->getData() instanceof Collection && $form->getData()->count()
        ) {
            $view->vars['localized_slug_component'] = $options['localized_slug_component'];
            $view->vars['localized_slug_component_options']['confirmation_component_options'] = [
                'slugFields' => '[name^="'.$view->vars['full_name'].'[values]"]',
                'createRedirectCheckbox' =>
                    '[name^="'.$view->vars['full_name'].'['.self::CREATE_REDIRECT_OPTION_NAME.']"]',
            ];
        }
    }
}
