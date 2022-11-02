<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_13;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Update acl_protected entity field config option for image field of Category entity.
 */
class UpdateAttachmentFieldConfigForCategoryImages implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        foreach (['smallImage', 'largeImage'] as $fieldName) {
            $queries->addPostQuery(
                new UpdateEntityConfigFieldValueQuery(Category::class, $fieldName, 'attachment', 'acl_protected', false)
            );
        }
    }
}
