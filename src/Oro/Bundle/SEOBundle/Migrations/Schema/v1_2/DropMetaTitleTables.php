<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;
use Oro\Bundle\ProductBundle\Entity\Product;

class DropMetaTitleTables implements
    Migration,
    OrderedMigrationInterface,
    NameGeneratorAwareInterface
{
    /**
     * @var ExtendDbIdentifierNameGenerator
     */
    protected $nameGenerator;

    /**
     * {@inheritdoc}
     */
    public function setNameGenerator(DbIdentifierNameGenerator $nameGenerator)
    {
        $this->nameGenerator = $nameGenerator;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 20;
    }
}
