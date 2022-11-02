<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;

/**
 * Adds webp images urls for products.
 */
class WebpAwareWebsiteSearchProductIndexerListener implements WebsiteSearchProductIndexerListenerInterface
{
    use ContextTrait;

    private ManagerRegistry $managerRegistry;

    private AttachmentManager $attachmentManager;

    private WebsiteContextManager $websiteContextManager;

    public function __construct(
        ManagerRegistry $managerRegistry,
        AttachmentManager $attachmentManager,
        WebsiteContextManager $websiteContextManager
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->attachmentManager = $attachmentManager;
        $this->websiteContextManager = $websiteContextManager;
    }

    public function onWebsiteSearchIndex(IndexEntityEvent $event): void
    {
        if (!$this->hasContextFieldGroup($event->getContext(), 'image')) {
            return;
        }

        if (!$this->attachmentManager->isWebpEnabledIfSupported()) {
            return;
        }

        $website = $this->getWebsite($event);
        if (!$website) {
            $event->stopPropagation();

            return;
        }

        /** @var Product[] $products */
        $products = $event->getEntities();

        $productIds = array_map(
            static function (Product $product) {
                return $product->getId();
            },
            $products
        );

        $productImages = $this->getProductRepository()->getListingImagesFilesByProductIds($productIds);
        foreach ($products as $product) {
            $this->processImages($event, $productImages, $product->getId());
        }
    }

    private function getWebsite(IndexEntityEvent $event): ?Website
    {
        $websiteId = $this->websiteContextManager->getWebsiteId($event->getContext());
        if ($websiteId) {
            return $this->managerRegistry->getManagerForClass(Website::class)->find(Website::class, $websiteId);
        }

        return null;
    }

    private function processImages(IndexEntityEvent $event, array $productImages, int $productId): void
    {
        if (isset($productImages[$productId])) {
            /** @var File $entity */
            $entity = $productImages[$productId];
            foreach (['product_large', 'product_medium', 'product_small'] as $filterName) {
                $event->addField(
                    $productId,
                    'image_' . $filterName . '_webp',
                    $this->attachmentManager->getFilteredImageUrl($entity, $filterName, 'webp')
                );
            }
        }
    }

    private function getProductRepository(): ProductRepository
    {
        return $this->managerRegistry->getRepository(Product::class);
    }
}
