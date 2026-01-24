<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for creating and editing tax rules.
 *
 * Tax rules define the relationship between customer tax codes, product tax codes, tax rates, and tax jurisdictions.
 * This form allows administrators to configure which tax rate applies when a specific combination of customer type,
 * product type, and location is encountered during tax calculation.
 */
class TaxRuleType extends AbstractType
{
    const NAME = 'oro_tax_rule_type';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('description', TextareaType::class, [
                'label' => 'oro.tax.taxrule.description.label',
                'required' => false
            ])
            ->add('customerTaxCode', CustomerTaxCodeAutocompleteType::class, [
                'label' => 'oro.tax.taxrule.customer_tax_code.label',
                'required' => true
            ])
            ->add('productTaxCode', ProductTaxCodeAutocompleteType::class, [
                'label' => 'oro.tax.taxrule.product_tax_code.label',
                'required' => true

            ])
            ->add('tax', TaxSelectType::class, [
                'label' => 'oro.tax.taxrule.tax.label',
                'required' => true
            ])
            ->add('taxJurisdiction', TaxJurisdictionSelectType::class, [
                'label' => 'oro.tax.taxrule.tax_jurisdiction.label',
                'required' => true
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
        ]);
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
