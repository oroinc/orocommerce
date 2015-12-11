<?php

namespace OroB2B\Bundle\TaxBundle\Form\Type;

use OroB2B\src\OroB2B\Bundle\TaxBundle\Form\Type\TaxSelectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaxRuleType extends AbstractType
{
    const NAME = 'orob2b_tax_tax_rule_type';

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
                'label' => 'orob2b.tax.taxrule.description.label',
                'required' => true
            ])
            ->add('tax', TaxSelectType::NAME, [
//                'label' => 'test'
            ])
            ;
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
        return self::NAME;
    }
}
