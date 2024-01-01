<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_21;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddCategoryMenuUpdateRelation implements Migration, ExtendExtensionAwareInterface
{
    use ExtendExtensionAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->extendExtension->addManyToOneRelation(
            $schema,
            $schema->getTable('oro_commerce_menu_upd'),
            'category',
            $schema->getTable('oro_catalog_category'),
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
