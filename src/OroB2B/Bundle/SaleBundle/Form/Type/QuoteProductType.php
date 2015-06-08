<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;

class QuoteProductType extends AbstractType
{
    const NAME = 'orob2b_sale_quote_product';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('product', null, [
                'required'  => true,
                'label'     => 'orob2b.product.entity_label',
            ])
            ->add(
                'quoteProductItems',
                QuoteProductItemCollectionType::NAME,
                [
                    'label'     => 'orob2b.sale.quote.quoteproduct.quoteproductitem.entity_plural_label',
                    'add_label' => 'orob2b.sale.quote.quoteproduct.quoteproductitem.add_label',
                ]
            )
        ;
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class'    => 'OroB2B\Bundle\SaleBundle\Entity\QuoteProduct',
            'intention'     => 'sale_quote_product',
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

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        /* @var $quoteProduct QuoteProduct */
        $quoteProduct = $event->getData();
        $form = $event->getForm();

        if ($quoteProduct && null !== $quoteProduct->getId()) {
            $product = $quoteProduct->getProduct();
            if (!$product) {
                $form->add(
                    'product',
                    null,
                    [
                        'required'      => true,
                        'label'         => 'orob2b.product.entity_label',
                        'empty_value'   => $quoteProduct->getProductSku() . ' - removed'
                    ]
                );
            }
        }
    }
}
