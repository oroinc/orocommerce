<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Search\Reindex\ProductReindexManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * The base class for product visibility cache builders.
 */
abstract class AbstractProductResolvedCacheBuilder extends AbstractResolvedCacheBuilder
{
    private ProductReindexManager $productReindexManager;

    public function __construct(
        ManagerRegistry $doctrine,
        ProductReindexManager $productReindexManager
    ) {
        parent::__construct($doctrine);
        $this->productReindexManager = $productReindexManager;
    }

    protected function resolveStaticValues(
        string $selectedVisibility,
        VisibilityInterface $productVisibility = null
    ): array {
        $updateData = [
            'sourceProductVisibility' => $productVisibility,
            'source' => BaseProductVisibilityResolved::SOURCE_STATIC,
            'category' => null,
        ];

        if ($selectedVisibility === VisibilityInterface::VISIBLE) {
            $updateData['visibility'] = BaseVisibilityResolved::VISIBILITY_VISIBLE;
        } elseif ($selectedVisibility === VisibilityInterface::HIDDEN) {
            $updateData['visibility'] = BaseVisibilityResolved::VISIBILITY_HIDDEN;
        }

        return $updateData;
    }

    protected function triggerProductReindexation(Product $product, ?Website $website, bool $schedule): void
    {
        $this->productReindexManager->reindexProduct(
            $product,
            $website ? $website->getId() : null,
            $schedule,
            ['visibility']
        );
    }
}
