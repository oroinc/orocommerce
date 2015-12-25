<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use OroB2B\Bundle\ProductBundle\Form\DataTransformer\QuickAddRowCollectionToQuickAddOrderTransformer;

class QuickAddOrderType extends AbstractType
{
    const NAME = 'orob2b_product_quick_add_order';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(QuickAddType::PRODUCTS_FIELD_NAME, QuickAddCollectionType::NAME)
            ->add(QuickAddType::COMPONENT_FIELD_NAME, 'hidden')
            ->add(QuickAddType::ADDITIONAL_FIELD_NAME, 'hidden');

        $builder->addModelTransformer(new QuickAddRowCollectionToQuickAddOrderTransformer());
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
