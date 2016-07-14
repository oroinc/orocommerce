<?php

namespace OroB2B\Bundle\WebsiteBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ChangeLocalizationsRelation implements Migration
{
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPreQuery(
            new CopyLocalizationReferencesToConfigQuery()
        );

        $schema->dropTable('orob2b_websites_localizations');
    }
}
