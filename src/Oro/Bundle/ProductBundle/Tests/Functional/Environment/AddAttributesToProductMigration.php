<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Environment;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * This migration add attributes to Product entity to use in functional tests.
 */
class AddAttributesToProductMigration implements Migration, ExtendExtensionAwareInterface
{
    /** @var ExtendExtension */
    private $extendExtension;

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
        $productTable = $schema->getTable('oro_product');
        if ($productTable->hasColumn('testAttrEnum_id')) {
            return;
        }

        $this->addIntegerAttribute($productTable);
        $this->addFloatAttribute($productTable);
    }

    /**
     * @param array $options
     *
     * @return array
     */
    private function getAttributeOptions(array $options)
    {
        return array_merge_recursive(
            [
                'extend'       => [
                    'is_extend' => true,
                    'owner'     => ExtendScope::OWNER_CUSTOM
                ],
                'attribute'    => [
                    'is_attribute' => true,
                    'filterable'   => true,
                    'enabled'      => true
                ],
                'importexport' => [
                    'excluded' => true
                ]
            ],
            $options
        );
    }

    /**
     * @param Table $table
     */
    private function addIntegerAttribute(Table $table)
    {
        $table->addColumn(
            'testAttrInteger',
            'integer',
            [
                OroOptions::KEY => $this->getAttributeOptions([
                    'entity' => ['label' => 'extend.entity.test.test_attr_integer'],
                    'attribute' => ['sortable' => true]
                ])
            ]
        );
    }

    /**
     * @param Table $table
     */
    private function addFloatAttribute(Table $table)
    {
        $table->addColumn(
            'testAttrFloat',
            'float',
            [
                OroOptions::KEY => $this->getAttributeOptions([
                    'entity' => ['label' => 'extend.entity.test.test_attr_float'],
                    'attribute' => ['sortable' => true]
                ])
            ]
        );
    }
}
