<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AppliedCouponCollectionType extends AbstractType
{
    const NAME = 'oro_promotion_applied_coupon_collection';

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CollectionType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $options['page_component_options']['dialogWidgetAlias'] = $options['dialog_widget_alias'];
        $view->vars['dialogWidgetAlias'] = $options['dialog_widget_alias'];
        $view->vars['entity'] = $options['entity'];
        $view->vars = array_replace_recursive($view->vars, ['attr' => [
            'data-page-component-view' => $options['page_component_view'],
            'data-page-component-options' => json_encode($options['page_component_options']),
        ]]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['entity']);
        $resolver->setDefaults(
            [
                'entry_type' => AppliedCouponType::class,
                'dialog_widget_alias' => 'add-coupons-dialog',
                'page_component_view' => 'oropromotion/js/app/views/applied-coupon-collection-view',
                'page_component_options' => [],
                'error_bubbling' => false,
                'prototype' => true,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype_name' => '__applied_coupon_collection_item__',
                'by_reference' => false
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
}
