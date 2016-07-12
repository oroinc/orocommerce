<?php

namespace OroB2B\Bundle\RFPBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RenameTablesAndColumns implements Migration, RenameExtensionAwareInterface
{
    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // email to request association
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_rel_26535370f42ab603f15753',
            'oro_rel_26535370f42ab603ec4b1d'
        );

        // calendar event to request association
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_rel_46a29d19f42ab603f15753',
            'oro_rel_46a29d19f42ab603ec4b1d'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }
}
