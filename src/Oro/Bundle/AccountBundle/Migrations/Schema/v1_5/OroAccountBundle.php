<?php

namespace Oro\Bundle\AccountBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroAccountBundle implements Migration
{
    const ORO_B2B_ACCOUNT_ADDRESS_TABLE_NAME = 'orob2b_account_address';
    const ORO_B2B_ACCOUNT_USER_ADDRESS_TABLE_NAME = 'orob2b_account_user_address';

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->alterAccountAddressTable($schema);
        $this->alterAccountUserAddressTable($schema);
    }

    /**
    * @param Schema $schema
    *
    * @throws \Doctrine\DBAL\Schema\SchemaException
    */
    protected function alterAccountAddressTable(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_ADDRESS_TABLE_NAME);
        $table->addColumn('phone', 'string', ['notnull' => false, 'length' => 255]);
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function alterAccountUserAddressTable(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_USER_ADDRESS_TABLE_NAME);
        $table->addColumn('phone', 'string', ['notnull' => false, 'length' => 255]);
    }
}
