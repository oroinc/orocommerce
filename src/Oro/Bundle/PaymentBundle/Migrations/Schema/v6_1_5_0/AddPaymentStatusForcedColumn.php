<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Migrations\Schema\v6_1_5_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

final class AddPaymentStatusForcedColumn implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_payment_status');
        if (!$table->hasColumn('forced')) {
            $table->addColumn('forced', 'boolean', ['default' => false]);
        }
    }
}
