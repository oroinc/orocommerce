<?php

namespace Oro\Bundle\TaxBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroTaxBundle implements Migration
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
