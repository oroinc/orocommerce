<?php

namespace Oro\Bundle\WebsiteSearchBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ChangeWebsiteSearchIntegerType implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_website_search_integer');
        if (!$table->hasColumn('value')) {
            return;
        }

        $table->getColumn('value')->setType(Type::getType(Types::BIGINT));
    }
}
