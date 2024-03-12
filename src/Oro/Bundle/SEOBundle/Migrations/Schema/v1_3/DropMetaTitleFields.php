<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendNameGeneratorAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\Product;

class DropMetaTitleFields implements
    Migration,
    OrderedMigrationInterface,
    NameGeneratorAwareInterface
{
    use ExtendNameGeneratorAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new DropMetaTitleFieldsQuery(Product::class, $this->nameGenerator));
        $queries->addQuery(new DropMetaTitlesEntityConfigValuesQuery(Product::class, 'metaTitles', 'product'));

        $queries->addQuery(new DropMetaTitleFieldsQuery(Page::class, $this->nameGenerator));
        $queries->addQuery(new DropMetaTitlesEntityConfigValuesQuery(Page::class, 'metaTitles', 'page'));

        $queries->addQuery(new DropMetaTitleFieldsQuery(Category::class, $this->nameGenerator));
        $queries->addQuery(new DropMetaTitlesEntityConfigValuesQuery(Category::class, 'metaTitles', 'category'));
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 10;
    }
}
