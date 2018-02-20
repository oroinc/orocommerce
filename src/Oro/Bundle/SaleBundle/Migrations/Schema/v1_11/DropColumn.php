<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v1_11;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveFieldQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class DropColumn implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_sale_quote');
        foreach ($table->getForeignKeys() as $fk) {
            if ($fk->getColumns() === ['payment_term_id']) {
                $table->removeForeignKey($fk->getName());
            }
        }
        $table->dropColumn('payment_term_id');

        $queries->addPostQuery(
            new RemoveFieldQuery('Oro\Bundle\SaleBundle\Entity\Quote', 'paymentTerm')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 20;
    }
}
