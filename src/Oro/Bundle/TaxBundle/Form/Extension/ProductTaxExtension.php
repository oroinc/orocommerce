<?php

namespace Oro\Bundle\TaxBundle\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Entity\Repository\ProductTaxCodeRepository;
use Oro\Bundle\TaxBundle\Form\Type\ProductTaxCodeAutocompleteType;

class ProductTaxExtension extends AbstractTaxExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ProductType::NAME;
    }

    /** {@inheritdoc} */
    protected function addTaxCodeField(FormBuilderInterface $builder)
    {
        $builder
            ->add(
                'taxCode',
                ProductTaxCodeAutocompleteType::NAME,
                [
                    'required' => false,
                    'mapped' => false,
                    'label' => 'oro.tax.taxcode.label',
                    'create_form_route' => null,
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
        if ($taxCode) {
            $taxCode->removeProduct($product);
        }

        if ($taxCodeNew) {
            $taxCodeNew->addProduct($product);
        }
    }

    /**
     * @param Product $product
     * @return ProductTaxCode|null
     */
    protected function getTaxCode($product)
    {
        /** @var ProductTaxCodeRepository $repository */
        $repository = $this->getRepository();

        return $repository->findOneByProduct($product);
    }
}
