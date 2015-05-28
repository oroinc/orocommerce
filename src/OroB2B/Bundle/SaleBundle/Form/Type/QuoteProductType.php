<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class QuoteProductType extends AbstractType
{
    const NAME = 'orob2b_sale_quote_product';

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('product', null, [
                'required' => true,
                'label' => 'orob2b.product.entity_label'
            ])
            ->add(
                'quoteProductItems',
                'oro_collection',
                [
                    'label' => 'orob2b.sale.quote.quoteproduct.quoteproductitem.entity_plural_label',
                    'required' => false,
                    'type' => QuoteProductItemType::NAME,
                    'show_form_when_empty' => false
                ]
            )
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'OroB2B\Bundle\SaleBundle\Entity\QuoteProduct',
            'intention' => 'sale_quote_product',
            'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
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
