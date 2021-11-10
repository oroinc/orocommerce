<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\BuildQueryProductListEvent;
use Oro\Bundle\ProductBundle\Event\BuildResultProductListEvent;

/**
 * Adds common fields to storefront product lists.
 */
class ProductListEventListener
{
    private ImagePlaceholderProviderInterface $imagePlaceholderProvider;

    public function __construct(ImagePlaceholderProviderInterface $imagePlaceholderProvider)
    {
        $this->imagePlaceholderProvider = $imagePlaceholderProvider;
    }

    public function onBuildQuery(BuildQueryProductListEvent $event): void
    {
        $event->getQuery()
            ->addSelect('text.type')
            ->addSelect('text.sku')
            ->addSelect('text.names_LOCALIZATION_ID as name')
            ->addSelect('text.image_product_large as image')
            ->addSelect('text.primary_unit as unit')
            ->addSelect('text.product_units')
            ->addSelect('integer.newArrival')
            ->addSelect('integer.variant_fields_count');
    }

    public function onBuildResult(BuildResultProductListEvent $event): void
    {
        $noImagePath = false;
        foreach ($event->getProductData() as $productId => $data) {
            $hasProductImage = true;
            $productImageUrl = $data['image'] ?: null;
            if (!$productImageUrl) {
                if (false === $noImagePath) {
                    $noImagePath = $this->imagePlaceholderProvider->getPath('product_large');
                }
                $hasProductImage = false;
                $productImageUrl = $noImagePath;
            }

            $productView = $event->getProductView($productId);
            $productType = $data['type'];
            $productView->set('type', $productType);
            $productView->set('sku', $data['sku']);
            $productView->set('name', $data['name']);
            $productView->set('hasImage', $hasProductImage);
            $productView->set('image', $productImageUrl);
            $productView->set('unit', $data['unit']);
            $productView->set(
                'product_units',
                $data['product_units'] ? unserialize($data['product_units'], ['allowed_classes' => false]) : []
            );
            $productView->set('newArrival', (bool)$data['newArrival']);
            $productView->set(
                'variant_fields_count',
                Product::TYPE_CONFIGURABLE === $productType ? $data['variant_fields_count'] : null
            );
        }
    }
}
