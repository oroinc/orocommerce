<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Form\Type;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use Oro\Bundle\ShoppingListBundle\ProductKit\Provider\ProductKitItemProductsProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type that represents a shopping list line item of a product kit item.
 */
class ProductKitItemLineItemType extends AbstractType
{
    private ProductKitItemProductsProvider $kitItemProductsProvider;

    private DataTransformerInterface $productToIdDataTransformer;

    public function __construct(
        ProductKitItemProductsProvider $productKitItemProductsProvider,
        DataTransformerInterface $productToIdDataTransformer
    ) {
        $this->kitItemProductsProvider = $productKitItemProductsProvider;
        $this->productToIdDataTransformer = $productToIdDataTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                $builder
                    ->create('product', ChoiceType::class, [
                        'required' => false,
                        'expanded' => true,
                        'multiple' => false,
                        'choices' => [],
                    ])
                    ->addModelTransformer($this->productToIdDataTransformer)
            )
            ->add('quantity', QuantityType::class, ['required' => false, 'useInputTypeNumberValueFormat' => true]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (PreSetDataEvent $event) {
            /** @var ProductKitItemLineItem|null $kitItemLineItem */
            $kitItemLineItem = $event->getData();
            if ($kitItemLineItem === null) {
                return;
            }

            $choices = $this->kitItemProductsProvider->getProductsAvailableForPurchase($kitItemLineItem->getKitItem());
            $isOptional = $kitItemLineItem->getKitItem()?->isOptional();
            if ($isOptional) {
                $choices[] = null;
            }

            $form = $event->getForm();

            FormUtils::replaceField($form, 'product', [
                'required' => !$isOptional,
                'choices' => $choices,
                'choice_value' => 'id',
            ]);

            FormUtils::replaceField($form, 'quantity', ['required' => !$isOptional]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductKitItemLineItem::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'oro_product_kit_item_line_item';
    }
}
