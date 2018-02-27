<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\ProductImage;

class UpdateProductImageExportConfigFields implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery(
                ProductImage::class,
                'product',
                'importexport',
                'excluded',
                false
            )
        );

        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery(
                ProductImage::class,
                'image',
                'importexport',
                'excluded',
                false
            )
        );

        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery(
                ProductImage::class,
                'types',
                'importexport',
                'full',
                true
            )
        );

        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery(
                ProductImage::class,
                'types',
                'importexport',
                'excluded',
                false
            )
        );
    }
}
