<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_20;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroProductBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery(
                'Oro\Bundle\ProductBundle\Entity\Brand',
                'descriptions',
                'importexport',
                'fallback_field',
                'wysiwyg'
            )
        );

        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery(
                'Oro\Bundle\ProductBundle\Entity\Product',
                'descriptions',
                'importexport',
                'fallback_field',
                'wysiwyg'
            )
        );
    }
}
