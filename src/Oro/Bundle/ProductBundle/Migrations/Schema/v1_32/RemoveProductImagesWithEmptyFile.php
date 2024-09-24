<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_32;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Migration for removing ProductImages without image files
 */
class RemoveProductImagesWithEmptyFile implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $tableName = 'oro_product_image';
        if ($schema->hasTable($tableName)) {
            $queries->addPostQuery(
                sprintf('DELETE FROM %s pi WHERE pi.image_id IS NULL', $tableName)
            );
        }
    }
}
