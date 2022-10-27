<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_25;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductShortDescription;

class UpdateProductFieldsAuditable implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->enableProductNameAuditable($queries);
        $this->disableProductDescAuditable($queries);
        $this->disableProductShortDescAuditable($queries);
        $this->disableBrandDescAuditable($queries);
    }

    private function enableProductNameAuditable(QueryBag $queryBag): void
    {
        $queryBag->addPostQuery(
            new UpdateEntityConfigEntityValueQuery(
                ProductName::class,
                'dataaudit',
                'auditable',
                true
            )
        );

        $queryBag->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(
                ProductName::class,
                'string',
                'dataaudit',
                'auditable',
                true
            )
        );
    }

    private function disableProductDescAuditable(QueryBag $queryBag): void
    {
        $queryBag->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(
                Product::class,
                'descriptions',
                'dataaudit',
                'auditable',
                false
            )
        );
    }

    private function disableProductShortDescAuditable(QueryBag $queryBag): void
    {
        $queryBag->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(
                Product::class,
                'shortDescriptions',
                'dataaudit',
                'auditable',
                false
            )
        );

        $queryBag->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(
                ProductShortDescription::class,
                'text',
                'dataaudit',
                'auditable',
                true
            )
        );
    }

    private function disableBrandDescAuditable(QueryBag $queryBag): void
    {
        $queryBag->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(
                Brand::class,
                'shortDescriptions',
                'dataaudit',
                'auditable',
                false
            )
        );

        $queryBag->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(
                Brand::class,
                'descriptions',
                'dataaudit',
                'auditable',
                false
            )
        );
    }
}
