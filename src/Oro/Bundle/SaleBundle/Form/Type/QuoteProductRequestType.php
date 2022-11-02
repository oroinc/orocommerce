<?php

namespace Oro\Bundle\SaleBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for QuoteProductRequest
 */
class QuoteProductRequestType extends AbstractType
{
    const NAME = 'oro_sale_quote_product_request';

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
            ->add(
                'price',
                PriceType::class,
                [
                    'required' => false,
                    'label' => 'oro.sale.quoteproductrequest.price.label',
                    'attr' => [
                        'readonly' => true
                    ]
                ]
            )
            ->add(
                'productUnit',
                ProductUnitSelectionType::class,
                [
                    'label' => 'oro.product.productunit.entity_label',
                    'required' => false,
                    'compact' => $options['compact_units'],
                    'attr' => [
                        'readonly' => true
                    ]
                ]
            )
            ->add(
                'quantity',
                QuantityType::class,
                [
                    'required' => false,
                    'label' => 'oro.sale.quoteproductrequest.quantity.label',
                    'attr' => [
                        'readonly' => true
                    ]
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'    => $this->dataClass,
                'compact_units' => false,
                'csrf_token_id' => 'sale_quote_product_request',
            ]
        );
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
