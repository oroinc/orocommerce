<?php

namespace Oro\Bundle\RFPBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ConfigBundle\Migration\RenameConfigSectionQuery;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\FrontendBundle\Migration\UpdateExtendRelationQuery;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroRFPBundle implements Migration, RenameExtensionAwareInterface, OrderedMigrationInterface
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

        // email to request association
        $extension->renameTable($schema, $queries, 'oro_rel_26535370f42ab603f15753', 'oro_rel_26535370f42ab603ec4b1d');
        $queries->addQuery(new UpdateExtendRelationQuery(
            'Oro\Bundle\EmailBundle\Entity\Email',
            'Oro\Bundle\RFPBundle\Entity\Request',
            'request_9fd4910b',
            'request_d1d045e1',
            RelationType::MANY_TO_MANY
        ));

        // rename tables
        $extension->renameTable($schema, $queries, 'orob2b_rfp_request', 'oro_rfp_request');
        $extension->renameTable($schema, $queries, 'orob2b_rfp_status', 'oro_rfp_status');
        $extension->renameTable($schema, $queries, 'orob2b_rfp_status_translation', 'oro_rfp_status_translation');
        $extension->renameTable($schema, $queries, 'orob2b_rfp_request_product', 'oro_rfp_request_product');
        $extension->renameTable($schema, $queries, 'orob2b_rfp_request_prod_item', 'oro_rfp_request_prod_item');

        // rename indexes
        $schema->getTable('orob2b_rfp_status')->dropIndex('orob2b_rfp_status_name_idx');
        $schema->getTable('orob2b_rfp_status_translation')->dropIndex('orob2b_rfp_status_trans_idx');

        $extension->addIndex($schema, $queries, 'oro_rfp_status', ['name'], 'oro_rfp_status_name_idx');
        $extension->addIndex(
            $schema,
            $queries,
            'oro_rfp_status_translation',
            ['locale', 'object_id', 'field'],
            'oro_rfp_status_trans_idx'
        );

        // system configuration
        $queries->addPostQuery(new RenameConfigSectionQuery('oro_b2b_rfp', 'oro_rfp'));
    }

    /**
     * Should be executed before:
     * @see \Oro\Bundle\RFPBundle\Migrations\Schema\v1_6\MigrateNotes
     *
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }
}
