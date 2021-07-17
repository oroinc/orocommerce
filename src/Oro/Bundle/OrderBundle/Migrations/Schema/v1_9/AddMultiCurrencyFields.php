<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

class AddMultiCurrencyFields implements
    Migration,
    RenameExtensionAwareInterface
{
    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queryBag)
    {
        self::addColumnsForMultiCurrency($schema, $queryBag, $this->renameExtension);
    }

    public static function addColumnsForMultiCurrency(
        Schema $schema,
        QueryBag $queryBag,
        RenameExtension $renameExtension
    ) {
        $table = $schema->getTable('oro_order');

        //Rename columns for new type
        self::renameOrderFields($schema, $queryBag, $renameExtension);

        //Add columns for new type
        $table->addColumn(
            'subtotal_currency',
            'currency',
            ['length' => 3, 'notnull' => false, 'comment' => '(DC2Type:currency)']
        );
        $table->addColumn(
            'total_currency',
            'currency',
            ['length' => 3, 'notnull' => false, 'comment' => '(DC2Type:currency)']
        );

        $table->addColumn(
            'base_subtotal_value',
            'money',
            ['notnull' => false, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn(
            'base_total_value',
            'money',
            ['notnull' => false, 'comment' => '(DC2Type:money)']
        );

        self::fillCurrencyFieldsWithDefaultValue($queryBag);
    }

    public static function renameOrderFields(
        Schema $schema,
        QueryBag $queries,
        RenameExtension $renameExtension
    ) {
        $table = $schema->getTable('oro_order');

        $type = Type::getType('money_value');
        $table->changeColumn(
            'subtotal',
            ['type' => $type, 'notnull' => false, 'comment' => '(DC2Type:money_value)']
        );

        $table->changeColumn(
            'total',
            ['type' => $type, 'notnull' => false, 'comment' => '(DC2Type:money_value)']
        );

        $renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            'subtotal',
            'subtotal_value'
        );

        $renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            'total',
            'total_value'
        );
    }

    public static function fillCurrencyFieldsWithDefaultValue(QueryBag $queries)
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
