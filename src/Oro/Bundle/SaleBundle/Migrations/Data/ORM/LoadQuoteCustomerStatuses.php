<?php

namespace Oro\Bundle\SaleBundle\Migrations\Data\ORM;

use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;
use Oro\Bundle\SaleBundle\Entity\Quote;

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

    /**
     * {@inheritdoc}
     */
    protected function getData()
    {
        return self::$data;
    }

    /**
     * Returns array of data keys.
     * @return array
     */
    public static function getDataKeys()
    {
        return array_keys(self::$data);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEnumCode()
    {
        return Quote::CUSTOMER_STATUS_CODE;
    }
}
