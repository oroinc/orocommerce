<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MakeContentOptional implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->alterOroCmsPageTable($schema);
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function alterOroCmsPageTable(Schema $schema)
    {
        $table = $schema->getTable('oro_cms_page');
        $table->changeColumn('content', ['notnull' => false]);
    }
}
