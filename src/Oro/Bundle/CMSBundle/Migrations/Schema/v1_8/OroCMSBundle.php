<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCMSBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_cms_content_widget');
        $table->addColumn('layout', 'string', ['length' => 255, 'notnull' => false]);
        $table->dropColumn('template');
    }
}
