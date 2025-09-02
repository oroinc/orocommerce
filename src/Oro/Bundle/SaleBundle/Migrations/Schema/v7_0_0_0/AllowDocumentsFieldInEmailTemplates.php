<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v7_0_0_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\SaleBundle\Entity\Quote;

class AllowDocumentsFieldInEmailTemplates implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        if (!$schema->getTable('oro_sale_quote')->hasColumn('documents')) {
            return;
        }

        $queries->addQuery(new UpdateEntityConfigFieldValueQuery(
            entityName: Quote::class,
            fieldName: 'documents',
            scope: 'email',
            code: 'available_in_template',
            value: true
        ));
    }
}
