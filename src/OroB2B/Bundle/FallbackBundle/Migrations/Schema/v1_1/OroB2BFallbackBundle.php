<?php

namespace OroB2B\Bundle\FallbackBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

class OroB2BFallbackBundle implements Migration, RenameExtensionAwareInterface, NameGeneratorAwareInterface
{
    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * @var DbIdentifierNameGenerator
     */
    protected $nameGenerator;

    /**
     * @inheritdoc
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

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
        $indexName = $this->nameGenerator->generateIndexName('orob2b_fallback_locale_value', ['locale_id']);
        $constraintName = $this->nameGenerator->generateForeignKeyConstraintName('orob2b_fallback_locale_value', [
            'locale_id'
        ]);

        $table = $schema->getTable('orob2b_fallback_locale_value');
        $table->renameIndex('idx_orob2b_fallback_fallback', 'idx_fallback');
        $table->renameIndex('idx_orob2b_fallback_string', 'idx_string');
        $table->dropIndex($indexName);
        $table->removeForeignKey($constraintName);

        $this->renameExtension->renameColumn($schema, $queries, $table, 'locale_id', 'localization_id');

        $schema->dropTable('oro_fallback_localization_val');
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'orob2b_fallback_locale_value',
            'oro_fallback_localization_val'
        );

        $indexName = $this->nameGenerator->generateIndexName('oro_fallback_localization_val', ['localization_id']);
        $this->renameExtension->addIndex(
            $schema,
            $queries,
            'oro_fallback_localization_val',
            ['localization_id'],
            $indexName
        );

        $this->renameExtension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_fallback_localization_val',
            'oro_localization',
            ['localization_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
