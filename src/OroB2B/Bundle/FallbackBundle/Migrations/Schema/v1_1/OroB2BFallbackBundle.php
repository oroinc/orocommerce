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
        $queries->addPreQuery('INSERT INTO `oro_fallback_localization_val` ' .
            '(id, localization_id, fallback, string, text) ' .
            'SELECT id, locale_id, fallback, string, text FROM `orob2b_fallback_locale_value`'
        );

        $this->dropConstraint($schema, 'orob2b_catalog_cat_long_desc', ['localized_value_id']);
        $this->dropConstraint($schema, 'orob2b_catalog_cat_short_desc', ['localized_value_id']);
        $this->dropConstraint($schema, 'orob2b_catalog_category_title', ['localized_value_id']);

        $this->dropConstraint($schema, 'orob2b_menu_item_title', ['localized_value_id']);

        $this->dropConstraint($schema, 'orob2b_product_description', ['localized_value_id']);
        $this->dropConstraint($schema, 'orob2b_product_name', ['localized_value_id']);
        $this->dropConstraint($schema, 'orob2b_product_short_desc', ['localized_value_id']);

        $queries->addQuery('DROP TABLE `orob2b_fallback_locale_value`');

        $queries->addPostQuery(new InsertDefaultLocalizationTitleQuery());
    }

    /**
     * @param Schema $schema
     * @param string $tableName
     * @param array $fields
     */
    protected function dropConstraint(Schema $schema, $tableName, array $fields)
    {
        $constraintName = $this->nameGenerator->generateForeignKeyConstraintName($tableName, $fields, true);

        $schema->getTable($tableName)->removeForeignKey($constraintName);
    }
}
