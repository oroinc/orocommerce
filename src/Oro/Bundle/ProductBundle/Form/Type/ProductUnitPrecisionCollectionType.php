<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Product unit precision collection form.
 */
class ProductUnitPrecisionCollectionType extends AbstractType
{
    const NAME = 'oro_product_unit_precision_collection';

    /**
     * {@inheritdoc}
     */
    public function getParent(): ?string
    {
        return CollectionType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'entry_type' => ProductUnitPrecisionType::class,
                'show_form_when_empty' => false,
                'check_field_name' => null
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
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
