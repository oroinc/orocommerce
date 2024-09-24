<?php

declare(strict_types=1);

namespace Oro\Bundle\SaleBundle\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SaleBundle\Form\EventListener\QuoteProductKitItemLineItemCollectionRemovingListener;
use Oro\Bundle\SaleBundle\Form\EventListener\QuoteProductKitItemLineItemExistingCollectionListener;
use Symfony\Bridge\Doctrine\Form\EventListener\MergeDoctrineCollectionListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\EventListener\MergeCollectionListener;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type that represents a product kit item line items collection in quote product line item.
 */
class QuoteProductKitItemLineItemCollectionType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $entryOptions = [
            'block_name' => 'entry',
        ];

        if ($options['product'] !== null) {
            foreach ($options['product']->getKitItems() as $kitItem) {
                $builder->add(
                    (string)$kitItem->getId(),
                    QuoteProductKitItemLineItemType::class,
                    $entryOptions + [
                        'required' => !$kitItem->isOptional(),
                        'property_path' => '[' . $kitItem->getId() . ']',
                        'product_kit_item' => $kitItem,
                        'error_bubbling' => false,
                    ]
                );
            }
        }

        $builder->addEventSubscriber(new MergeDoctrineCollectionListener());
        $builder->addEventSubscriber(new MergeCollectionListener(true, true));
        $builder->addEventSubscriber(
            new QuoteProductKitItemLineItemExistingCollectionListener(
                QuoteProductKitItemLineItemType::class,
                $entryOptions
            )
        );
        $builder->addEventSubscriber(new QuoteProductKitItemLineItemCollectionRemovingListener());
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('by_reference', false);
        $resolver->setDefault('error_bubbling', false);

        $resolver
            ->define('product')
            ->default(null)
            ->allowedTypes(Product::class, 'null');
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_sale_quote_product_kit_item_line_item_collection';
    }
}
