<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Migrations\Schema\v7_0_0_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

final class AddWebhookRequestLogsColumn implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_payment_transaction');
        if (!$table->hasColumn('webhook_request_logs')) {
            $table->addColumn('webhook_request_logs', 'secure_array', [
                'notnull' => false,
                'comment' => '(DC2Type:secure_array)',
            ]);
        }
    }
}
