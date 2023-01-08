<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Oro\Bundle\ProductBundle\Event\CollectAutocompleteFieldsEvent;
use Oro\Bundle\ProductBundle\Event\ProcessAutocompleteDataEvent;
use Oro\Bundle\UIBundle\Tools\UrlHelper;

/**
 * Adds small product image in webp format to the search autocomplete data.
 */
class WebpAwareProductAutocompleteListener
{
    private WebpConfiguration $webpConfiguration;

    private ImagePlaceholderProviderInterface $imagePlaceholderProvider;

    private UrlHelper $urlHelper;

    public function __construct(
        WebpConfiguration $webpConfiguration,
        ImagePlaceholderProviderInterface $imagePlaceholderProvider,
        UrlHelper $urlHelper
    ) {
        $this->webpConfiguration = $webpConfiguration;
        $this->imagePlaceholderProvider = $imagePlaceholderProvider;
        $this->urlHelper = $urlHelper;
    }

    public function onCollectAutocompleteFields(CollectAutocompleteFieldsEvent $event): void
    {
        if (!$this->webpConfiguration->isEnabledIfSupported()) {
            return;
        }

        $event->addField('text.image_product_small_webp as imageWebp');
    }

    public function onProcessAutocompleteData(ProcessAutocompleteDataEvent $event): void
    {
        $defaultImage = $this->imagePlaceholderProvider->getPath('product_small', 'webp');
        $data = $event->getData();
        foreach ($data['products'] as $key => $productData) {
            if (isset($productData['imageWebp'])) {
                if ($productData['imageWebp']) {
                    $productData['imageWebp'] = $this->urlHelper->getAbsolutePath($productData['imageWebp']);
                } else {
                    $productData['imageWebp'] = $defaultImage;
                }

                $data['products'][$key] = $productData;
            }
        }

        $event->setData($data);
    }
}
