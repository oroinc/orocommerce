<?php

namespace Oro\Bundle\PromotionBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroPromotionBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addValidFromField($schema);
    }

    protected function addValidFromField(Schema $schema)
    {
        $table = $schema->getTable('oro_promotion_coupon');
        $table->addColumn('valid_from', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
    }
}
