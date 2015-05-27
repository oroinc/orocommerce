<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class QuoteProductItemType extends PriceType
{
    const NAME = 'orob2b_sale_quote_product_item';

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('quantity', null, [
                'required' => true,
                'label' => 'orob2b.sale.quote.quoteproduct.quoteproductitem.quantity.label',
            ])
            ->add('productUnit', null, [
                'required' => true,
                'label' => 'orob2b.product.productunit.entity_label',
            ])
        ;
        $options['currencies_list'] = null;
        $options['compact'] = false;
        parent::buildForm($builder, $options);
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'OroB2B\Bundle\SaleBundle\Entity\QuoteProductItem',
            'intention' => 'sale_quote_product_item',
            'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
