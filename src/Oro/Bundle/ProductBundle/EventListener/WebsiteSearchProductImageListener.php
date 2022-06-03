<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;

/**
 * Add image data to search index
 */
class WebsiteSearchProductImageListener implements WebsiteSearchProductIndexerListenerInterface
{
    use ContextTrait;

    /** @var WebsiteContextManager */
    private $websiteContextManager;

    /** @var ManagerRegistry */
    private $registry;

    /** @var AttachmentManager */
    private $attachmentManager;

    public function __construct(
        WebsiteContextManager $websiteContextManager,
        ManagerRegistry $registry,
        AttachmentManager $attachmentManager
    ) {
        $this->websiteContextManager = $websiteContextManager;
        $this->registry = $registry;
        $this->attachmentManager = $attachmentManager;
    }

    public function onWebsiteSearchIndex(IndexEntityEvent $event)
    {
        if (!$this->hasContextFieldGroup($event->getContext(), 'image')) {
            return;
        }

        $websiteId = $this->websiteContextManager->getWebsiteId($event->getContext());
        if (!$websiteId) {
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

    private function processImages(IndexEntityEvent $event, array $productImages, int $productId): void
    {
        if (isset($productImages[$productId])) {
            /** @var File $entity */
            $entity = $productImages[$productId];
            foreach (['product_large', 'product_medium', 'product_small'] as $filterName) {
                $event->addField(
                    $productId,
                    'image_' . $filterName,
                    $this->attachmentManager->getFilteredImageUrl($entity, $filterName)
                );
            }
        }
    }

    private function getProductRepository(): ProductRepository
    {
        return $this->registry->getRepository(Product::class);
    }
}
