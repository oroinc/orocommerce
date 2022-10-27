<?php

namespace Oro\Bundle\PromotionBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Add Coupon code_uppercase Field for faster search when insensitive search enabled.
 */
class AddCouponCodeUppercaseField implements Migration, DatabasePlatformAwareInterface
{
    /** @var AbstractPlatform */
    protected $platform;

    /**
     * {@inheritdoc}
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_promotion_coupon');
        if ($table->hasColumn('code_uppercase')) {
            return;
        }
        $table->addColumn('code_uppercase', 'string', ['length' => 255, 'notnull' => false]);
        $table->addIndex(['code_uppercase'], 'idx_oro_promotion_coupon_code_upper', []);

        $queries->addPostQuery('UPDATE oro_promotion_coupon SET code_uppercase = UPPER(code)');

        $postSchema = clone $schema;
        $postSchema->getTable('oro_promotion_coupon')
            ->changeColumn('code_uppercase', ['notnull' => true]);
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
}
