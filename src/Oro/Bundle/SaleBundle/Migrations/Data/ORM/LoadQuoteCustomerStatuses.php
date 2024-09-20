<?php

namespace Oro\Bundle\SaleBundle\Migrations\Data\ORM;

use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;
use Oro\Bundle\SaleBundle\Entity\Quote;

/**
 * Loads customer status enum options for Quote Entity
 */
class LoadQuoteCustomerStatuses extends AbstractEnumFixture
{
    /** @var array */
    protected static $data = [
        'open' => 'Open',
        'expired' => 'Expired',
        'accepted' => 'Accepted',
        'declined' => 'Declined',
        'pending_approval' => 'Pending Approval',
        'approved' => 'Approved',
        'not_approved' => 'Not Approved'
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
        return Quote::CUSTOMER_STATUS_CODE;
    }
}
