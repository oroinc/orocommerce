<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Migrations\Schema\v1_2\DropEntityConfigFieldQuery;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtensionAwareInterface;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtensionAwareTrait;

class ReorganizePageSlugs implements Migration, DatabasePlatformAwareInterface, SlugExtensionAwareInterface
{
    use DatabasePlatformAwareTrait;
    use SlugExtensionAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->slugExtension->addLocalizedSlugPrototypes(
            $schema,
            'oro_cms_page_slug_prototype',
            'oro_cms_page',
            'page_id'
        );

        $queries->addQuery(new ReorganizeSlugsQuery());
        $this->dropCurrentSlugRelation($schema, $queries);
    }

    /**
     * @param Schema $schema
     * @param Schema $toSchema
     * @return array
     */
    protected function getSchemaDiff(Schema $schema, Schema $toSchema)
    {
        $comparator = new Comparator();

        return $comparator->compare($schema, $toSchema)->toSql($this->platform);
    }

    protected function dropCurrentSlugRelation(Schema $schema, QueryBag $queries)
    {
        $preSchema = clone $schema;
        $table = $preSchema->getTable('oro_cms_page');

        $columnFk = null;
        foreach ($table->getForeignKeys() as $foreignKey) {
            if ($foreignKey->getColumns() === ['current_slug_id']) {
                $columnFk = $foreignKey->getName();
            }
        }

        if ($columnFk) {
            $table->removeForeignKey($columnFk);
        }
        if ($table->hasIndex('UNIQ_99CF638E9B14E34B')) {
            $table->dropIndex('UNIQ_99CF638E9B14E34B');
        }
        if ($table->hasColumn('current_slug_id')) {
            $table->dropColumn('current_slug_id');
        }

        foreach ($this->getSchemaDiff($schema, $preSchema) as $query) {
            $queries->addQuery($query);
        }
        $queries->addQuery(new DropEntityConfigFieldQuery(Page::class, 'currentSlug'));
    }
}
