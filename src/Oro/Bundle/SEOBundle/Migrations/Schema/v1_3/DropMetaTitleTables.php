<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendNameGeneratorAwareTrait;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\Product;

class DropMetaTitleTables implements
    Migration,
    OrderedMigrationInterface,
    NameGeneratorAwareInterface
{
    use ExtendNameGeneratorAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $schema->dropTable($this->nameGenerator->generateManyToManyJoinTableName(
            Product::class,
            'metaTitles',
            LocalizedFallbackValue::class
        ));
        $schema->dropTable($this->nameGenerator->generateManyToManyJoinTableName(
            Page::class,
            'metaTitles',
            LocalizedFallbackValue::class
        ));
        $schema->dropTable($this->nameGenerator->generateManyToManyJoinTableName(
            Category::class,
            'metaTitles',
            LocalizedFallbackValue::class
        ));
    }

    #[\Override]
    public function getOrder()
    {
        return 20;
    }
}
