<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class QuoteType extends AbstractType
{
    
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('qid', 'hidden')
            ->add('owner', null, [
                'required' => true,
                'label' => 'orob2b.sale.quote.owner.label',
            ])
            ->add('validUntil', null, [
                'required' => false,
                'label' => 'orob2b.sale.quote.valid_until.label',
            ])
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'OroB2B\Bundle\SaleBundle\Entity\Quote',
            'intention' => 'sale_quote',
            'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'orob2b_sale_quote';
    }
}
