<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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

    /**
     * {@inheritdoc}
     */
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
