<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Oro\Bundle\OrderBundle\Form\Type\OrderCollectionTableType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for Applied Promotion Collection.
 */
class AppliedPromotionCollectionTableType extends AbstractType
{
    const NAME = 'oro_promotion_applied_promotion_collection_table';

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return OrderCollectionTableType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'template_name' => '@OroPromotion/AppliedPromotion/applied_promotions_edit_table.html.twig',
                'page_component' => 'oroui/js/app/components/view-component',
                'page_component_options' => ['view' => 'oropromotion/js/app/views/applied-promotion-collection-view'],
                'attr' => ['class' => 'oro-promotions-collection'],
                'entry_type' => AppliedPromotionType::class,
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
