<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_20;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityBundle\ORM\DatabasePlatformInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Migrate ID from integer to UUID to prevent reaching max integer value.
 */
class PriceListToProductIdToUUID implements Migration, ConnectionAwareInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    private Connection $connection;

    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_price_list_to_product');

        // Column type already changed
        if ($table->getColumn('id')->getType()->getName() === Types::GUID) {
            return;
        }

        if (DatabasePlatformInterface::DATABASE_POSTGRESQL === $this->connection->getDatabasePlatform()->getName()) {
            $queries->addQuery('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');
            $queries->addQuery('ALTER TABLE oro_price_list_to_product DROP COLUMN id;');
            $queries->addQuery('ALTER TABLE oro_price_list_to_product ADD COLUMN id UUID;');
            $queries->addQuery('UPDATE oro_price_list_to_product SET id=uuid_generate_v4();');
            $queries->addQuery('ALTER TABLE oro_price_list_to_product ADD PRIMARY KEY (id);');
        } else {
            $table->dropPrimaryKey();
            $table->changeColumn(
                'id',
                [
                    'type' => Type::getType(Types::GUID),
                    'notnull' => false,
                    'comment' => '(DC2Type:guid)'
                ]
            );
            $queries->addQuery('UPDATE oro_price_list_to_product SET id=uuid()');
            $queries->addQuery(
                "ALTER TABLE oro_price_list_to_product CHANGE id id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)'"
            );
            $table->setPrimaryKey(['id']);
        }
    }

    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }
}
