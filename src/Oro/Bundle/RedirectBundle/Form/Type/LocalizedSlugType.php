<?php

namespace Oro\Bundle\RedirectBundle\Form\Type;

use Oro\Bundle\EntityBundle\EntityProperty\UpdatedAtAwareInterface;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'slugify_component' => 'ororedirect/js/app/components/localized-field-slugify-component',
            'slugify_route' => 'oro_api_slugify_slug',
        ]);
        $resolver->setRequired('source_field');
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
