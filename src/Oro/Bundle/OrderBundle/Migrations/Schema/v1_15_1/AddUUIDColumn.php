<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_15_1;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds a unique identifier used as a reference for the order.
 */
class AddUUIDColumn implements Migration, ConnectionAwareInterface
{
    protected ?Connection $connection = null;

    public function setConnection(Connection $connection): void
    {
        $this->connection = $connection;
    }

    public function up(Schema $schema, QueryBag $queries): void
    {
        $dbDriver = $this->connection->getDriver()->getName();

        $table = $schema->getTable('oro_order');
        if (!$table->hasColumn('uuid')) {
            if ($dbDriver === DatabaseDriverInterface::DRIVER_POSTGRESQL) {
                $this->alterPsqlColumn($queries);
            } else {
                $this->alterMySqlColumn($queries);
            }

            $queries->addPostQuery('CREATE INDEX oro_order_uuid ON oro_order (uuid)');
            $queries->addPostQuery('CREATE UNIQUE INDEX UNIQ_388B2E9DD17F50A6 ON oro_order (uuid)');
        }
    }

    private function alterMySqlColumn(QueryBag $queries): void
    {
        $queries->addQuery('ALTER TABLE oro_order ADD uuid CHAR(36) COMMENT \'(DC2Type:guid)\'');
        $queries->addPostQuery('UPDATE oro_order SET uuid = UUID()');
        $queries->addPostQuery('ALTER TABLE oro_order MODIFY uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
    }

    private function alterPsqlColumn(QueryBag $queries): void
    {
        $queries->addQuery('ALTER TABLE oro_order ADD COLUMN uuid UUID');
        $queries->addPostQuery('UPDATE oro_order SET uuid = uuid_generate_v4()');
        $queries->addPostQuery('ALTER TABLE oro_order ALTER COLUMN uuid SET NOT NULL');
    }
}
