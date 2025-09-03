<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v7_0_0_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\OrderBundle\Entity\Order;

class AllowDocumentsFieldInEmailTemplates implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        if (!$schema->getTable('oro_order')->hasColumn('documents')) {
            return;
        }

        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery(
                entityName: Order::class,
                fieldName: 'documents',
                scope: 'email',
                code: 'available_in_template',
                value: true
            )
        );
    }
}
