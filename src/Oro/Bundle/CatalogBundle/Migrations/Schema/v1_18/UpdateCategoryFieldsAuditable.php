<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_18;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryShortDescription;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateCategoryFieldsAuditable implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->enableCategoryTitleAuditable($queries);
        $this->disableCategoryLongDescAuditable($queries);
        $this->disableCategoryShortDescAuditable($queries);
    }

    private function enableCategoryTitleAuditable(QueryBag $queries): void
    {
        $queries->addPostQuery(
            new UpdateEntityConfigEntityValueQuery(
                CategoryTitle::class,
                'dataaudit',
                'auditable',
                true
            )
        );

        $queries->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(
                CategoryTitle::class,
                'string',
                'dataaudit',
                'auditable',
                true
            )
        );
    }

    private function disableCategoryLongDescAuditable(QueryBag $queries): void
    {
        $queries->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(
                Category::class,
                'longDescriptions',
                'dataaudit',
                'auditable',
                false
            )
        );

        $queries->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(
                CategoryShortDescription::class,
                'text',
                'dataaudit',
                'auditable',
                true
            )
        );
    }

    private function disableCategoryShortDescAuditable(QueryBag $queries): void
    {
        $queries->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(
                Category::class,
                'shortDescriptions',
                'dataaudit',
                'auditable',
                false
            )
        );
    }
}
