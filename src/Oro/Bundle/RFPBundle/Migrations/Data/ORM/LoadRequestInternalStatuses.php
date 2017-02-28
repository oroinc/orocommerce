<?php

namespace Oro\Bundle\RFPBundle\Migrations\Data\ORM;

use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;
use Oro\Bundle\RFPBundle\Entity\Request;

class LoadRequestInternalStatuses extends AbstractEnumFixture
{
    /** @var array */
    protected static $data = [
        'open' => 'Open',
        'processed' => 'Processed',
        'more_info_requested' => 'More Info Requested',
        'declined' => 'Declined',
        'cancelled_by_customer' => 'Cancelled By Customer',
        'deleted' => 'Deleted'
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
        return Request::INTERNAL_STATUS_CODE;
    }
}
