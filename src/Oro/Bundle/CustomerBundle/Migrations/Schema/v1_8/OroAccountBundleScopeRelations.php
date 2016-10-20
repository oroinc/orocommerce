<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CustomerBundle\Migrations\Schema\OroAccountBundleInstaller;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ScopeBundle\Migrations\Schema\OroScopeBundleInstaller;

class OroAccountBundleScopeRelations implements Migration, ExtendExtensionAwareInterface
{
    /**
     * @var ExtendExtension
     */
    private $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addRelationsToScope($schema);

        $this->dropVisibilityTables($schema);
    }

    /**
     * @param Schema $schema
     */
    private function addRelationsToScope(Schema $schema)
    {
        $this->extendExtension->addManyToOneRelation(
            $schema,
            OroScopeBundleInstaller::ORO_SCOPE,
            'account',
            OroAccountBundleInstaller::ORO_ACCOUNT_TABLE_NAME,
            'id',
            [
                'extend' => [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'cascade' => ['all'],
                    'on_delete' => 'CASCADE',
                    'nullable' => true
                ]
            ],
            RelationType::MANY_TO_ONE
        );

        $this->extendExtension->addManyToOneRelation(
            $schema,
            OroScopeBundleInstaller::ORO_SCOPE,
            'accountGroup',
            OroAccountBundleInstaller::ORO_ACCOUNT_GROUP_TABLE_NAME,
            'id',
            [
                'extend' => [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'cascade' => ['all'],
                    'on_delete' => 'CASCADE',
                    'nullable' => true
                ]
            ],
            RelationType::MANY_TO_ONE
        );
    }

    /**
     * @param Schema $schema
     */
    private function dropVisibilityTables(Schema $schema)
    {
        $schema->dropTable('orob2b_category_visibility');
        $schema->dropTable('orob2b_acc_category_visibility');
        $schema->dropTable('orob2b_acc_grp_ctgr_visibility');
        $schema->dropTable('orob2b_product_visibility');
        $schema->dropTable('orob2b_acc_product_visibility');
        $schema->dropTable('orob2b_acc_grp_prod_visibility');
    }
}
