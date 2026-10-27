<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;

/**
 * Marks the line items of product variants that are not linked to a parent product as not visible
 * when the view of product variants is restricted, so that no link to the restricted product view page is rendered.
 */
class DatagridLineItemsDataRestrictVariantViewListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    private const IS_VISIBLE = 'isVisible';

    public function __construct(
        private readonly ManagerRegistry $doctrine
    ) {
    }

    public function onLineItemData(DatagridLineItemsDataEvent $event): void
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $productIds = [];
        foreach ($event->getLineItems() as $lineItemId => $lineItem) {
            $product = $lineItem->getProduct();
            if (null === $product || !$product->isSimple() || null !== $lineItem->getParentProduct()) {
                continue;
            }

            $productIds[$lineItemId] = $product->getId();
        }

        if (!$productIds) {
            return;
        }

        $variantIds = $this->getVariantIds(array_values(array_unique($productIds)));
        foreach ($productIds as $lineItemId => $productId) {
            if (isset($variantIds[$productId])) {
                $event->addDataForLineItem($lineItemId, [self::IS_VISIBLE => false]);
            }
        }
    }

    /**
     * @param int[] $productIds
     *
     * @return array<int, bool> [product id => true, ...]
     */
    private function getVariantIds(array $productIds): array
    {
        $rows = $this->doctrine->getRepository(ProductVariantLink::class)
            ->createQueryBuilder('pvl')
            ->select('DISTINCT IDENTITY(pvl.product) AS productId')
            ->where('pvl.product IN (:productIds)')
            ->setParameter('productIds', $productIds, ArrayParameterType::INTEGER)
            ->getQuery()
            ->getArrayResult();

        return array_fill_keys(array_map('intval', array_column($rows, 'productId')), true);
    }
}
