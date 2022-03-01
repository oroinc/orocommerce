<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_24;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\ProductDescription;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductShortDescription;

class UpdateEntitiesAuditable implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(
            new UpdateEntityConfigEntityValueQuery(
                ProductName::class,
                'dataaudit',
                'auditable',
                true
            )
        );

        $queries->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(
                ProductName::class,
                'string',
                'dataaudit',
                'auditable',
                true
            )
        );

        $queries->addPostQuery(
            new UpdateEntityConfigEntityValueQuery(
                ProductDescription::class,
                'dataaudit',
                'auditable',
                true
            )
        );

        $queries->addPostQuery(
            new UpdateEntityConfigEntityValueQuery(
                ProductShortDescription::class,
                'dataaudit',
                'auditable',
                true
            )
        );

        $queries->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(
                ProductShortDescription::class,
                'text',
                'dataaudit',
                'auditable',
                true
            )
        );
    }
}
