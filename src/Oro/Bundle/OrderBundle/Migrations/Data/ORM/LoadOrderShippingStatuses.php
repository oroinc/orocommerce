<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\ORM;

use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Loads supported order shipping statuses.
 */
class LoadOrderShippingStatuses extends AbstractEnumFixture
{
    #[\Override]
    protected function getData(): array
    {
        return [
            'not_shipped' => 'Not Shipped',
            'shipped' => 'Shipped',
            'partially_shipped' => 'Partially Shipped'
        ];
    }

    #[\Override]
    protected function getDefaultValue(): ?string
    {
        return 'not_shipped';
    }

    #[\Override]
    protected function getEnumCode(): string
    {
        return Order::SHIPPING_STATUS_CODE;
    }
}
