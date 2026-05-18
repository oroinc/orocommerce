<?php

declare(strict_types=1);

namespace Oro\Bundle\PromotionBundle\Migrations\Schema\v7_0_2_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds draft_session_uuid field to AppliedCoupon, AppliedDiscount, and AppliedPromotion entities.
 */
class AddDraftSessionFieldsToRelatedEntities implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->addDraftSessionUuidToAppliedCoupon($schema);
        $this->addDraftSessionUuidToAppliedDiscount($schema);
        $this->addDraftSessionUuidToAppliedPromotion($schema);
    }

    private function addDraftSessionUuidToAppliedCoupon(Schema $schema): void
    {
        $table = $schema->getTable('oro_promotion_applied_coupon');

        if (!$table->hasColumn('draft_session_uuid')) {
            $table->addColumn('draft_session_uuid', 'guid', ['notnull' => false]);
        }
    }

    private function addDraftSessionUuidToAppliedDiscount(Schema $schema): void
    {
        $table = $schema->getTable('oro_promotion_applied_discount');

        if (!$table->hasColumn('draft_session_uuid')) {
            $table->addColumn('draft_session_uuid', 'guid', ['notnull' => false]);
        }
    }

    private function addDraftSessionUuidToAppliedPromotion(Schema $schema): void
    {
        $table = $schema->getTable('oro_promotion_applied');

        if (!$table->hasColumn('draft_session_uuid')) {
            $table->addColumn('draft_session_uuid', 'guid', ['notnull' => false]);
        }
    }
}
