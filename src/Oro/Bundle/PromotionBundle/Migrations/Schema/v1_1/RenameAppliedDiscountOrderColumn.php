<?php

namespace Oro\Bundle\PromotionBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityConfigBundle\Migration\RemoveFieldQuery;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;

class RenameAppliedDiscountOrderColumn implements Migration, RenameExtensionAwareInterface, OrderedMigrationInterface
{
    use MigrationConstraintTrait;

    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * Sets the RenameExtension
     *
     * @param RenameExtension $renameExtension
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_promotion_applied_discount');
        $this->renameExtension->renameColumn($schema, $queries, $table, 'order_id', 'deprecated_order_id');

        $queries->addPostQuery(
            new RemoveFieldQuery(AppliedDiscount::class, 'order')
        );

        $table->removeForeignKey($this->getConstraintName($table, 'order_id'));

        // Drop order_id index
        foreach ($table->getIndexes() as $index) {
            if ($index->getColumns() === ['order_id']) {
                $table->dropIndex($index->getName());
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 1;
    }
}
