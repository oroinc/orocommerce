<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_21;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CatalogBundle\Migrations\Schema\OroCatalogBundleInstaller;
use Oro\Bundle\CommerceMenuBundle\Migrations\Schema\OroCommerceMenuBundleInstaller;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddCategoryMenuUpdateRelation implements Migration, ExtendExtensionAwareInterface
{
    private ?ExtendExtension $extendExtension = null;

    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addCategoryProductRelation($schema);
    }

    protected function addCategoryProductRelation(Schema $schema): void
    {
        $table = $schema->getTable(OroCatalogBundleInstaller::ORO_CATALOG_CATEGORY_TABLE_NAME);
        $targetTable = $schema->getTable(OroCommerceMenuBundleInstaller::ORO_COMMERCE_MENU_UPDATE_TABLE_NAME);

        $this->extendExtension->addManyToOneRelation(
            $schema,
            $targetTable,
            'category',
            $table,
            'id',
            [
                ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                'extend' => [
                    'is_extend' => true,
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'on_delete' => 'CASCADE',
                ],
                'form' => ['is_enabled' => false],
            ]
        );
    }
}
