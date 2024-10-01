<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveManyToOneRelationQuery;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveImageRelationOnProduct implements
    Migration,
    OrderedMigrationInterface,
    ExtendExtensionAwareInterface
{
    use ExtendExtensionAwareTrait;

    #[\Override]
    public function getOrder(): int
    {
        return 20;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $productClass = $this->extendExtension->getEntityClassByTableName('oro_product');
        $productTable = $schema->getTable('orob2b_product');
        $productTable->removeForeignKey('fk_orob2b_product_image_id');
        $productTable->dropColumn('image_id');

        $queries->addQuery(new RemoveManyToOneRelationQuery($productClass, 'image'));
    }
}
