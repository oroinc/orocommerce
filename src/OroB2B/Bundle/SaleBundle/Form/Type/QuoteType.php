<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;

use OroB2B\Bundle\CustomerBundle\Form\Type\CustomerSelectType;

class QuoteType extends AbstractType
{
    const NAME = 'orob2b_sale_quote';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('qid', 'hidden')
            ->add('owner', 'oro_user_select', [
                'label'     => 'orob2b.sale.quote.owner.label',
                'required'  => true,
            ])
            ->add('accountUser', null, [
                'label'     => 'orob2b.sale.quote.account_user.label',
                'required'  => false,
            ])
            ->add('account', CustomerSelectType::NAME, [
                'label'     => 'orob2b.sale.quote.customer.label',
                'required'  => false,
            ])
            ->add('validUntil', OroDateTimeType::NAME, [
                'label'     => 'orob2b.sale.quote.valid_until.label',
                'required'  => false,
            ])
            ->add(
                'quoteProducts',
                QuoteProductCollectionType::NAME,
                [
                    'add_label' => 'orob2b.sale.quoteproduct.add_label',
                ]
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class'    => 'OroB2B\Bundle\SaleBundle\Entity\Quote',
            'intention'     => 'sale_quote',
            'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
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
