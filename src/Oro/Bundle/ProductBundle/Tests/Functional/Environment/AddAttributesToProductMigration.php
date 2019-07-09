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

        $this->addEnumAttribute($schema, $productTable);
        $this->addMultiEnumAttribute($schema, $productTable);
        $this->addManyToOneAttribute($schema, $productTable);
        $this->addManyToOneAttributeWithIdentifiedFieldAsTitle($schema, $productTable);
        $this->addManyToManyAttribute($schema, $productTable);
        $this->addManyToManyAttributeWithIdentifiedFieldAsTitle($schema, $productTable);
        $this->addInvisibleAttribute($productTable);
        $this->addBooleanAttribute($productTable);
        $this->addStringAttribute($productTable);
        $this->addIntegerAttribute($productTable);
        $this->addFloatAttribute($productTable);
        $this->addDateTimeAttribute($productTable);
        $this->addMoneyAttribute($productTable);
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
     * @param Schema $schema
     * @param Table  $table
     */
    private function addEnumAttribute(Schema $schema, Table $table)
    {
        $this->extendExtension->addEnumField(
            $schema,
            $table,
            'testAttrEnum',
            'test_prod_attr_enum',
            false,
            false,
            $this->getAttributeOptions([
                'entity' => ['label' => 'extend.entity.test.test_attr_enum'],
                'attribute' => ['sortable' => true]
            ])
        );
    }

    /**
     * @param Schema $schema
     * @param Table  $table
     */
    private function addMultiEnumAttribute(Schema $schema, Table $table)
    {
        $this->extendExtension->addEnumField(
            $schema,
            $table,
            'testAttrMultiEnum',
            'test_prod_attr_m_enum',
            true,
            false,
            $this->getAttributeOptions([
                'entity' => ['label' => 'extend.entity.test.test_attr_multi_enum']
            ])
        );
    }

    /**
     * @param Schema $schema
     * @param Table  $table
     */
    private function addManyToOneAttribute(Schema $schema, Table $table)
    {
        $this->extendExtension->addManyToOneRelation(
            $schema,
            $table,
            'testAttrManyToOne',
            'oro_customer',
            'name',
            $this->getAttributeOptions([
                'entity' => ['label' => 'extend.entity.test.test_attr_many_to_one'],
                'attribute' => ['sortable' => true]
            ])
        );
    }

    /**
     * @param Schema $schema
     * @param Table  $table
     */
    private function addManyToOneAttributeWithIdentifiedFieldAsTitle(Schema $schema, Table $table)
    {
        $this->extendExtension->addManyToOneRelation(
            $schema,
            $table,
            'testToOneId',
            'oro_dictionary_country',
            'iso2_code',
            [
                'entity' => ['label' => 'extend.entity.test.test_attr_many_to_one'],
                'extend'       => [
                    'is_extend' => true,
                    'owner'     => ExtendScope::OWNER_CUSTOM
                ],
                'attribute'    => [
                    'is_attribute' => true,
                    'filterable'   => false,
                    'sortable'     => false,
                    'searchable'   => false,
                    'enabled'      => true
                ],
                'importexport' => [
                    'excluded' => true
                ]
            ]
        );
    }

    /**
     * @param Schema $schema
     * @param Table  $table
     */
    private function addManyToManyAttribute(Schema $schema, Table $table)
    {
        $this->extendExtension->addManyToManyRelation(
            $schema,
            $table,
            'testAttrManyToMany',
            'oro_customer_user',
            ['first_name', 'middle_name', 'last_name'],
            ['first_name', 'middle_name', 'last_name'],
            ['first_name', 'middle_name', 'last_name'],
            $this->getAttributeOptions([
                'entity' => ['label' => 'extend.entity.test.test_attr_many_to_many']
            ])
        );
    }

    /**
     * @param Schema $schema
     * @param Table  $table
     */
    private function addManyToManyAttributeWithIdentifiedFieldAsTitle(Schema $schema, Table $table)
    {
        $this->extendExtension->addManyToManyRelation(
            $schema,
            $table,
            'testToManyId',
            'oro_dictionary_country',
            ['iso2_code'],
            ['iso2_code'],
            ['iso2_code'],
            $this->getAttributeOptions([
                'entity' => ['label' => 'extend.entity.test.test_attr_many_to_many']
            ])
        );
    }

    /**
     * @param Table $table
     */
    private function addInvisibleAttribute(Table $table)
    {
        $table->addColumn(
            'testAttrInvisible',
            'string',
            [
                OroOptions::KEY => $this->getAttributeOptions([
                    'entity'    => ['label' => 'extend.entity.test.test_attr_invisible'],
                    'attribute' => ['visible' => false]
                ])
            ]
        );
    }

    /**
     * @param Table $table
     */
    private function addBooleanAttribute(Table $table)
    {
        $table->addColumn(
            'testAttrBoolean',
            'boolean',
            [
                OroOptions::KEY => $this->getAttributeOptions([
                    'entity' => ['label' => 'extend.entity.test.test_attr_boolean']
                ])
            ]
        );
    }

    /**
     * @param Table $table
     */
    private function addStringAttribute(Table $table)
    {
        $table->addColumn(
            'testAttrString',
            'string',
            [
                OroOptions::KEY => $this->getAttributeOptions([
                    'entity' => ['label' => 'extend.entity.test.test_attr_string']
                ])
            ]
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

    /**
     * @param Table $table
     */
    private function addDateTimeAttribute(Table $table)
    {
        $table->addColumn(
            'testAttrDateTime',
            'datetime',
            [
                OroOptions::KEY => $this->getAttributeOptions([
                    'entity' => ['label' => 'extend.entity.test.test_attr_date_time']
                ])
            ]
        );
    }

    /**
     * @param Table $table
     */
    private function addMoneyAttribute(Table $table)
    {
        $table->addColumn(
            'testAttrMoney',
            'money',
            [
                OroOptions::KEY => $this->getAttributeOptions([
                    'entity' => ['label' => 'extend.entity.test.test_attr_money']
                ])
            ]
        );
    }
}
