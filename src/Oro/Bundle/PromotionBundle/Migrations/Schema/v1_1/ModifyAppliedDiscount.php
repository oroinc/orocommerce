<?php

namespace Oro\Bundle\PromotionBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveFieldQuery;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;

class ModifyAppliedDiscount implements Migration, OrderedMigrationInterface, DatabasePlatformAwareInterface
{
    use MigrationConstraintTrait;
    use DatabasePlatformAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 20;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->modifyAppliedDiscount($schema, $queries);
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
     */
    protected function modifyAppliedDiscount(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_promotion_applied_discount');

        $table->dropColumn('type');
        $table->dropColumn('promotion_name');
        $table->dropColumn('config_options');

        $table->removeForeignKey($this->getConstraintName($table, 'line_item_id'));

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_order_line_item'),
            ['line_item_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );

        $queries->addPostQuery(new RemoveFieldQuery(AppliedDiscount::class, 'order'));
        $queries->addPostQuery(new RemoveFieldQuery(AppliedDiscount::class, 'promotion'));

        $table->removeForeignKey($this->getConstraintName($table, 'order_id'));
        $table->removeForeignKey($this->getConstraintName($table, 'promotion_id'));

        $table->dropColumn('order_id');
        $table->dropColumn('promotion_id');

        // Drop order_id and promotion_id indexes
        foreach ($table->getIndexes() as $index) {
            $columns = $index->getColumns();
            if ($columns === ['order_id'] || $columns === ['promotion_id']) {
                $table->dropIndex($index->getName());
            }
        }

        $postSchema = clone $schema;
        $postSchema->getTable('oro_promotion_applied_discount')
            ->getColumn('applied_promotion_id')
            ->setNotnull(true);
        $postSchema->getTable('oro_promotion_applied')
            ->getColumn('promotion_data')
            ->setNotnull(true);
        $postQueries = $this->getSchemaDiff($schema, $postSchema);
        foreach ($postQueries as $query) {
            $queries->addPostQuery($query);
        }
    }
}
