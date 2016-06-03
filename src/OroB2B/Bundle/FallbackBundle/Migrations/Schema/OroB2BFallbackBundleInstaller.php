<?php

namespace OroB2B\Bundle\FallbackBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BFallbackBundleInstaller implements Installation
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
//        // TODO: will be removed in scope https://magecore.atlassian.net/browse/BAP-10654
//        $this->addOroFallbackLocalizedValueForeignKeys($schema);
    }

//    /**
//     * Add oro_fallback_localization_val foreign keys.
//     *
//     * @param Schema $schema
//     */
//    protected function addOroFallbackLocalizedValueForeignKeys(Schema $schema)
//    {
//        $table = $schema->getTable('oro_fallback_localization_val');
//        $table->addForeignKeyConstraint(
//            $schema->getTable('orob2b_locale'),
//            ['locale_id'],
//            ['id'],
//            ['onUpdate' => null, 'onDelete' => 'CASCADE']
//        );
//    }
}
