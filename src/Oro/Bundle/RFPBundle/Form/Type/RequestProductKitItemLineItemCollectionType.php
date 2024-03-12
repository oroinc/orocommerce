<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RFPBundle\Form\EventListener\RequestProductKitItemLineItemCollectionRemovingListener;
use Oro\Bundle\RFPBundle\Form\EventListener\RequestProductKitItemLineItemExistingCollectionListener;
use Symfony\Bridge\Doctrine\Form\EventListener\MergeDoctrineCollectionListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\EventListener\MergeCollectionListener;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type that represents a product kit item line items collection in request product line item.
 */
class RequestProductKitItemLineItemCollectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $entryOptions = [
            'block_name' => 'entry',
        ];

        if ($options['product'] !== null) {
            foreach ($options['product']->getKitItems() as $kitItem) {
                $builder->add(
                    (string)$kitItem->getId(),
                    RequestProductKitItemLineItemType::class,
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
            new RequestProductKitItemLineItemExistingCollectionListener(
                RequestProductKitItemLineItemType::class,
                $entryOptions
            )
        );
        $builder->addEventSubscriber(new RequestProductKitItemLineItemCollectionRemovingListener());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('by_reference', false);

        $resolver
            ->define('product')
            ->default(null)
            ->allowedTypes(Product::class, 'null');
    }

    public function getBlockPrefix(): string
    {
        return 'oro_rfp_request_product_kit_item_line_item_collection';
    }
}
