<?php

namespace Oro\Bundle\WebsiteBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ScopeBundle\Migration\Extension\ScopeExtensionAwareInterface;
use Oro\Bundle\ScopeBundle\Migration\Extension\ScopeExtensionAwareTrait;
use Oro\Bundle\WebsiteBundle\Migrations\Schema\OroWebsiteBundleInstaller;

class OroWebsiteBundle implements Migration, ScopeExtensionAwareInterface
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
            'website',
            OroWebsiteBundleInstaller::WEBSITE_TABLE_NAME,
            'name'
        );
    }
}
