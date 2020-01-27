<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCatalogBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery(
                'Oro\Bundle\CatalogBundle\Entity\Category',
                'longDescriptions',
                'importexport',
                'fallback_field',
                'wysiwyg'
            )
        );
    }
}
