<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

class AddMultiCurrencyFields implements Migration, RenameExtensionAwareInterface
{
    use RenameExtensionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_order');

        $this->renameOrderFields($schema, $queries);

        $table->addColumn('subtotal_currency', 'currency', [
            'length' => 3,
            'notnull' => false,
            'comment' => '(DC2Type:currency)'
        ]);
        $table->addColumn('total_currency', 'currency', [
            'length' => 3,
            'notnull' => false,
            'comment' => '(DC2Type:currency)'
        ]);
        $table->addColumn('base_subtotal_value', 'money', ['notnull' => false, 'comment' => '(DC2Type:money)']);
        $table->addColumn('base_total_value', 'money', ['notnull' => false, 'comment' => '(DC2Type:money)']);

        $this->fillCurrencyFieldsWithDefaultValue($queries);
    }

    private function renameOrderFields(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_order');
        $type = Type::getType('money_value');
        $table->modifyColumn('subtotal', ['type' => $type, 'notnull' => false, 'comment' => '(DC2Type:money_value)']);
        $table->modifyColumn('total', ['type' => $type, 'notnull' => false, 'comment' => '(DC2Type:money_value)']);

        $this->renameExtension->renameColumn($schema, $queries, $table, 'subtotal', 'subtotal_value');
        $this->renameExtension->renameColumn($schema, $queries, $table, 'total', 'total_value');
    }

    private function fillCurrencyFieldsWithDefaultValue(QueryBag $queries): void
    {
        $queries->addPostQuery(
            new SqlMigrationQuery(
                'UPDATE oro_order SET subtotal_currency=currency, total_currency=currency'
            )
        );
        $queries->addPostQuery(
            new SqlMigrationQuery(
                'UPDATE oro_order SET base_subtotal_value=subtotal_value, base_total_value=total_value'
            )
        );
    }
}
