<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateProductImageTypeTable implements Migration
{
    public const PRODUCT_IMAGE_TYPE_TABLE = 'oro_product_image_type';
    public const PRODUCT_IMAGE_TABLE = 'oro_product_image';
    public const FOREIGN_COLUMN = 'id';
    public const LOCAL_COLUMN = 'product_image_id';

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable(self::PRODUCT_IMAGE_TYPE_TABLE);

        foreach ($table->getForeignKeys() as $foreignKey) {
            if (
                $foreignKey->getForeignTableName() === self::PRODUCT_IMAGE_TABLE &&
                $foreignKey->getLocalColumns() === [self::LOCAL_COLUMN]
            ) {
                $table->removeForeignKey($foreignKey->getName());
            }
        }

        $table->addForeignKeyConstraint(
            $schema->getTable(self::PRODUCT_IMAGE_TABLE),
            [self::LOCAL_COLUMN],
            [self::FOREIGN_COLUMN],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
