<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds "checksum" column for {@see RequestProductItem::$checksum} field.
 */
class AddRequestProductItemChecksumColumn implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_rfp_request_prod_item');
        if (!$table->hasColumn('checksum')) {
            $table->addColumn('checksum', 'string', ['length' => 40, 'notnull' => true, 'default' => '']);
        }
    }
}
