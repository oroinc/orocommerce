<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\BuildQueryProductListEvent;
use Oro\Bundle\ProductBundle\Event\BuildResultProductListEvent;
use Oro\Bundle\UIBundle\Tools\UrlHelper;

/**
 * Adds common fields to storefront product lists.
 */
class ProductListEventListener
{
    private ImagePlaceholderProviderInterface $imagePlaceholderProvider;

    private WebpConfiguration $webpConfiguration;

    private UrlHelper $urlHelper;

    public function __construct(
        ImagePlaceholderProviderInterface $imagePlaceholderProvider,
        WebpConfiguration $webpConfiguration,
        UrlHelper $urlHelper
    ) {
        $this->imagePlaceholderProvider = $imagePlaceholderProvider;
        $this->webpConfiguration = $webpConfiguration;
        $this->urlHelper = $urlHelper;
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
        foreach ($event->getProductData() as $productId => $data) {
            $productView = $event->getProductView($productId);
            $productType = $data['type'];
            $productView->set('type', $productType);
            $productView->set('sku', $data['sku']);
            $productView->set('name', $data['name']);
            $productView->set('hasImage', $data['image'] !== '');
            $productView->set('image', $this->getProductImageUrl($data['image'], 'product_large'));
            if (isset($data['imageWebp'])) {
                $productView->set(
                    'imageWebp',
                    $this->getProductImageUrl($data['imageWebp'], 'product_large', 'webp')
                );
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

    private function getProductImageUrl(string $path, string $placeholderFilter, string $format = '')
    {
        if ($path !== '') {
            // The image URL obtained from the search index does not contain a base url
            // so may not represent an absolute path.
            $imageUrl = $this->urlHelper->getAbsolutePath($path);
        } else {
            $imageUrl = $this->imagePlaceholderProvider->getPath($placeholderFilter, $format);
        }

        return $imageUrl;
    }
}
