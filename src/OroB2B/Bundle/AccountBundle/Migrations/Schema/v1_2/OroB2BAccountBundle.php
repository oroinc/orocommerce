<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use OroB2B\Bundle\AccountBundle\Migrations\Schema\OroB2BAccountBundleInstaller;

class OroB2BAccountBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable(OroB2BAccountBundleInstaller::ORO_B2B_ACCOUNT_USER_ROLE_TABLE_NAME);
        $table->getColumn('role')->setType(Type::getType(Type::STRING))->setOptions(['length' => 255]);
        $table->getColumn('label')->setType(Type::getType(Type::STRING))->setOptions(['length' => 255]);
    }
}
