<?php

namespace Oro\Bundle\CommerceMenuBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityConfigBundle\Migration\RemoveFieldQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class DropOwnershipFromMenuUpdateTable implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_commerce_menu_upd');
        $table->dropColumn('ownership_type');
        $table->dropColumn('owner_id');
        if ($table->hasIndex('oro_commerce_menu_upd_uidx')) {
            $table->dropIndex('oro_commerce_menu_upd_uidx');
        }
        $queries->addQuery(
            new RemoveFieldQuery(
                'Oro\Bundle\CommerceMenuBundle\Entity\MenuUpdate',
                'ownershipType'
            )
        );
        $queries->addQuery(
            new RemoveFieldQuery(
                'Oro\Bundle\CommerceMenuBundle\Entity\MenuUpdate',
                'ownerId'
            )
        );
    }
}
