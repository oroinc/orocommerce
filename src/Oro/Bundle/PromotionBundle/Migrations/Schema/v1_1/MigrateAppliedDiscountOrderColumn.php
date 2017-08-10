<?php

namespace Oro\Bundle\PromotionBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MigrateAppliedDiscountOrderColumn implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->moveOrderColumnToDeprecated($queries);
    }

    /**
     * @param QueryBag $queries
     */
    private function moveOrderColumnToDeprecated(QueryBag $queries)
    {
        $sql = <<<SQL
UPDATE oro_promotion_applied_discount SET order_id = deprecated_order_id
SQL;
        $queries->addQuery($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 3;
    }
}
