<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Schema\v1_6;

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
        $table = $schema->getTable('orob2b_account_address');
        if ($table->hasColumn('serialized_data') &&
            !class_exists('Oro\Bundle\EntitySerializedFieldsBundle\OroEntitySerializedFieldsBundle')
        ) {
            $table->dropColumn('serialized_data');
        }
    }
}
