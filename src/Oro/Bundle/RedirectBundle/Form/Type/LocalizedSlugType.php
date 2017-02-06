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
                while ($form->getParent()) {
                    $form = $form->getParent();
                }

                $data = $form->getData();
                if ($data instanceof UpdatedAtAwareInterface) {
                    $data->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
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
            'slug_suggestion_enabled' => true,
            'slugify_route' => 'oro_api_slugify_slug',
        ]);
        $resolver->setDefined('source_field');
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($options['slug_suggestion_enabled']
            && isset($options['source_field'])
            && !empty($view->parent->vars['full_name'])
        ) {
            $parent = $view->parent;
            while ($parent->parent) {
                $parent = $parent->parent;
            }
            $sourceFieldName = sprintf('%s[%s]', $parent->vars['full_name'], $options['source_field']);
            $targetFieldName = $view->vars['full_name'];

            $view->vars['slugify_component_options'] = [
                'source' => sprintf('[name^="%s[values]"]', $sourceFieldName),
                'target' => sprintf('[name^="%s[values]"]', $targetFieldName),
                'slugify_route' => $options['slugify_route'],
            ];
        }
    }
}
