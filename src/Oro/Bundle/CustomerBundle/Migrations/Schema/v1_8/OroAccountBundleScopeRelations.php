<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CustomerBundle\Migrations\Schema\OroAccountBundleInstaller;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ScopeBundle\Migration\Extension\ScopeExtensionAwareInterface;
use Oro\Bundle\ScopeBundle\Migration\Extension\ScopeExtensionAwareTrait;

class OroAccountBundleScopeRelations implements Migration, ScopeExtensionAwareInterface
{
    use ScopeExtensionAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addRelationsToScope($schema);
    }

    /**
     * @param Schema $schema
     */
    private function addRelationsToScope(Schema $schema)
    {
        $this->scopeExtension->addScopeAssociation(
            $schema,
            'account',
            OroAccountBundleInstaller::ORO_ACCOUNT_TABLE_NAME,
            'name'
        );

        $this->scopeExtension->addScopeAssociation(
            $schema,
            'accountGroup',
            OroAccountBundleInstaller::ORO_ACCOUNT_GROUP_TABLE_NAME,
            'name'
        );
    }
}
