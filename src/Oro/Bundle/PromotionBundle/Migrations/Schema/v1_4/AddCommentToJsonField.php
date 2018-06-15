<?php

namespace Oro\Bundle\PromotionBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds comment to config_options and promotion_data fields of the oro_promotion_applied table
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
        $table = $schema->getTable('oro_promotion_applied');

        $table->getColumn('config_options')
            ->setComment('(DC2Type:json_array)');
        $table->getColumn('promotion_data')
            ->setComment('(DC2Type:json_array)');
    }
}
