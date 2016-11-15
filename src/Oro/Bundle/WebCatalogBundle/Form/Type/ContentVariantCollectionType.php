<?php

namespace Oro\Bundle\WebCatalogBundle\Form\Type;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContentVariantCollectionType extends AbstractType
{
    const NAME = 'oro_web_catalog_content_variant_collection';

    /**
     * @var EventSubscriberInterface
     */
    private $resizeSubscriber;

    /**
     * @param EventSubscriberInterface $resizeSubscriber
     */
    public function __construct(EventSubscriberInterface $resizeSubscriber)
    {
        $this->resizeSubscriber = $resizeSubscriber;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'prototype_name' => '__name__'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars = array_replace(
            $view->vars,
            [
                'prototype_name' => $options['prototype_name']
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->resizeSubscriber);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return static::NAME;
    }
}
