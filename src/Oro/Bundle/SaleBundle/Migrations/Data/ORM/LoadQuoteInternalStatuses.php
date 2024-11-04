<?php

namespace Oro\Bundle\SaleBundle\Migrations\Data\ORM;

use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;
use Oro\Bundle\SaleBundle\Entity\Quote;

/**
 * Loads internal status enum options for Quote Entity
 */
class LoadQuoteInternalStatuses extends AbstractEnumFixture
{
    protected static array $data = [
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

    #[\Override]
    protected function getData(): array
    {
        return self::$data;
    }

    public static function getDataKeys(): array
    {
        return array_keys(self::$data);
    }

    #[\Override]
    protected function getEnumCode(): string
    {
        return Quote::INTERNAL_STATUS_CODE;
    }
}
