<?php

namespace Oro\Bundle\AccountBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\AccountBundle\Migrations\Schema\OroAccountBundleInstaller;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ScopeBundle\Migrations\Schema\OroScopeBundleInstaller;

class OroAccountBundleScopeRelations implements Migration, ExtendExtensionAwareInterface, OrderedMigrationInterface
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
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 2;
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
            [],
            RelationType::MANY_TO_ONE,
            ['onDelete' => 'CASCADE']
        );

        $this->extendExtension->addManyToOneRelation(
            $schema,
            OroScopeBundleInstaller::ORO_SCOPE,
            'accountGroup',
            OroAccountBundleInstaller::ORO_ACCOUNT_GROUP_TABLE_NAME,
            'id',
            [],
            RelationType::MANY_TO_ONE,
            ['onDelete' => 'CASCADE']
        );
    }
}
