<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

class DropMetaTitleTables implements
    Migration,
    OrderedMigrationInterface,
    NameGeneratorAwareInterface,
    ExtendExtensionAwareInterface
{
    /**
     * @var ExtendDbIdentifierNameGenerator
     */
    protected $nameGenerator;

    /**
     * @var ExtendExtension
     */
    protected $extendExtension;

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
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $schema->dropTable($this->getAssociationTableName('oro_product', 'oro_fallback_localization_val'));
        $schema->dropTable($this->getAssociationTableName('oro_cms_page', 'oro_fallback_localization_val'));
        $schema->dropTable($this->getAssociationTableName('oro_catalog_category', 'oro_fallback_localization_val'));
    }

    /**
     * @param string $sourceTable
     * @param string $targetTable
     * @return string
     */
    protected function getAssociationTableName($sourceTable, $targetTable)
    {
        $sourceClassName = $this->extendExtension->getEntityClassByTableName($sourceTable);
        $targetClassName = $this->extendExtension->getEntityClassByTableName($targetTable);

        return $this->nameGenerator->generateManyToManyJoinTableName(
            $sourceClassName,
            'metaTitles',
            $targetClassName
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 20;
    }
}
