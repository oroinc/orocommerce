<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CurrencyBundle\Form\Type\OptionalPriceType as PriceType;

//use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitRemovedSelectionType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType as ProductUnitRemovedSelectionType;

class QuoteProductRequestType extends AbstractType
{
    const NAME = 'orob2b_sale_quote_product_request';

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
            ->add('quantity', 'integer', [
                'required'  => false,
                'label'     => 'orob2b.sale.quoteproductrequest.quantity.label',
            ])
            ->add('price', PriceType::NAME, [
                'required'  => false,
                'label'     => 'orob2b.sale.quoteproductrequest.price.label',
            ])
            ->add('productUnit', ProductUnitRemovedSelectionType::NAME, [
                'label' => 'orob2b.product.productunit.entity_label',
                'required' => true,
            ]);
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'    => $this->dataClass,
            'intention'     => 'sale_quote_product_request',
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
