<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Migrations\Schema\v6_1_5_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

final class AddPaymentStatusUpdatedAtColumn implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_payment_status');
        if (!$table->hasColumn('updated_at')) {
            // Adds the `updated_at` nullable column to the `oro_payment_status` table.
            $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)', 'notnull' => false]);

            // Updates existing records with the current date and time for the `updated_at` column.
            $queries->addPostQuery('UPDATE oro_payment_status SET updated_at = NOW() WHERE updated_at IS NULL;');

            // Alters the `updated_at` column to be NOT NULL.
            $queries->addPostQuery('ALTER TABLE oro_payment_status ALTER COLUMN updated_at SET NOT NULL;');
        }
    }
}
