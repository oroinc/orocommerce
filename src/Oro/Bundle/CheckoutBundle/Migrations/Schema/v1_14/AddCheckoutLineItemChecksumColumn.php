<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds "checksum" column for {@see CheckoutLineItem::$checksum} field.
 */
class AddCheckoutLineItemChecksumColumn implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_checkout_line_item');
        if (!$table->hasColumn('checksum')) {
            $table->addColumn('checksum', 'string', ['length' => 40, 'notnull' => true, 'default' => '']);
        }
    }
}
