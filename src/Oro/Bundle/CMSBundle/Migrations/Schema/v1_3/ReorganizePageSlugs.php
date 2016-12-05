<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtension;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtensionAwareInterface;

class ReorganizePageSlugs implements Migration, DatabasePlatformAwareInterface, SlugExtensionAwareInterface
{
    /**
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * @var SlugExtension
     */
    protected $slugExtension;

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
    public function setSlugExtension(SlugExtension $extension)
    {
        $this->slugExtension = $extension;
    }

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

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    protected function dropCurrentSlugRelation(Schema $schema, QueryBag $queries)
    {
        $preSchema = clone $schema;
        $table = $preSchema->getTable('oro_cms_page');
        if ($table->hasIndex('UNIQ_BCE4CB4A9B14E34B')) {
            $table->dropIndex('UNIQ_BCE4CB4A9B14E34B');
        }
        if ($table->hasForeignKey('FK_BCE4CB4A9B14E34B')) {
            $table->removeForeignKey('FK_BCE4CB4A9B14E34B');
        }
        if ($table->hasColumn('current_slug_id')) {
            $table->dropColumn('current_slug_id');
        }

        foreach ($this->getSchemaDiff($schema, $preSchema) as $query) {
            $queries->addQuery($query);
        }
    }
}
