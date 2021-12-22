<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
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

    private WebpConfiguration $webpConfiguration;

    public function __construct(
        ImagePlaceholderProviderInterface $imagePlaceholderProvider,
        WebpConfiguration $webpConfiguration
    ) {
        $this->imagePlaceholderProvider = $imagePlaceholderProvider;
        $this->webpConfiguration = $webpConfiguration;
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

        if ($this->webpConfiguration->isEnabledIfSupported()) {
            $event->getQuery()->addSelect('text.image_product_large_webp as imageWebp');
        }
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
            if (isset($data['imageWebp'])) {
                $productView->set('imageWebp', $data['imageWebp']);
            }
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
