<?php

namespace OroB2B\Bundle\ProductBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityConfigBundle\Migration\RemoveManyToOneRelationQuery;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveImageRelationOnProduct implements
    Migration,
    OrderedMigrationInterface,
    ExtendExtensionAwareInterface
{
    const PRODUCT_TABLE_NAME = 'orob2b_product';
    const PRODUCT_IMAGE_FIELD_NAME = 'image_id';
    const PRODUCT_IMAGE_FK_NAME = 'fk_orob2b_product_image_id';
    const PRODUCT_IMAGE_ASSOCCIATION_NAME = 'image';

    /**
     * @var ExtendExtension
     */
    protected $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $productClass = $this->extendExtension->getEntityClassByTableName(self::PRODUCT_TABLE_NAME);
        $productTable = $schema->getTable(self::PRODUCT_TABLE_NAME);
        $productTable->removeForeignKey(self::PRODUCT_IMAGE_FK_NAME);
        $productTable->dropColumn(self::PRODUCT_IMAGE_FIELD_NAME);

        $queries->addQuery(
            new RemoveManyToOneRelationQuery(
                $productClass,
                self::PRODUCT_IMAGE_ASSOCCIATION_NAME
            )
        );

    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 20;
    }
}
