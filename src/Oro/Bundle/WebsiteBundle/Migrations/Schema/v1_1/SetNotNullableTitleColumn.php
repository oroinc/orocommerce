<?php

namespace Oro\Bundle\WebsiteBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class SetNotNullableTitleColumn implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 30;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_locale');
        $table
            ->getColumn('title')
            ->setType(Type::getType(Type::STRING))
            ->setOptions(['length' => 255, 'notnull' => true]);

        $table->addUniqueIndex(['title']);
    }
}
