<?php

namespace Oro\Bundle\SaleBundle\Migrations\Data\ORM;

use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;
use Oro\Bundle\SaleBundle\Entity\Quote;

class LoadQuoteInternalStatuses extends AbstractEnumFixture
{
    /** @var array */
    protected static $data = [
        'draft' => 'Draft',
        'template' => 'Template',
        'open' => 'Open',
        'sent_to_customer' => 'Sent to Customer',
        'expired' => 'Expired',
        'accepted' => 'Accepted',
        'declined' => 'Declined',
        'deleted' => 'Deleted',
        'cancelled' => 'Cancelled',
        'submitted_for_review' => 'Submitted for Review',
        'under_review' => 'Under Review',
        'reviewed' => 'Reviewed',
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
        return Quote::INTERNAL_STATUS_CODE;
    }
}
