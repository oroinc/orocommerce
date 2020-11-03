<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_23;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\Brand;

/**
 * Updates acl_protected entity field config option for Brand descriptions.
 */
class UpdateAttachmentFieldConfigForBrandDescriptions implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(Brand::class, 'descriptions', 'attachment', 'acl_protected', false)
        );
    }
}
