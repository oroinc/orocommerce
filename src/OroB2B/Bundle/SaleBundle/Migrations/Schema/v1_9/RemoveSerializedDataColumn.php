<?php

namespace OroB2B\Bundle\SaleBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveSerializedDataColumn implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_quote_address');
        if ($table->hasColumn('serialized_data') &&
            !class_exists('Oro\Bundle\EntitySerializedFieldsBundle\OroEntitySerializedFieldsBundle')
        ) {
            $table->dropColumn('serialized_data');
        }
    }
}
