<?php

namespace Oro\Bundle\RFPBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Add RFP request as email owner.
 */
class AddOwnerToOroEmailAddress implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addOwnerToOroEmailAddress($schema);
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function addOwnerToOroEmailAddress(Schema $schema)
    {
        $table = $schema->getTable('oro_email_address');

        if ($table->hasColumn('owner_request_id')) {
            return;
        }

        $table->addColumn('owner_request_id', 'integer', ['notnull' => false]);
        $table->addIndex(['owner_request_id']);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_rfp_request'),
            ['owner_request_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }
}
