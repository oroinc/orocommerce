<?php

namespace Oro\Bundle\WebsiteBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ChangeLocalizationsRelation implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPreQuery(
            new CopyLocalizationReferencesToConfigQuery()
        );

        $queries->addQuery('DROP TABLE orob2b_websites_localizations');
    }
}
