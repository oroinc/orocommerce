<?php

namespace Oro\Bundle\TaxBundle\Form\Extension;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Form\Type\ProductTaxCodeAutocompleteType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Handles tax code for Product.
 */
class ProductTaxExtension extends AbstractTaxExtension
{
    /**
     * {@inheritDoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [ProductType::class];
    }

    /**
     * {@inheritDoc}
     */
    protected function addTaxCodeField(FormBuilderInterface $builder): void
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
     * {@inheritDoc}
     */
    protected function handleTaxCode(object $entity, ?AbstractTaxCode $taxCode, ?AbstractTaxCode $taxCodeNew): void
    {
        /** @var Product $entity */
        /** @var ProductTaxCode|null $taxCodeNew */
        $entity->setTaxCode($taxCodeNew);
    }

    /**
     * {@inheritDoc}
     */
    protected function getTaxCode(object $entity): ?AbstractTaxCode
    {
        /** @var Product $entity */
        return $entity->getTaxCode();
    }
}
