<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;

class ProductPriceListAwareSelectType extends AbstractType
{
    const NAME = 'orob2b_pricing_product_price_list_aware_select';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'orob2b_pricing_price_list_aware_products_list'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ProductSelectType::NAME;
    }
}
