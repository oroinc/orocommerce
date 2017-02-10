<?php

namespace Oro\Bundle\RFPBundle\Migrations\Data\ORM;

use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;
use Oro\Bundle\RFPBundle\Entity\Request;

class LoadRequestCustomerStatuses extends AbstractEnumFixture
{
    /** @var array */
    protected static $data = [
        'submitted' => 'Submitted',
        'pending_approval' => 'Pending Approval',
        'requires_attention' => 'Requires Attention',
        'cancelled' => 'Cancelled'
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
        return Request::CUSTOMER_STATUS_CODE;
    }
}
