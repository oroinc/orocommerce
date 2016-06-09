<?php

namespace OroB2B\Bundle\PricingBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductType;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSelectType;

class PriceAttributesProductFormExtension extends AbstractTypeExtension
{
    const PRODUCT_PRICE_ATTRIBUTES_PRICES = 'productPriceAttributesPrices';

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ProductType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(self::PRODUCT_PRICE_ATTRIBUTES_PRICES, 'collection', [
            'mapped' => false,
            'type' => PriceListSelectType::NAME
        ]);
    }
}
