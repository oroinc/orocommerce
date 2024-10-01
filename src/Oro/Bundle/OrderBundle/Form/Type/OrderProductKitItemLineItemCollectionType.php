<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Form\Type;

use Oro\Bundle\OrderBundle\Form\Type\EventListener\OrderProductKitItemLineItemCollectionRemovingListener;
use Oro\Bundle\OrderBundle\Form\Type\EventListener\OrderProductKitItemLineItemExistingCollectionListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Bridge\Doctrine\Form\EventListener\MergeDoctrineCollectionListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\EventListener\MergeCollectionListener;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type that represents a product kit item line items collection in an order line item.
 */
class OrderProductKitItemLineItemCollectionType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $entryOptions = [
            'currency' => $options['currency'],
            'block_name' => 'entry',
        ];

        if ($options['product'] !== null) {
            foreach ($options['product']->getKitItems() as $kitItem) {
                $builder->add(
                    (string)$kitItem->getId(),
                    OrderProductKitItemLineItemType::class,
                    $entryOptions + [
                        'required' => !$kitItem->isOptional(),
                        'property_path' => '[' . $kitItem->getId() . ']',
                        'product_kit_item' => $kitItem,
                    ]
                );
            }
        }

        $builder->addEventSubscriber(new MergeDoctrineCollectionListener());
        $builder->addEventSubscriber(new MergeCollectionListener(true, true));
        $builder->addEventSubscriber(
            new OrderProductKitItemLineItemExistingCollectionListener(
                OrderProductKitItemLineItemType::class,
                $entryOptions
            )
        );
        $builder->addEventSubscriber(new OrderProductKitItemLineItemCollectionRemovingListener());
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('by_reference', false);

        $resolver
            ->define('currency')
            ->default(null)
            ->allowedTypes('string', 'null');

        $resolver
            ->define('product')
            ->default(null)
            ->allowedTypes(Product::class, 'null');
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_order_product_kit_item_line_item_collection';
    }
}
