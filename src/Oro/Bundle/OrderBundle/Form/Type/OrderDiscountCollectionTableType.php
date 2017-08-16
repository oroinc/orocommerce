<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Oro\Bundle\OrderBundle\Provider\DiscountSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderDiscountCollectionTableType extends AbstractType
{
    const NAME = 'oro_order_discount_collection_table';

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'collection';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['page_component', 'page_component_options']);
        $resolver->setAllowedTypes('page_component', 'string');
        $resolver->setAllowedTypes('page_component_options', 'array');

        $resolver->setDefaults(
            [
                'page_component' => 'oroui/js/app/components/view-component',
                'page_component_options' => [
                    'view' => 'oroorder/js/app/views/discount-items-view',
                    'discountType' => DiscountSubtotalProvider::TYPE,
                    'totalType' => LineItemSubtotalProvider::TYPE,
                ],
                'type' => OrderDiscountCollectionRowType::NAME,
                'error_bubbling' => false,
                'prototype' => true,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype_name' => '__order_discount_row__',
                'by_reference' => false,
            ]
        );
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
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr']['data-page-component-module'] = $options['page_component'];
        $view->vars['attr']['data-page-component-options'] = json_encode($options['page_component_options']);
    }
}
