<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Operation;

use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

/**
 * Creates an Order draft from the given RFQ Request.
 */
class CreateOrderDraftFromRfq
{
    public function __construct(
        private readonly OrderDraftManager $orderDraftManager,
    ) {
    }

    public function createOrderDraftFromRfq(Request $request): Order
    {
        $draftSessionUuid = UUIDGenerator::v4();

        $order = $this->orderDraftManager->saveToEntityDraft($request, $draftSessionUuid);
        assert($order instanceof Order);

        return $order;
    }
}
