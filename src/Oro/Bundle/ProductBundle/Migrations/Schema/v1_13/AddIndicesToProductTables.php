<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_13;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Migrations\Schema\OroProductBundleInstaller;

class AddIndicesToProductTables implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addProductIndices($schema);
        $this->addProductImageTypeIndex($schema);
    }

    protected function addProductIndices(Schema $schema)
    {
        $table = $schema->getTable(OroProductBundleInstaller::PRODUCT_TABLE_NAME);
        $table->addIndex(['created_at', 'id', 'organization_id'], 'idx_oro_product_created_at_id_organization');
        $table->addIndex(['updated_at', 'id', 'organization_id'], 'idx_oro_product_updated_at_id_organization');
        $table->addIndex(['sku', 'id', 'organization_id'], 'idx_oro_product_sku_id_organization');
        $table->addIndex(['status', 'id', 'organization_id'], 'idx_oro_product_status_id_organization');
        $table->addIndex(['is_featured'], 'idx_oro_product_is_featured');
    }

    protected function addProductImageTypeIndex(Schema $schema)
    {
        $table = $schema->getTable(OroProductBundleInstaller::PRODUCT_IMAGE_TYPE_TABLE_NAME);
        $table->addIndex(['type'], 'idx_oro_product_image_type_type');
    }
}
