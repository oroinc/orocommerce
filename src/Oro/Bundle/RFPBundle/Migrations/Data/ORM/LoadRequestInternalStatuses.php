<?php

namespace Oro\Bundle\RFPBundle\Migrations\Data\ORM;

use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;
use Oro\Bundle\RFPBundle\Entity\Request;

/**
 * Loads RFQ internal status enum options.
 */
class LoadRequestInternalStatuses extends AbstractEnumFixture
{
    protected static array $data = [
        'open' => 'Open',
        'processed' => 'Processed',
        'more_info_requested' => 'More Info Requested',
        'declined' => 'Declined',
        'cancelled_by_customer' => 'Cancelled By Customer',
        'deleted' => 'Deleted'
    ];

    #[\Override]
    protected function getData(): array
    {
        return self::$data;
    }

    /**
     * Returns array of data keys.
     * @return array
     */
    public static function getDataKeys(): array
    {
        return array_keys(self::$data);
    }

    #[\Override]
    protected function getEnumCode(): string
    {
        return Request::INTERNAL_STATUS_CODE;
    }
}
