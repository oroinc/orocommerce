<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v7_0_2_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds draft_session_uuid field to OrderAddress, OrderDiscount, and OrderProductKitItemLineItem entities.
 */
class AddDraftSessionFieldsToRelatedEntities implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->addDraftSessionUuidToOrderAddress($schema);
        $this->addDraftSessionUuidToOrderDiscount($schema);
        $this->addDraftSessionUuidToOrderProductKitItemLineItem($schema);
    }

    private function addDraftSessionUuidToOrderAddress(Schema $schema): void
    {
        $table = $schema->getTable('oro_order_address');

        if (!$table->hasColumn('draft_session_uuid')) {
            $table->addColumn('draft_session_uuid', 'guid', ['notnull' => false]);
        }
    }

    private function addDraftSessionUuidToOrderDiscount(Schema $schema): void
    {
        $table = $schema->getTable('oro_order_discount');

        if (!$table->hasColumn('draft_session_uuid')) {
            $table->addColumn('draft_session_uuid', 'guid', ['notnull' => false]);
        }
    }

    private function addDraftSessionUuidToOrderProductKitItemLineItem(Schema $schema): void
    {
        $table = $schema->getTable('oro_order_product_kit_item_line_item');

        if (!$table->hasColumn('draft_session_uuid')) {
            $table->addColumn('draft_session_uuid', 'guid', ['notnull' => false]);
        }
    }
}
