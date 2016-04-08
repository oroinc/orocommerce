<?php

namespace OroB2B\Bundle\TaxBundle\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductType;
use OroB2B\Bundle\TaxBundle\Entity\AbstractTaxCode;
use OroB2B\Bundle\TaxBundle\Entity\ProductTaxCode;
use OroB2B\Bundle\TaxBundle\Entity\Repository\ProductTaxCodeRepository;
use OroB2B\Bundle\TaxBundle\Form\Type\ProductTaxCodeAutocompleteType;

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
                    'label' => 'orob2b.tax.taxcode.label',
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
