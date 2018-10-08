<?php

namespace Oro\Bundle\PromotionBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Add comment to json array field on Postgres and update json field database type for mysql 5.7
 */
class OroPromotionBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(new UpdateJsonArrayQuery());
        $queries->addPostQuery(new SetCommentOnJsonArrayQuery());
    }
}
