<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaxRuleType extends AbstractType
{
    const NAME = 'orob2b_tax_rule_type';

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

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('description', 'textarea', [
                'label' => 'oro.tax.taxrule.description.label',
                'required' => false
            ])
            ->add('accountTaxCode', AccountTaxCodeAutocompleteType::NAME, [
                'label' => 'oro.tax.taxrule.account_tax_code.label',
                'required' => true
            ])
            ->add('productTaxCode', ProductTaxCodeAutocompleteType::NAME, [
                'label' => 'oro.tax.taxrule.product_tax_code.label',
                'required' => true

            ])
            ->add('tax', TaxSelectType::NAME, [
                'label' => 'oro.tax.taxrule.tax.label',
                'required' => true
            ])
            ->add('taxJurisdiction', TaxJurisdictionSelectType::NAME, [
                'label' => 'oro.tax.taxrule.tax_jurisdiction.label',
                'required' => true
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
        ]);
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
