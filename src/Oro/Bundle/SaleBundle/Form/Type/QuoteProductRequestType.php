<?php

namespace Oro\Bundle\SaleBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\SaleBundle\Entity\QuoteProductRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for QuoteProductRequest entity.
 */
class QuoteProductRequestType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
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

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => QuoteProductRequest::class,
                'compact_units' => false,
                'csrf_token_id' => 'sale_quote_product_request',
            ]
        );
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_sale_quote_product_request';
    }
}
