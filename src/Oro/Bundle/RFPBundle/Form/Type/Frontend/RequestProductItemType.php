<?php

namespace Oro\Bundle\RFPBundle\Form\Type\Frontend;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for RequestProductItem
 */
class RequestProductItemType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'price',
                PriceType::class,
                [
                    'required' => true,
                    'by_reference' => false,
                    'validation_groups' => ['Optional'],
                    'currency_empty_value' => null,
                ]
            )
            ->add(
                'productUnit',
                ProductUnitSelectionType::class,
                [
                    'required' => false,
                    'compact' => true,
                ]
            )
            ->add(
                'quantity',
                QuantityType::class,
                [
                    'required' => false,
                    'default_data' => 1,
                    'useInputTypeNumberValueFormat' => true,
                ]
            );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RequestProductItem::class,
        ]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_rfp_frontend_request_product_item';
    }
}
