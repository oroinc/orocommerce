<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds comment to completed_data field of the oro_checkout table
 */
class AddCommentToJsonField implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addCommentsToJsonArrayFields($schema);
    }

    /**
     * @param Schema $schema
     */
    private function addCommentsToJsonArrayFields(Schema $schema)
    {
        $table = $schema->getTable('oro_checkout');
        $table->getColumn('completed_data')
            ->setComment('(DC2Type:json_array)');
    }
}
