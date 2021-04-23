<?php

namespace Oro\Bundle\TaxBundle\Form\Extension;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Form\Type\ProductTaxCodeAutocompleteType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Stands for handling tax code for Product.
 */
class ProductTaxExtension extends AbstractTaxExtension
{
    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [ProductType::class];
    }

    /** {@inheritdoc} */
    protected function addTaxCodeField(FormBuilderInterface $builder)
    {
        $builder
            ->add(
                'taxCode',
                ProductTaxCodeAutocompleteType::class,
                [
                    'required' => false,
                    'mapped' => false,
                    'label' => 'oro.tax.taxcode.label',
                    'create_form_route' => null,
                    'dynamic_fields_ignore_exception' => true,
                ]
            );
    }

    /**
     * @param Product $product
     * @param ProductTaxCode|AbstractTaxCode $taxCode
     * @param ProductTaxCode|AbstractTaxCode $taxCodeNew
     */
    protected function handleTaxCode($product, AbstractTaxCode $taxCode = null, AbstractTaxCode $taxCodeNew = null)
    {
        $product->setTaxCode($taxCodeNew);
    }

    /**
     * @param Product $product
     * @return ProductTaxCode|null
     */
    protected function getTaxCode($product)
    {
        return $product->getTaxCode();
    }
}
