<?php

namespace Oro\Bundle\PromotionBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ModifyAppliedDiscount implements Migration, DatabasePlatformAwareInterface
{
    use DatabasePlatformAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->modifyAppliedDiscount($schema);
        $queries->addPostQuery(new MigratePromotionDataQuery());

        $postSchema = clone $schema;
        $postSchema->getTable('oro_promotion_applied_discount')
            ->changeColumn('promotion_data', ['notnull' => true]);
        $postQueries = $this->getSchemaDiff($schema, $postSchema);
        foreach ($postQueries as $query) {
            $queries->addPostQuery($query);
        }
    }

    /**
     * @param Schema $schema
     * @param Schema $toSchema
     * @return array
     */
    private function getSchemaDiff(Schema $schema, Schema $toSchema)
    {
        $comparator = new Comparator();
        return $comparator->compare($schema, $toSchema)->toSql($this->platform);
    }

    /**
     * Add fields to applied discount.
     *
     * @param Schema $schema
     */
    protected function modifyAppliedDiscount(Schema $schema)
    {
        $table = $schema->getTable('oro_promotion_applied_discount');
        $table->addColumn('promotion_data', 'json_array', ['notnull' => false]);
        $table->addColumn('enabled', 'boolean', ['default' => true]);
        $table->addColumn('coupon_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('source_promotion_id', 'integer', ['notnull' => false]);
    }
}
