<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Datagrid\DraftSession;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQueryListenerInterface;
use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;
use Oro\Component\DraftSession\Manager\DraftSessionOrmFilterManager;

/**
 * Preloads the order line items, the unit precisions of their products and the product kit relations
 * before the order line items are validated by {@see OrderLineItemDraftValidationDatagridListener}
 * to avoid a separate query per order line item.
 */
final class PreloadOrderLineItemsDatagridListener implements OrmResultBeforeQueryListenerInterface
{
    private array $preloadingConfig = [
        'lineItems' => [
            // Count constraint for Oro\Bundle\OrderBundle\Entity\OrderLineItem::$orders.
            'orders' => [],
            'product' => [
                // ProductKitLineItemContainsRequiredKitItems constraint.
                'kitItems' => [],
                // QuantityUnitPrecision constraint, Product::getUnitPrecision() compares the unit codes.
                'unitPrecisions' => [
                    'unit' => [],
                ],
            ],
            'productUnit' => [],
            'kitItemLineItems' => [
                'kitItem' => [
                    // ProductKitItemLineItemProductUnitAvailable constraint.
                    'productUnit' => [],
                    // ProductKitItemLineItemProductAvailable constraint,
                    // ProductKitItem::getKitItemProduct() iterates over the kit item products.
                    'kitItemProducts' => [
                        'product' => [],
                    ],
                ],
                'product' => [
                    // ProductKitItemLineItemQuantityUnitPrecision constraint.
                    'unitPrecisions' => [
                        'unit' => [],
                    ],
                ],
                'productUnit' => [],
            ],
        ],
    ];

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly PreloadingManager $preloadingManager,
        private readonly DraftSessionOrmFilterManager $draftSessionOrmFilterManager
    ) {
    }

    public function setPreloadingConfig(array $preloadingConfig): void
    {
        $this->preloadingConfig = $preloadingConfig;
    }

    #[\Override]
    public function onResultBeforeQuery(OrmResultBeforeQuery $event): void
    {
        $datagridParameters = $event->getDatagrid()->getParameters();
        $draftSessionUuid = (string)$datagridParameters->get('draft_session_uuid');
        $orderId = (int)$datagridParameters->get('order_id');

        if (!$draftSessionUuid || !$orderId) {
            return;
        }

        // The order drafts must be visible, exactly as when the order is loaded for validation.
        $isOrmFilterEnabled = $this->draftSessionOrmFilterManager->isEnabled();
        $this->draftSessionOrmFilterManager->disable();

        try {
            /** @var OrderRepository $orderRepository */
            $orderRepository = $this->doctrine->getRepository(Order::class);
            // The order is loaded the same way as in OrderLineItemDraftValidationDatagridListener, so the very same
            // managed order instance with the preloaded relations is validated there.
            $order = $orderRepository->getOrderWithRelations($orderId);
            if (!$order) {
                // Order does not exist anymore.
                return;
            }

            $this->preloadingManager->preloadInEntities([$order], $this->preloadingConfig);
        } finally {
            if ($isOrmFilterEnabled) {
                $this->draftSessionOrmFilterManager->enable();
            }
        }
    }
}
