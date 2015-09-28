<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserSelectType;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountSelectType;

class QuoteType extends AbstractType
{
    const NAME = 'orob2b_sale_quote';

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
            ->add('qid', 'hidden')
            ->add('owner', 'oro_user_select', [
                'label'     => 'orob2b.sale.quote.owner.label',
                'required'  => true,
            ])
            ->add('accountUser', AccountUserSelectType::NAME, [
                'label'     => 'orob2b.sale.quote.account_user.label',
                'required'  => false,
            ])
            ->add('account', AccountSelectType::NAME, [
                'label'     => 'orob2b.sale.quote.account.label',
                'required'  => false,
            ])
            ->add('validUntil', OroDateTimeType::NAME, [
                'label'     => 'orob2b.sale.quote.valid_until.label',
                'required'  => false,
            ])
            ->add('locked', 'checkbox', [
                'label' => 'orob2b.sale.quote.locked.label',
                'required'  => false,
            ])
            ->add(
                'quoteProducts',
                QuoteProductCollectionType::NAME,
                [
                    'add_label' => 'orob2b.sale.quoteproduct.add_label',
                    'options' => [
                        'compact_units' => true,
                    ],
                ]
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'    => $this->dataClass,
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
