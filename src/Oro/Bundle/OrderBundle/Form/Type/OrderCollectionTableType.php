<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderCollectionTableType extends AbstractType
{
    const NAME = 'oro_order_collection_table';

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars = array_replace_recursive($view->vars, [
            'template_name' => $options['template_name'],
            'attr' => [
                'data-page-component-module' => $options['page_component'],
                'data-page-component-options' => json_encode($options['page_component_options'])
            ]
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['page_component', 'template_name']);
        $resolver->setDefined(['page_component_options']);
        $resolver->setAllowedTypes('page_component', 'string');
        $resolver->setAllowedTypes('page_component_options', 'array');
        $resolver->setAllowedTypes('template_name', 'string');

        $resolver->setDefaults([
            'error_bubbling' => false,
            'prototype' => true,
            'allow_add' => true,
            'allow_delete' => true,
            'page_component_options' => [],
            'prototype_name' => '__table_collection_item__',
            'by_reference' => false
        ]);
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
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CollectionType::class;
    }
}
