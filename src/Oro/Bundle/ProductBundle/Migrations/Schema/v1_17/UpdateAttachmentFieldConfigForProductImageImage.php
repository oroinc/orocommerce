<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_17;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\ProductImage;

/**
 * Update acl_protected entity field config option for image field of ProductImage entity.
 */
class UpdateAttachmentFieldConfigForProductImageImage implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(ProductImage::class, 'image', 'attachment', 'acl_protected', false)
        );
    }
}
