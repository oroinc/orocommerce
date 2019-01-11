<?php

namespace Oro\Bundle\ConsentBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Removes old customer_user_id field (with needed constreints) from table oro_consent_acceptance
 * It is replaced by customerUser_id column in AddCustomerUserRelation migration
 */
class DropOldCustomerUserIdField implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_consent_acceptance');

        $table->dropIndex('oro_customer_consent_uidx');
        $table->removeForeignKey('FK_5B063A1BBBB3772B');
        $table->dropColumn('customer_user_id');
    }

    /**
     * Must be executed after AddCustomerUserRelation when we sync data from old field
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 2;
    }
}
