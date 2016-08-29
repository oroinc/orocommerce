<?php

namespace Oro\Bundle\TaxBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\FrontendBundle\Migration\UpdateClassNamesQuery;
use Oro\Bundle\FrontendBundle\Migration\UpdateSerializedClassNames;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RenameTables implements Migration, RenameExtensionAwareInterface
{
    /**
     * @var RenameExtension
     */
    private $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $extension = $this->renameExtension;

        // rename tables
        $extension->renameTable($schema, $queries, 'orob2b_tax', 'oro_tax');
        $extension->renameTable($schema, $queries, 'orob2b_tax_acc_grp_tc_acc_grp', 'oro_tax_acc_grp_tc_acc_grp');
        $extension->renameTable($schema, $queries, 'orob2b_tax_acc_tax_code_acc', 'oro_tax_acc_tax_code_acc');
        $extension->renameTable($schema, $queries, 'orob2b_tax_account_tax_code', 'oro_tax_account_tax_code');
        $extension->renameTable($schema, $queries, 'orob2b_tax_jurisdiction', 'oro_tax_jurisdiction');
        $extension->renameTable($schema, $queries, 'orob2b_tax_prod_tax_code_prod', 'oro_tax_prod_tax_code_prod');
        $extension->renameTable($schema, $queries, 'orob2b_tax_product_tax_code', 'oro_tax_product_tax_code');
        $extension->renameTable($schema, $queries, 'orob2b_tax_rule', 'oro_tax_rule');
        $extension->renameTable($schema, $queries, 'orob2b_tax_zip_code', 'oro_tax_zip_code');
        $extension->renameTable($schema, $queries, 'orob2b_tax_value', 'oro_tax_value');

        // rename indexes
        $schema->getTable('orob2b_tax_value')->dropIndex('orob2b_tax_value_class_id_idx');

        $extension->addIndex(
            $schema,
            $queries,
            'oro_tax_value',
            ['entity_class', 'entity_id'],
            'oro_tax_value_class_id_idx'
        );

        // fix class names stored in DB
        $queries->addQuery(new UpdateClassNamesQuery('oro_tax_value', 'entity_class'));
        $queries->addQuery(new UpdateSerializedClassNames('oro_tax_value', 'result'));
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }
}
