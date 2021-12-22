<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Oro\Bundle\ProductBundle\Event\CollectAutocompleteFieldsEvent;
use Oro\Bundle\ProductBundle\Event\ProcessAutocompleteDataEvent;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Adds small product image in webp format to the search autocomplete data.
 */
class WebpAwareProductAutocompleteListener
{
    private RequestStack $requestStack;

    private WebpConfiguration $webpConfiguration;

    private ImagePlaceholderProviderInterface $imagePlaceholderProvider;

    public function __construct(
        RequestStack $requestStack,
        WebpConfiguration $webpConfiguration,
        ImagePlaceholderProviderInterface $imagePlaceholderProvider
    ) {
        $this->requestStack = $requestStack;
        $this->webpConfiguration = $webpConfiguration;
        $this->imagePlaceholderProvider = $imagePlaceholderProvider;
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
        $request = $this->requestStack->getCurrentRequest();
        foreach ($data as $sku => $productData) {
            if (isset($productData['imageWebp'])) {
                if ($productData['imageWebp']) {
                    $productData['imageWebp'] = $request
                        ? $request->getUriForPath($productData['imageWebp'])
                        : $productData['imageWebp'];
                } else {
                    $productData['imageWebp'] = $defaultImage;
                }

                $data[$sku] = $productData;
            }
        }

        $event->setData($data);
    }
}
