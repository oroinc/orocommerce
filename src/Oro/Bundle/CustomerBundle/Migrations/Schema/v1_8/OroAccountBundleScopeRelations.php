<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CustomerBundle\Migrations\Schema\OroCustomerBundleInstaller;
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
            'customer',
            'oro_account',
            'name'
        );

        $this->scopeExtension->addScopeAssociation(
            $schema,
            'customerGroup',
            'oro_account_group',
            'name'
        );
    }
}
