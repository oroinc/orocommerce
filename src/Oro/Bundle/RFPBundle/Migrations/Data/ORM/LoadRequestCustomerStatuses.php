<?php

namespace Oro\Bundle\RFPBundle\Migrations\Data\ORM;

use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;
use Oro\Bundle\RFPBundle\Entity\Request;

/**
 * Loads RFQ customer status enum options.
 */
class LoadRequestCustomerStatuses extends AbstractEnumFixture
{
    protected static array $data = [
        'submitted' => 'Submitted',
        'pending_approval' => 'Pending Approval',
        'requires_attention' => 'Requires Attention',
        'cancelled' => 'Cancelled'
    ];

    protected function getData(): array
    {
        return self::$data;
    }

    public static function getDataKeys(): array
    {
        return array_keys(self::$data);
    }

    protected function getEnumCode(): string
    {
        return Request::CUSTOMER_STATUS_CODE;
    }
}
