<?php

namespace Oro\Bundle\TaxBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\FrontendBundle\Migration\UpdateClassNamesQuery;
use Oro\Bundle\FrontendBundle\Migration\UpdateSerializedClassNames;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RenameTables implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // fix class names stored in DB
        $queries->addQuery(new UpdateClassNamesQuery('orob2b_tax_value', 'entity_class'));
        $queries->addQuery(new UpdateSerializedClassNames('orob2b_tax_value', 'result'));
    }
}
