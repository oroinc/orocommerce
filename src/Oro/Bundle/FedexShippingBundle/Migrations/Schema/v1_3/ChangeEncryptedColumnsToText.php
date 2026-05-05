<?php

namespace Oro\Bundle\FedexShippingBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Converts FedEx encrypted credential columns from VARCHAR(255) to TEXT to fit AES-encrypted values.
 */
class ChangeEncryptedColumnsToText implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_integration_transport');

        $this->changeColumnToText($table, 'fedex_client_secret');
        $this->changeColumnToText($table, 'fedex_password');
    }

    private function changeColumnToText(Table $table, string $columnName): void
    {
        if ($table->getColumn($columnName)->getType()->getName() === Types::TEXT) {
            return;
        }

        $table->modifyColumn($columnName, ['type' => Type::getType(Types::TEXT), 'length' => null]);
    }
}
