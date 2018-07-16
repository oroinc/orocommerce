<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Add comment to json array field on Postgres and update json field database type for mysql 5.7
 */
class OroCheckoutBundle implements Migration
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
