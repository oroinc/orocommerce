<?php

namespace OroB2B\Bundle\FallbackBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

class OroB2BWebsiteBundle implements Migration, RenameExtensionAwareInterface, NameGeneratorAwareInterface
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
     * {@inheritdoc}
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
        $table = $schema->getTable('orob2b_locale');
        $table->addColumn('formatting_code', 'string', ['length' => 64]);
        $table->dropIndex('uniq_orob2b_locale_code');

        $this->renameExtension->renameColumn($schema, $queries, $table, 'title', 'name');
        $this->renameExtension->renameColumn($schema, $queries, $table, 'code', 'language_code');

        $schema->dropTable('oro_localization');
        $this->renameExtension->renameTable($schema, $queries, 'orob2b_locale', 'oro_localization');
        $this->renameExtension->renameTable($schema, $queries, 'orob2b_websites_locales', 'orob2b_websites_localizations');

        $queries->addQuery('UPDATE `oro_localization` SET `formatting_code` = `language_code`');
    }
}
