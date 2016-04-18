<?php

namespace OroB2B\Bundle\AlternativeCheckoutBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BAlternativeCheckoutBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_1';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addAlternativeCheckoutColumns($schema);
    }

    /**
     * Add checkout type column
     *
     * @param Schema $schema
     */
    protected function addAlternativeCheckoutColumns(Schema $schema)
    {
        $table = $schema->getTable('orob2b_checkout');
        $table->addColumn('allowed', 'boolean', ['notnull' => false,]);
        $table->addColumn('allow_request_date', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('request_approval_notes', 'text', ['notnull' => false]);
        $table->addColumn('requested_for_approve', 'boolean', []);
    }
}
